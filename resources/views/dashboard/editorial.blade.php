@extends('layouts.app')

@section('content')
@php
    $categoriesJson = json_encode(array_map(function($c) {
        return [
            'id' => (int)$c['id'],
            'name' => $c['name'],
            'slug' => $c['slug'],
            'color_code' => $c['color_code'] ?? '#ec4899',
            'icon' => $c['icon'] ?? 'tag',
            'description' => $c['description'] ?? '',
            'published_articles_count' => (int)($c['published_articles_count'] ?? 0),
            'total_articles_count' => (int)($c['total_articles_count'] ?? 0),
        ];
    }, $categories));

    $assignmentsJson = json_encode($assignments);
    $journalistsJson = json_encode($journalists);
    $currentHeadlineJson = json_encode($currentHeadline);
    $curatedArticleIdsJson = json_encode($curatedArticleIds ?? []);
@endphp
<script>
window.brighteenEditorialCategories = {!! $categoriesJson !!};
window.brighteenEditorialAssignments = {!! $assignmentsJson !!};
window.brighteenEditorialJournalists = {!! $journalistsJson !!};
window.brighteenCurrentHeadline = {!! $currentHeadlineJson ?: 'null' !!};
window.brighteenCuratedArticleIds = {!! $curatedArticleIdsJson !!};
window.brighteenEditorialNotice = '{{ $notice ?? "" }}';

function editorialDesk() {
    return {
        allCategories: window.brighteenEditorialCategories || [],
        allAssignments: window.brighteenEditorialAssignments || [],
        allJournalists: window.brighteenEditorialJournalists || [],
        curatedArticleIds: window.brighteenCuratedArticleIds || [],
        assignmentFilter: 'all',
        assignmentSearch: '',
        dispatchSearch: '',
        assignmentView: 'grid',

        // Dropdown state for CTA More Options
        moreOptionsOpen: false,

        // Tab state: 'assignments' | 'queue' | 'dispatches'
        activeTab: (function() {
            const hash = window.location.hash ? window.location.hash.replace('#', '') : '';
            if (['queue', 'pending', 'review'].includes(hash)) return 'queue';
            if (['dispatches', 'published', 'headline-manager'].includes(hash)) return 'dispatches';
            return 'assignments';
        })(),

        // Lazy loading state: true when a tab has been activated
        loadedTabs: {
            assignments: false,
            queue: false,
            dispatches: false
        },

        isMobile: typeof window !== 'undefined' ? window.innerWidth < 768 : false,

        mobileSections: {
            assignments: true,
            queue: false,
            dispatches: false
        },

        init() {
            // Immediately activate the initial tab for lazy loading & mobile section
            this.loadedTabs[this.activeTab] = true;
            this.mobileSections = {
                assignments: this.activeTab === 'assignments',
                queue: this.activeTab === 'queue',
                dispatches: this.activeTab === 'dispatches'
            };
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
            });
        },

        setTab(tab) {
            this.activeTab = tab;
            this.loadedTabs[tab] = true;
            this.mobileSections[tab] = true;
            if (window.history && window.history.pushState) {
                window.history.pushState(null, null, '#' + tab);
            } else {
                window.location.hash = '#' + tab;
            }
        },

        toggleMobileSection(tab) {
            if (this.mobileSections[tab]) {
                // Allow collapsing the active section to save maximum space
                this.mobileSections[tab] = false;
            } else {
                // Accordion behavior: close other sections to keep mobile uncluttered and compact
                this.mobileSections.assignments = false;
                this.mobileSections.queue = false;
                this.mobileSections.dispatches = false;
                this.mobileSections[tab] = true;
                this.loadedTabs[tab] = true;
                this.setTab(tab);
                this.$nextTick(() => {
                    const el = document.getElementById(tab + '-section');
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }
        },

        expandAllMobile() {
            this.mobileSections.assignments = true;
            this.mobileSections.queue = true;
            this.mobileSections.dispatches = true;
            this.loadedTabs.assignments = true;
            this.loadedTabs.queue = true;
            this.loadedTabs.dispatches = true;
        },

        collapseAllMobile() {
            this.mobileSections.assignments = false;
            this.mobileSections.queue = false;
            this.mobileSections.dispatches = false;
        },

        // Mobile touch swipe gestures
        touchStartX: 0,
        touchEndX: 0,
        tabOrder: ['assignments', 'queue', 'dispatches'],
        handleTouchStart(e) {
            if (e.touches && e.touches.length > 0) {
                this.touchStartX = e.touches[0].clientX;
            }
        },
        handleTouchEnd(e) {
            if (e.changedTouches && e.changedTouches.length > 0) {
                this.touchEndX = e.changedTouches[0].clientX;
                this.handleSwipe();
            }
        },
        handleSwipe() {
            // Prevent accidental tab switching during vertical mobile scrolling
            if (this.isMobile) return;
            const diff = this.touchEndX - this.touchStartX;
            if (Math.abs(diff) < 55) return;
            const idx = this.tabOrder.indexOf(this.activeTab);
            if (diff < -55 && idx < this.tabOrder.length - 1) {
                // Swiped Left -> Next Tab
                this.setTab(this.tabOrder[idx + 1]);
            } else if (diff > 55 && idx > 0) {
                // Swiped Right -> Previous Tab
                this.setTab(this.tabOrder[idx - 1]);
            }
        },

        // Active Headline Pop-up Preview Modal
        headlinePreviewModalOpen: false,
        previewHeadlineData: window.brighteenCurrentHeadline || null,
        openHeadlinePreview(art = null) {
            this.previewHeadlineData = art || window.brighteenCurrentHeadline || null;
            this.headlinePreviewModalOpen = true;
        },

        // Active Curated Story Pop-up Preview Modal
        curatedPreviewModalOpen: false,
        previewCuratedData: null,
        openCuratedPreview(art = null) {
            this.previewCuratedData = art || null;
            this.curatedPreviewModalOpen = true;
        },
        formatDateStr(str) {
            if (!str) return 'Recently';
            try {
                const s = str.replace(' ', 'T').replace(/-/g, '/');
                const d = new Date(s);
                if (isNaN(d.getTime())) return str;
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            } catch (e) {
                return str;
            }
        },

        matchesDispatchSearch(title, author) {
            if (!this.dispatchSearch || !this.dispatchSearch.trim()) return true;
            const q = this.dispatchSearch.toLowerCase();
            return (title && title.toLowerCase().includes(q)) || (author && author.toLowerCase().includes(q));
        },

        rejectModalOpen: false,
        rejectArticleId: null,
        rejectArticleTitle: '',
        categoryModalOpen: false,
        heroPopupOpen: ['category_created', 'category_updated', 'category_deleted'].includes(window.brighteenEditorialNotice),

        // Assignment Modals state
        createAssignmentModalOpen: false,
        editAssignmentModalOpen: false,
        deleteAssignmentModalOpen: false,
        assignmentToEdit: {
            id: null,
            assigned_to_user_id: '',
            category_id: '',
            topic: '',
            description: '',
            submission_deadline: '',
            publishing_schedule: '',
            status: 'pending'
        },
        assignmentToDelete: {
            id: null,
            topic: ''
        },

        // Edit Category Modal state
        editCategoryModalOpen: false,
        editCategory: {
            id: null,
            name: '',
            color_code: '#ec4899',
            icon: 'tag',
            description: ''
        },

        // Safe Delete Category Modal state
        deleteCategoryModalOpen: false,
        deleteCategoryData: {
            id: null,
            name: '',
            color_code: '#ec4899',
            icon: 'tag',
            total_stories: 0
        },
        targetCategoryId: '',

        // Create Category state for live preview
        newCategory: {
            name: '',
            color_code: '#ec4899',
            icon: 'sparkles',
            description: ''
        },

        openCreateAssignment() {
            this.createAssignmentModalOpen = true;
        },

        openEditAssignment(asg) {
            this.assignmentToEdit = {
                id: asg.id,
                assigned_to_user_id: asg.assigned_to_user_id,
                category_id: asg.category_id,
                topic: asg.topic,
                description: asg.description || '',
                submission_deadline: asg.submission_deadline ? asg.submission_deadline.replace(' ', 'T').substring(0, 16) : '',
                publishing_schedule: asg.publishing_schedule ? asg.publishing_schedule.replace(' ', 'T').substring(0, 16) : '',
                status: asg.status
            };
            this.editAssignmentModalOpen = true;
        },

        openDeleteAssignment(asg) {
            this.assignmentToDelete = {
                id: asg.id,
                topic: asg.topic
            };
            this.deleteAssignmentModalOpen = true;
        },

        filteredAssignments() {
            let list = this.allAssignments;
            if (this.assignmentFilter !== 'all') {
                list = list.filter(a => a.effective_status === this.assignmentFilter);
            }
            if (this.assignmentSearch.trim() !== '') {
                const q = this.assignmentSearch.toLowerCase();
                list = list.filter(a => 
                    (a.topic && a.topic.toLowerCase().includes(q)) ||
                    (a.journalist_name && a.journalist_name.toLowerCase().includes(q)) ||
                    (a.category_name && a.category_name.toLowerCase().includes(q))
                );
            }
            return list;
        },

        formatDateStr(str) {
            if (!str) return '-';
            try {
                const d = new Date(str.replace(' ', 'T'));
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                return str;
            }
        },

        openReject(id, title) {
            this.rejectArticleId = id;
            this.rejectArticleTitle = title;
            this.rejectModalOpen = true;
        },

        openEditCategory(catId) {
            const cat = this.allCategories.find(c => Number(c.id) === Number(catId));
            if (!cat) return;
            this.editCategory = {
                id: cat.id,
                name: cat.name,
                color_code: cat.color_code || '#ec4899',
                icon: cat.icon || 'tag',
                description: cat.description || ''
            };
            this.editCategoryModalOpen = true;
        },

        openDeleteCategory(catId) {
            const cat = this.allCategories.find(c => Number(c.id) === Number(catId));
            if (!cat) return;
            this.deleteCategoryData = {
                id: cat.id,
                name: cat.name,
                color_code: cat.color_code || '#ec4899',
                icon: cat.icon || 'tag',
                total_stories: parseInt(cat.total_articles_count || 0)
            };
            const others = this.getAlternativeCategories();
            this.targetCategoryId = others.length > 0 ? others[0].id : '';
            this.deleteCategoryModalOpen = true;
        },

        getAlternativeCategories() {
            const currentId = Number(this.deleteCategoryData.id);
            if (!currentId) return this.allCategories;
            return this.allCategories.filter(c => Number(c.id) !== currentId);
        },

        hasAlternativeCategories() {
            return this.getAlternativeCategories().length > 0;
        }
    };
}
</script>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="editorialDesk()">

    <!-- Page Header & Operational Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-tertiary-light uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-base">rule</span>
                <span>Editorial Governance</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-black font-display text-slate-900 dark:text-white">Editorial Desk & Queue</h1>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <span>{{ $totalDrafts }} drafts in progress</span>
                </span>
            </div>
        </div>

        <!-- 1. Grouped & Restructured CTA Buttons: Primary Operational + Secondary + Dropdown -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Primary Action Button: Draft New Piece -->
            <a href="{{ url('articles/create') }}" 
               class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white text-xs font-black shadow-lg shadow-primary/25 transition-all flex items-center gap-1.5 transform hover:-translate-y-0.5">
                <span class="material-symbols-outlined text-base font-bold">add</span>
                <span>+ Draft New Piece</span>
            </a>

            <!-- Secondary Operational Action Button: Assign to Journalist -->
            <button type="button" @click="openCreateAssignment()"
                    class="px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-900 dark:bg-white/10 dark:hover:bg-white/15 text-white text-xs font-bold shadow-md shadow-black/10 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">assignment_add</span>
                <span>+ Assign to Journalist</span>
            </button>

            <!-- Dropdown Menu: System Configuration & Beats ("More Options") -->
            <div class="relative" @click.outside="moreOptionsOpen = false">
                <button type="button" @click="moreOptionsOpen = !moreOptionsOpen"
                        class="px-3.5 py-2.5 rounded-2xl bg-white dark:bg-surface-card hover:bg-slate-100 dark:hover:bg-white/10 text-xs font-bold border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 transition-all flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-base">tune</span>
                    <span>More Options</span>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="moreOptionsOpen ? 'rotate-180' : ''">expand_more</span>
                </button>

                <div x-cloak x-show="moreOptionsOpen"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     class="absolute right-0 mt-2 w-60 p-2 rounded-2xl bg-white dark:bg-[#151922] shadow-2xl border border-slate-200 dark:border-white/10 z-50 text-left space-y-1">
                    
                    <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">
                        Editorial Settings & Beats
                    </div>

                    <button type="button" @click="categoryModalOpen = true; moreOptionsOpen = false"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                        <span class="material-symbols-outlined text-base text-primary">category</span>
                        <span>Manage Categories & Beats</span>
                    </button>

                    <a href="{{ url('about/edit') }}" @click="moreOptionsOpen = false"
                       class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                        <span class="material-symbols-outlined text-base text-sky-500">info</span>
                        <span>Edit About Us Page</span>
                    </a>

                    @if(auth()->isManagement())
                        <div class="border-t border-slate-100 dark:border-white/5 my-1"></div>
                        <a href="{{ url('dashboard/management') }}" @click="moreOptionsOpen = false"
                           class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                            <span class="material-symbols-outlined text-base text-secondary-light">shield_person</span>
                            <span>Executive Governance</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Alert -->
    @if(!empty($notice))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <span>
                    @if($notice === 'headline_promoted') Story successfully promoted as the active cover Headline on the homepage hero! (1 headline active at a time).
                    @elseif($notice === 'curated_promoted') Story successfully promoted as one of the 4 Curated School Stories on the homepage! (Up to 4 stories curated at a time).
                    @elseif($notice === 'curated_demoted') Story removed from the active Curated School Stories selection.
                    @elseif($notice === 'assignment_created') New post assignment dispatched to journalist with instant notification!
                    @elseif($notice === 'assignment_updated') Assignment details, topic briefing, and schedules updated successfully!
                    @elseif($notice === 'assignment_deleted') Assignment record removed successfully.
                    @elseif($notice === 'assignment_turned_in') Assignment turned in by journalist and sent for editorial review.
                    @elseif($notice === 'article_approved') Article successfully approved and published live!
                    @elseif($notice === 'article_rejected') Article rejected with editorial feedback returned to writer.
                    @elseif($notice === 'category_created') New editorial beat category created successfully!
                    @elseif($notice === 'category_updated') Editorial category beat updated successfully!
                    @elseif($notice === 'category_deleted')
                        Category beat deleted and stories successfully moved to <strong>{{ $targetName ?? 'Target Category' }}</strong>!
                    @else Action completed successfully.
                    @endif
                </span>
            </div>
            @if(in_array($notice, ['category_created', 'category_updated', 'category_deleted']))
                <button type="button" @click="heroPopupOpen = true" class="text-xs font-bold underline hover:text-emerald-300 flex items-center gap-1 shrink-0">
                    <span class="material-symbols-outlined text-sm">visibility</span>
                    <span>View Hero Popup Summary</span>
                </button>
            @endif
        </div>
    @endif

    @if(!empty($error))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <span>
                @if($error === 'article_not_published') Only published stories can be promoted as the homepage cover headline.
                @elseif($error === 'missing_assignment_fields') Please fill in all required assignment fields: Journalist, Category, Topic, and Deadlines.
                @elseif($error === 'assignment_not_found') The requested assignment was not found.
                @elseif($error === 'invalid_target_category') Cannot delete beat: Please choose a valid destination category to reassign stories to.
                @elseif($error === 'category_not_found') The requested category was not found.
                @elseif($error === 'category_name_required') Category beat name is required and cannot be empty.
                @elseif($error === 'article_not_found') Article not found.
                @else An error occurred while processing your request.
                @endif
            </span>
        </div>
    @endif

    <!-- ACTIVE HOMEPAGE RUNNING ANNOUNCEMENT CONTROL -->
    <div class="p-5 sm:p-6 rounded-3xl glass-panel border border-primary/30 bg-gradient-to-r from-primary/5 via-indigo-500/5 to-transparent shadow-sm">
        <form method="POST" action="{{ url('dashboard/editorial/announcement') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <input type="hidden" name="redirect_to" value="dashboard/editorial?notice=announcement_updated">
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-2xl bg-primary/20 text-primary flex items-center justify-center shrink-0 mt-0.5 shadow-sm">
                    <span class="material-symbols-outlined text-xl">campaign</span>
                </span>
                <div>
                    <h4 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Active Homepage Running Announcement</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20 uppercase">Editorial & Management</span>
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">This official announcement appears on the homepage running marquee ticker alongside the 5 latest updated stories.</p>
                </div>
            </div>
            <div class="flex-1 max-w-xl flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <input type="text" name="announcement_text" value="{{ $announcement ?? '' }}" 
                       placeholder="Write important announcement here..."
                       class="flex-1 px-4 py-2.5 rounded-xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
                <button type="submit" 
                        class="px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20 transition-all whitespace-nowrap flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">save</span>
                    <span>Update Ticker</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 2 & 4. TRANSFORMED UNIFIED TAB UI: MOBILE SCROLLABLE PILLS & DESKTOP TAB BAR -->
    
    <!-- 2 & 4. TRANSFORMED UNIFIED TAB UI: MOBILE COLLAPSIBLE CONTROLS & DESKTOP TAB BAR -->
    
    <!-- Mobile Collapsible Sections Helper Bar (< 768px): Replaces cut-off horizontal pill tabs -->
    <div class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-100/90 dark:bg-white/[0.04] border border-slate-200 dark:border-white/10 md:hidden text-xs">
        <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-bold px-1 min-w-0">
            <span class="material-symbols-outlined text-sm text-primary shrink-0">view_agenda</span>
            <span class="truncate">Editorial Sections</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <button type="button" 
                    @click="collapseAllMobile()" 
                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 font-bold text-[11px] border border-slate-200/80 dark:border-white/10 transition-all flex items-center gap-1 shadow-xs">
                <span class="material-symbols-outlined text-xs">unfold_less</span>
                <span>Collapse All</span>
            </button>
            <button type="button" 
                    @click="expandAllMobile()" 
                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 font-bold text-[11px] border border-slate-200/80 dark:border-white/10 transition-all flex items-center gap-1 shadow-xs">
                <span class="material-symbols-outlined text-xs">unfold_more</span>
                <span>Expand All</span>
            </button>
        </div>
    </div>

    <!-- Desktop View (≥ 768px): Full-Width Tab Bar Spanning Over Content Area -->
    <div class="hidden md:grid md:grid-cols-3 gap-3 p-2 rounded-3xl bg-slate-100/80 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10 shadow-sm">
        <!-- Desktop Tab 1: Assignments -->
        <button type="button" @click="setTab('assignments')"
                class="p-4 rounded-2xl flex items-center justify-between text-left transition-all group"
                :class="activeTab === 'assignments' 
                    ? 'bg-white dark:bg-[#1a202c] shadow-lg border border-primary/40 ring-2 ring-primary/20' 
                    : 'hover:bg-white/60 dark:hover:bg-white/5 border border-transparent'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                      :class="activeTab === 'assignments' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-primary/10 text-primary'">
                    <span class="material-symbols-outlined text-xl">assignment_ind</span>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">Assignments</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Story briefs & deadlines</div>
                </div>
            </div>
            <span class="px-3 py-1 rounded-xl text-xs font-black shrink-0 transition-colors"
                  :class="activeTab === 'assignments' ? 'bg-primary text-white shadow-sm' : 'bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ $totalAssignments }}
            </span>
        </button>

        <!-- Desktop Tab 2: Submissions -->
        <button type="button" @click="setTab('queue')"
                class="p-4 rounded-2xl flex items-center justify-between text-left transition-all group"
                :class="activeTab === 'queue' 
                    ? 'bg-white dark:bg-[#1a202c] shadow-lg border border-amber-500/40 ring-2 ring-amber-500/20' 
                    : 'hover:bg-white/60 dark:hover:bg-white/5 border border-transparent'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                      :class="activeTab === 'queue' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25' : 'bg-amber-500/10 text-amber-500'">
                    <span class="material-symbols-outlined text-xl">rate_review</span>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">Submissions</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Moderation review queue</div>
                </div>
            </div>
            <span class="px-3 py-1 rounded-xl text-xs font-black shrink-0 transition-colors"
                  :class="activeTab === 'queue' ? 'bg-amber-500 text-white shadow-sm' : ({{ count($pendingArticles) }} > 0 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold' : 'bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-slate-300')">
                {{ count($pendingArticles) }} Pending
            </span>
        </button>

        <!-- Desktop Tab 3: Published -->
        <button type="button" @click="setTab('dispatches')"
                class="p-4 rounded-2xl flex items-center justify-between text-left transition-all group"
                :class="activeTab === 'dispatches' 
                    ? 'bg-white dark:bg-[#1a202c] shadow-lg border border-emerald-500/40 ring-2 ring-emerald-500/20' 
                    : 'hover:bg-white/60 dark:hover:bg-white/5 border border-transparent'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                      :class="activeTab === 'dispatches' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'">
                    <span class="material-symbols-outlined text-xl">library_books</span>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">Published</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Live dispatches & spotlights</div>
                </div>
            </div>
            <span class="px-3 py-1 rounded-xl text-xs font-black shrink-0 transition-colors"
                  :class="activeTab === 'dispatches' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ $totalPublished }}
            </span>
        </button>
    </div>

    <!-- 3, 4, 5. CONTENT AREA WRAPPER WITH VERTICALLY STACKED COLLAPSIBLE SECTIONS -->
    <div @touchstart="handleTouchStart($event)" @touchend="handleTouchEnd($event)" class="space-y-4 sm:space-y-6">

    <!-- SECTION 1: ASSIGNMENTS -->
    <div id="assignments-section" 
         class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 overflow-hidden transition-all duration-300 shadow-sm"
         :class="{
             'ring-2 ring-primary/40 border-primary/40 shadow-md': isMobile && mobileSections.assignments,
             'md:hidden': activeTab !== 'assignments'
         }">
        
        <!-- Mobile Collapsible Header Button (< 768px) -->
        <button type="button" 
                @click="toggleMobileSection('assignments')"
                class="w-full p-4 sm:p-5 flex items-center justify-between text-left transition-colors md:hidden select-none focus:outline-none"
                :class="mobileSections.assignments 
                    ? 'bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-b border-slate-200/80 dark:border-white/10' 
                    : 'bg-white/60 dark:bg-surface-card/60 hover:bg-slate-100/80 dark:hover:bg-white/5'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md transition-transform"
                      :class="mobileSections.assignments ? 'bg-primary text-white shadow-primary/30 scale-105' : 'bg-primary/10 text-primary'">
                    <span class="material-symbols-outlined text-xl">assignment_ind</span>
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white truncate">Assignments</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 transition-colors"
                              :class="mobileSections.assignments ? 'bg-primary text-white shadow-sm' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                            {{ $totalAssignments }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Story briefs, deadlines & dispatch</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-[11px] font-bold text-primary hidden xs:inline" x-text="mobileSections.assignments ? 'Collapse' : 'Expand'"></span>
                <span class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 transition-transform duration-200"
                      :class="mobileSections.assignments ? 'rotate-180 bg-primary/20 text-primary' : ''">
                    <span class="material-symbols-outlined text-base">expand_more</span>
                </span>
            </div>
        </button>

        <!-- Section Body Content -->
        <div id="assignments" 
             x-show="!isMobile || mobileSections.assignments" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0"
             class="p-4 sm:p-6 md:p-8 space-y-6">
        <template x-if="loadedTabs['assignments']">
            <div class="space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary to-indigo-500 text-white flex items-center justify-center shadow-lg shadow-primary/20">
                            <span class="material-symbols-outlined text-xl">assignment_ind</span>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Assignments</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20" x-text="allAssignments.length + ' Total'"></span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Dispatch story briefs to journalists, monitor deadlines, and track submissions</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <!-- Search -->
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                            <input type="text" x-model="assignmentSearch" placeholder="Search topic or journalist..."
                                   class="pl-9 pr-3 py-2 rounded-xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 w-44 sm:w-52">
                        </div>

                        <!-- View Toggle: Cards vs Table (hidden on small mobile, where cards are always used) -->
                        <div class="hidden sm:inline-flex items-center p-1 rounded-xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs">
                            <button type="button" @click="assignmentView = 'grid'" 
                                    :class="assignmentView === 'grid' ? 'bg-white dark:bg-surface-card text-primary shadow-sm font-bold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1" title="Responsive Card Grid View">
                                <span class="material-symbols-outlined text-sm">grid_view</span>
                                <span>Cards</span>
                            </button>
                            <button type="button" @click="assignmentView = 'table'" 
                                    :class="assignmentView === 'table' ? 'bg-white dark:bg-surface-card text-primary shadow-sm font-bold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1" title="Table View">
                                <span class="material-symbols-outlined text-sm">table_rows</span>
                                <span>Table</span>
                            </button>
                        </div>

                        <!-- Create Assignment Button -->
                        <button type="button" @click="openCreateAssignment()"
                                class="px-4 py-2 rounded-xl bg-gradient-to-r from-primary to-pink-500 hover:from-primary-hover hover:to-pink-600 text-white text-xs font-bold shadow-md shadow-primary/20 transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">add_task</span>
                            <span>Assign Story</span>
                        </button>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200 dark:border-white/10 text-xs font-bold no-scrollbar">
                    <button type="button" @click="assignmentFilter = 'all'"
                            :class="assignmentFilter === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shrink-0">
                        <span>All</span>
                        <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-black/20 text-white" x-text="allAssignments.length"></span>
                    </button>
                    <button type="button" @click="assignmentFilter = 'pending'"
                            :class="assignmentFilter === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>In Progress</span>
                        <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-black/20 text-white" x-text="allAssignments.filter(a => a.effective_status === 'pending').length"></span>
                    </button>
                    <button type="button" @click="assignmentFilter = 'turned_in'"
                            :class="assignmentFilter === 'turned_in' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                        <span>Turned In</span>
                        <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-black/20 text-white" x-text="allAssignments.filter(a => a.effective_status === 'turned_in').length"></span>
                    </button>
                    <button type="button" @click="assignmentFilter = 'approved'"
                            :class="assignmentFilter === 'approved' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Published</span>
                        <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-black/20 text-white" x-text="allAssignments.filter(a => a.effective_status === 'approved').length"></span>
                    </button>
                    <button type="button" @click="assignmentFilter = 'expired'"
                            :class="assignmentFilter === 'expired' ? 'bg-rose-500 text-white shadow-sm' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        <span>Expired</span>
                        <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-black/20 text-white" x-text="allAssignments.filter(a => a.effective_status === 'expired').length"></span>
                    </button>
                </div>

                <!-- Empty State -->
                <template x-if="filteredAssignments().length === 0">
                    <div class="text-center py-12 text-slate-400">
                        <span class="material-symbols-outlined text-4xl mb-2 text-slate-500">assignment_late</span>
                        <p class="text-sm font-semibold">No assignments matching current filter.</p>
                        <button type="button" @click="openCreateAssignment()" class="mt-3 text-xs font-bold text-primary hover:underline">
                            + Dispatch a new assignment now
                        </button>
                    </div>
                </template>

                <template x-if="filteredAssignments().length > 0">
                    <div>
                        <!-- RESPONSIVE CARD GRID VIEW (1 col mobile, 2 col tablet, 3 col desktop) -->
                        <div x-show="assignmentView === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                            <template x-for="asg in filteredAssignments()" :key="asg.id">
                                <div :id="'assignment-card-' + asg.id" 
                                     class="p-5 rounded-2xl bg-white dark:bg-surface-card border border-slate-200 dark:border-white/5 hover:border-primary/40 dark:hover:border-primary/40 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
                                    <div class="space-y-3">
                                        <!-- Top Row: Category Beat Badge + Status Pill -->
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold text-white shadow-sm inline-flex items-center gap-1 shrink-0"
                                                  :style="'background-color: ' + (asg.category_color || '#ec4899')">
                                                <span class="material-symbols-outlined text-xs" x-text="asg.category_icon || 'tag'"></span>
                                                <span x-text="asg.category_name"></span>
                                            </span>

                                            <div>
                                                <template x-if="asg.effective_status === 'pending'">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        <span>In Progress</span>
                                                    </span>
                                                </template>
                                                <template x-if="asg.effective_status === 'turned_in'">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 inline-flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-xs">done</span>
                                                        <span>Turned In</span>
                                                    </span>
                                                </template>
                                                <template x-if="asg.effective_status === 'approved'">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 inline-flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-xs">verified</span>
                                                        <span>Published</span>
                                                    </span>
                                                </template>
                                                <template x-if="asg.effective_status === 'expired'">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/20 inline-flex items-center gap-1"
                                                          title="Deadline has passed, submission remains open">
                                                        <span class="material-symbols-outlined text-xs">event_busy</span>
                                                        <span>Expired</span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Topic & Briefing -->
                                        <div>
                                            <h4 class="font-bold text-slate-900 dark:text-white text-sm line-clamp-2 leading-snug group-hover:text-primary transition-colors" x-text="asg.topic"></h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 line-clamp-2 leading-relaxed" x-text="asg.description || 'No detailed instructions provided.'"></p>
                                        </div>

                                        <!-- Linked Article Pill (if turned in / published) -->
                                        <template x-if="asg.article_title">
                                            <div class="p-2 rounded-xl bg-primary/5 border border-primary/15 flex items-center gap-1.5 text-xs text-primary font-bold">
                                                <span class="material-symbols-outlined text-sm shrink-0">description</span>
                                                <a :href="'{{ url('articles/') }}/' + asg.article_slug" target="_blank" class="hover:underline truncate" x-text="asg.article_title"></a>
                                            </div>
                                        </template>

                                        <!-- Journalist Info -->
                                        <div class="pt-2 border-t border-slate-100 dark:border-white/5 flex items-center gap-2.5">
                                            <img :src="asg.journalist_avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'" 
                                                 :alt="asg.journalist_name" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary/20 shrink-0">
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="asg.journalist_name"></div>
                                                <div class="text-[10px] text-slate-400 truncate" x-text="'Assigned by: ' + (asg.editor_name || 'Desk')"></div>
                                            </div>
                                        </div>

                                        <!-- Schedule & Deadlines Grid -->
                                        <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/5 text-[11px]">
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-semibold uppercase flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs text-amber-500">timer</span>
                                                    <span>Deadline</span>
                                                </div>
                                                <div class="font-bold truncate mt-0.5" 
                                                     :class="asg.is_overdue && asg.status === 'pending' ? 'text-rose-500 font-black' : 'text-slate-700 dark:text-slate-200'"
                                                     x-text="formatDateStr(asg.submission_deadline)"></div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-semibold uppercase flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs text-indigo-400">calendar_month</span>
                                                    <span>Publish</span>
                                                </div>
                                                <div class="font-bold text-slate-700 dark:text-slate-200 truncate mt-0.5" x-text="formatDateStr(asg.publishing_schedule)"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Bottom Actions -->
                                    <div class="pt-3 mt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between gap-2">
                                        <div class="text-[10px] text-slate-400 truncate">
                                            <template x-if="asg.turned_in_at">
                                                <span>Turned in: <strong class="text-slate-600 dark:text-slate-300" x-text="formatDateStr(asg.turned_in_at)"></strong></span>
                                            </template>
                                            <template x-if="!asg.turned_in_at">
                                                <span>Assigned: <span x-text="formatDateStr(asg.created_at)"></span></span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <template x-if="asg.article_id">
                                                <a :href="'{{ url('articles/') }}/' + asg.article_id + '/edit'" 
                                                   class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 text-xs font-bold transition-all flex items-center gap-1"
                                                   title="Review & Edit Submission">
                                                    <span class="material-symbols-outlined text-xs">rate_review</span>
                                                    <span>Review</span>
                                                </a>
                                            </template>
                                            <button type="button" @click="openEditAssignment(asg)"
                                                    class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 transition-all"
                                                    title="Edit Assignment Details">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                            </button>
                                            <button type="button" @click="openDeleteAssignment(asg)"
                                                    class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 transition-all"
                                                    title="Delete Assignment">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- DESKTOP TABLE VIEW FALLBACK -->
                        <div x-show="assignmentView === 'table'" class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-bold">
                                        <th class="py-3 px-4">Topic & Briefing</th>
                                        <th class="py-3 px-4">Assigned Journalist</th>
                                        <th class="py-3 px-4">Category Beat</th>
                                        <th class="py-3 px-4">Deadlines & Schedule</th>
                                        <th class="py-3 px-4">Status & Turned In</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-white/5 text-slate-700 dark:text-slate-300">
                                    <template x-for="asg in filteredAssignments()" :key="asg.id">
                                        <tr :id="'assignment-row-' + asg.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                            <!-- Topic & Briefing -->
                                            <td class="py-4 px-4 max-w-xs">
                                                <div class="font-bold text-slate-900 dark:text-white text-sm" x-text="asg.topic"></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2" x-text="asg.description || 'No detailed instructions provided.'"></div>
                                                <template x-if="asg.article_title">
                                                    <div class="mt-2 flex items-center gap-1.5 text-[11px] text-primary font-bold">
                                                        <span class="material-symbols-outlined text-xs">description</span>
                                                        <span>Article: </span>
                                                        <a :href="'{{ url('articles/') }}/' + asg.article_slug" target="_blank" class="hover:underline" x-text="asg.article_title"></a>
                                                    </div>
                                                </template>
                                            </td>

                                            <!-- Assigned Journalist -->
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2.5">
                                                    <img :src="asg.journalist_avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'" 
                                                         :alt="asg.journalist_name" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary/20">
                                                    <div>
                                                        <div class="font-bold text-slate-900 dark:text-white" x-text="asg.journalist_name"></div>
                                                        <div class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'Assigned by: ' + (asg.editor_name || 'Desk')"></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Category Beat -->
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold text-white shadow-sm inline-flex items-center gap-1"
                                                      :style="'background-color: ' + (asg.category_color || '#ec4899')">
                                                    <span class="material-symbols-outlined text-xs" x-text="asg.category_icon || 'tag'"></span>
                                                    <span x-text="asg.category_name"></span>
                                                </span>
                                            </td>

                                            <!-- Deadlines & Schedule -->
                                            <td class="py-4 px-4 whitespace-nowrap space-y-1">
                                                <div class="flex items-center gap-1.5 text-[11px]">
                                                    <span class="material-symbols-outlined text-xs text-amber-500">timer</span>
                                                    <span class="text-slate-500 dark:text-slate-400">Deadline:</span>
                                                    <strong :class="asg.is_overdue && asg.status === 'pending' ? 'text-rose-500 font-black' : 'text-slate-800 dark:text-slate-200'"
                                                            x-text="formatDateStr(asg.submission_deadline)"></strong>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[11px]">
                                                    <span class="material-symbols-outlined text-xs text-indigo-400">calendar_month</span>
                                                    <span class="text-slate-500 dark:text-slate-400">Publish:</span>
                                                    <span class="text-slate-700 dark:text-slate-300" x-text="formatDateStr(asg.publishing_schedule)"></span>
                                                </div>
                                            </td>

                                            <!-- Status & Turned In -->
                                            <td class="py-4 px-4 whitespace-nowrap space-y-1.5">
                                                <template x-if="asg.effective_status === 'pending'">
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        <span>In Progress</span>
                                                    </span>
                                                </template>
                                                <template x-if="asg.effective_status === 'turned_in'">
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 inline-flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-xs">done</span>
                                                        <span>Turned In</span>
                                                    </span>
                                                </template>
                                                <template x-if="asg.effective_status === 'approved'">
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 inline-flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-xs">verified</span>
                                                        <span>Published</span>
                                                    </span>
                                                </template>
                                                <template x-if="asg.effective_status === 'expired'">
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/20 inline-flex items-center gap-1"
                                                          title="Deadline has passed, submission remains open">
                                                        <span class="material-symbols-outlined text-xs">event_busy</span>
                                                        <span>Expired</span>
                                                    </span>
                                                </template>

                                                <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                                    <template x-if="asg.turned_in_at">
                                                        <span>Turned in: <strong class="text-slate-700 dark:text-slate-300" x-text="formatDateStr(asg.turned_in_at)"></strong></span>
                                                    </template>
                                                    <template x-if="!asg.turned_in_at">
                                                        <span class="italic text-slate-400 dark:text-slate-500">Assigned: <span x-text="formatDateStr(asg.created_at)"></span></span>
                                                    </template>
                                                </div>
                                            </td>

                                            <!-- Actions -->
                                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <template x-if="asg.article_id">
                                                        <a :href="'{{ url('articles/') }}/' + asg.article_id + '/edit'" 
                                                           class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 transition-all"
                                                           title="Review & Edit Submission">
                                                            <span class="material-symbols-outlined text-sm">rate_review</span>
                                                        </a>
                                                    </template>
                                                    <button type="button" @click="openEditAssignment(asg)"
                                                            class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 transition-all"
                                                            title="Edit Assignment Details">
                                                        <span class="material-symbols-outlined text-sm">edit</span>
                                                    </button>
                                                    <button type="button" @click="openDeleteAssignment(asg)"
                                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 transition-all"
                                                            title="Delete Assignment">
                                                        <span class="material-symbols-outlined text-sm">delete</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

    <!-- SECTION 2: SUBMISSIONS -->
    <div id="queue-section" 
         class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 overflow-hidden transition-all duration-300 shadow-sm"
         :class="{
             'ring-2 ring-amber-500/40 border-amber-500/40 shadow-md': isMobile && mobileSections.queue,
             'md:hidden': activeTab !== 'queue'
         }">
        
        <!-- Mobile Collapsible Header Button (< 768px) -->
        <button type="button" 
                @click="toggleMobileSection('queue')"
                class="w-full p-4 sm:p-5 flex items-center justify-between text-left transition-colors md:hidden select-none focus:outline-none"
                :class="mobileSections.queue 
                    ? 'bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border-b border-slate-200/80 dark:border-white/10' 
                    : 'bg-white/60 dark:bg-surface-card/60 hover:bg-slate-100/80 dark:hover:bg-white/5'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md transition-transform"
                      :class="mobileSections.queue ? 'bg-amber-500 text-white shadow-amber-500/30 scale-105' : 'bg-amber-500/10 text-amber-500'">
                    <span class="material-symbols-outlined text-xl">rate_review</span>
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white truncate">Submissions</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 transition-colors"
                              :class="mobileSections.queue ? 'bg-amber-500 text-white shadow-sm' : ({{ count($pendingArticles) }} > 0 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300')">
                            {{ count($pendingArticles) }} Pending
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Moderation review queue & approval</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-[11px] font-bold text-amber-500 hidden xs:inline" x-text="mobileSections.queue ? 'Collapse' : 'Expand'"></span>
                <span class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 transition-transform duration-200"
                      :class="mobileSections.queue ? 'rotate-180 bg-amber-500/20 text-amber-600 dark:text-amber-400' : ''">
                    <span class="material-symbols-outlined text-base">expand_more</span>
                </span>
            </div>
        </button>

        <!-- Section Body Content -->
        <div id="queue" 
             x-show="!isMobile || mobileSections.queue" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0"
             class="p-4 sm:p-6 md:p-8 space-y-6">
        <template x-if="loadedTabs['queue']">
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/20">
                            <span class="material-symbols-outlined text-xl">rate_review</span>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Submissions</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                    {{ count($pendingArticles) }} Pending
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Review and moderate submitted student stories awaiting editorial approval</p>
                        </div>
                    </div>
                </div>

                @if(empty($pendingArticles))
                    <div class="text-center py-12 text-slate-400">
                        <span class="material-symbols-outlined text-4xl mb-2 text-slate-500">task_alt</span>
                        <p class="text-sm font-semibold">Editorial queue is all clear! No pending submissions.</p>
                    </div>
                @else
                    <!-- Responsive Grid: 1 col on mobile, 2 cols on lg/desktop -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
                        @foreach($pendingArticles as $art)
                            <div class="p-5 rounded-2xl bg-white dark:bg-surface-card border border-slate-200 dark:border-white/5 hover:border-amber-500/40 dark:hover:border-amber-500/40 shadow-sm hover:shadow-md transition-all flex flex-col justify-between gap-4">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <img src="{{ $art['author_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                                                 alt="{{ $art['author_name'] }}" class="w-9 h-9 rounded-xl object-cover ring-2 ring-primary/30 shrink-0">
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $art['author_name'] }}</div>
                                                <div class="text-[10px] text-slate-400">{{ date('M d, Y · H:i', strtotime($art['created_at'])) }}</div>
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider text-white shrink-0"
                                              style="background-color: {{ $art['category_color'] }};">
                                            {{ $art['category_name'] }}
                                        </span>
                                    </div>

                                    <div>
                                        <h4 class="text-base font-bold text-slate-900 dark:text-white line-clamp-2 leading-snug">{{ $art['title'] }}</h4>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 mt-1 leading-relaxed">{{ $art['excerpt'] }}</p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-white/5">
                                    <a href="{{ url('articles/' . $art['id'] . '/edit') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-xs font-bold text-slate-700 dark:text-slate-300 transition-all flex items-center gap-1 border border-slate-200 dark:border-transparent">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        <span>Inspect</span>
                                    </a>

                                    <form method="POST" action="{{ url('dashboard/editorial/articles/' . $art['id'] . '/approve') }}" class="inline">
                                        <button type="submit" class="px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold shadow-md shadow-emerald-500/20 transition-all flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                            <span>Approve</span>
                                        </button>
                                    </form>

                                    <button type="button" @click="openReject({{ $art['id'] }}, '{{ addslashes($art['title']) }}')"
                                            class="px-4 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-300 text-xs font-bold border border-rose-500/30 transition-all flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                        <span>Reject</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </template>
    </div>
</div>

    <!-- SECTION 3: PUBLISHED -->
    <div id="dispatches-section" 
         class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 overflow-hidden transition-all duration-300 shadow-sm"
         :class="{
             'ring-2 ring-emerald-500/40 border-emerald-500/40 shadow-md': isMobile && mobileSections.dispatches,
             'md:hidden': activeTab !== 'dispatches'
         }">
        
        <!-- Mobile Collapsible Header Button (< 768px) -->
        <button type="button" 
                @click="toggleMobileSection('dispatches')"
                class="w-full p-4 sm:p-5 flex items-center justify-between text-left transition-colors md:hidden select-none focus:outline-none"
                :class="mobileSections.dispatches 
                    ? 'bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent border-b border-slate-200/80 dark:border-white/10' 
                    : 'bg-white/60 dark:bg-surface-card/60 hover:bg-slate-100/80 dark:hover:bg-white/5'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md transition-transform"
                      :class="mobileSections.dispatches ? 'bg-emerald-600 text-white shadow-emerald-600/30 scale-105' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'">
                    <span class="material-symbols-outlined text-xl">library_books</span>
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white truncate">Published</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 transition-colors"
                              :class="mobileSections.dispatches ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                            {{ $totalPublished }} Live
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Live dispatches, hero headline & curated stories</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hidden xs:inline" x-text="mobileSections.dispatches ? 'Collapse' : 'Expand'"></span>
                <span class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 transition-transform duration-200"
                      :class="mobileSections.dispatches ? 'rotate-180 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : ''">
                    <span class="material-symbols-outlined text-base">expand_more</span>
                </span>
            </div>
        </button>

        <!-- Section Body Content -->
        <div id="dispatches" 
             x-show="!isMobile || mobileSections.dispatches" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0"
             class="p-4 sm:p-6 md:p-8 space-y-6">
        <template x-if="loadedTabs['dispatches']">
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-lg shadow-emerald-600/20 shrink-0">
                            <span class="material-symbols-outlined text-xl">library_books</span>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Published</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                    {{ $totalPublished }} Live
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Promote any published story as the homepage cover headline (1 at a time), or feature up to 4 stories in Curated School Stories on frontpage.</p>
                            <div class="flex items-center gap-3 pt-1.5 text-[11px] text-slate-500 dark:text-slate-400 font-medium flex-wrap">
                                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> <span class="material-symbols-outlined text-[14px] text-amber-500">star</span> Headline Hero</span>
                                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> <span class="material-symbols-outlined text-[14px] text-emerald-500">auto_awesome</span> Curated Stories (Max 4)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative w-full sm:w-72 shrink-0">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
                        <input type="text" x-model="dispatchSearch" placeholder="Filter story title or writer..." 
                               class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                </div>

                <!-- MOBILE VIEW (< 768px): RESPONSIVE CARDS -->
                <div class="block md:hidden space-y-3.5">
                    @foreach($publishedArticles as $pub)
                        @php
                            $isCurrentHeadline = ((int)$pub['id'] === (int)$headlineArticleId || !empty($pub['is_headline']));
                            $isCurrentCurated = (in_array((int)$pub['id'], $curatedArticleIds ?? [], true) || !empty($pub['is_curated']));
                        @endphp
                        <div x-show="matchesDispatchSearch('{{ addslashes($pub['title']) }}', '{{ addslashes($pub['author_name']) }}')"
                             class="p-4 rounded-2xl bg-white dark:bg-surface-card border border-slate-200 dark:border-white/5 shadow-sm space-y-3 {{ $isCurrentHeadline ? 'border-amber-500/40 ring-1 ring-amber-500/20' : ($isCurrentCurated ? 'border-emerald-500/40 ring-1 ring-emerald-500/20' : '') }}">
                            
                            <!-- Card Header: Category & Views -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold text-white shadow-sm flex items-center gap-1"
                                      style="background-color: {{ $pub['category_color'] }};">
                                    <span class="material-symbols-outlined text-[11px]">{{ $pub['category_icon'] ?? 'tag' }}</span>
                                    <span>{{ $pub['category_name'] }}</span>
                                </span>
                                <div class="flex items-center gap-1 text-[11px] text-slate-400 font-mono">
                                    <span class="material-symbols-outlined text-xs">visibility</span>
                                    <span>{{ number_format($pub['views_count']) }}</span>
                                </div>
                            </div>

                            <!-- Title & Status -->
                            <div>
                                <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                    @if($isCurrentHeadline)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">stars</span>
                                            <span>Headline Hero</span>
                                        </span>
                                    @endif
                                    @if($isCurrentCurated)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">auto_awesome</span>
                                            <span>Curated Story</span>
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ url('articles/' . $pub['slug']) }}" class="text-sm font-bold text-slate-900 dark:text-white hover:text-primary transition-colors line-clamp-2">
                                    {{ $pub['title'] }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                                    <span>By <strong class="text-slate-700 dark:text-slate-300">{{ $pub['author_name'] }}</strong></span>
                                    <span>{{ date('M d, Y', strtotime($pub['published_at'])) }}</span>
                                </div>
                            </div>

                            <!-- Mobile Actions & Spotlight Controls -->
                            <div class="pt-2.5 border-t border-slate-100 dark:border-white/5 flex items-center justify-between gap-2">
                                <!-- Spotlight Buttons -->
                                <div class="flex items-center gap-1.5">
                                    <!-- Headline Button -->
                                    @if($isCurrentHeadline)
                                        <button type="button" @click="openHeadlinePreview({{ json_encode($pub) }})" 
                                                class="px-2.5 py-1 rounded-xl bg-amber-500 text-white text-[11px] font-bold shadow-sm shadow-amber-500/30 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">stars</span>
                                            <span>Preview Hero</span>
                                        </button>
                                    @else
                                        <form method="POST" action="{{ url('dashboard/editorial/articles/' . $pub['id'] . '/headline') }}" class="inline m-0 p-0">
                                            <button type="submit" 
                                                    class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-amber-500 text-slate-600 dark:text-slate-300 hover:text-white text-[11px] font-bold border border-slate-200 dark:border-white/10 shadow-sm flex items-center gap-1 transition-all">
                                                <span class="material-symbols-outlined text-xs text-amber-500">star</span>
                                                <span>Headline</span>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Curated Button -->
                                    @if($isCurrentCurated)
                                        <form method="POST" action="{{ url('dashboard/editorial/articles/' . $pub['id'] . '/demote-curated') }}" class="inline m-0 p-0"
                                              onsubmit="return confirm('Remove this article from Curated stories on homepage?');">
                                            <button type="submit" 
                                                    class="px-2.5 py-1 rounded-xl bg-emerald-500 text-white text-[11px] font-bold shadow-sm flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">auto_awesome</span>
                                                <span>Curated</span>
                                                <span class="material-symbols-outlined text-xs ml-0.5 text-white/80">close</span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ url('dashboard/editorial/articles/' . $pub['id'] . '/curated') }}" class="inline m-0 p-0">
                                            <button type="submit" 
                                                    class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-emerald-600 text-slate-600 dark:text-slate-300 hover:text-white text-[11px] font-bold border border-slate-200 dark:border-white/10 shadow-sm flex items-center gap-1 transition-all">
                                                <span class="material-symbols-outlined text-xs text-emerald-500">auto_awesome</span>
                                                <span>Curate</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Live View & Edit -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a href="{{ url('articles/' . $pub['slug']) }}" 
                                       class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 transition-colors"
                                       title="View Live">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </a>
                                    <a href="{{ url('articles/' . $pub['id'] . '/edit') }}" 
                                       class="px-3 py-1 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary font-bold text-xs transition-colors">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- DESKTOP VIEW (≥ 768px): FULL DIRECTORY TABLE -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-white/10">
                            <tr>
                                <th class="py-3 px-3 whitespace-nowrap text-center w-28">Spotlight</th>
                                <th class="py-3 px-4">Title</th>
                                <th class="py-3 px-4">Category Beat</th>
                                <th class="py-3 px-4">Author</th>
                                <th class="py-3 px-4">Views</th>
                                <th class="py-3 px-4">Published Date</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-white/5 text-slate-700 dark:text-slate-300">
                            @foreach($publishedArticles as $pub)
                                @php
                                    $isCurrentHeadline = ((int)$pub['id'] === (int)$headlineArticleId || !empty($pub['is_headline']));
                                    $isCurrentCurated = (in_array((int)$pub['id'], $curatedArticleIds ?? [], true) || !empty($pub['is_curated']));
                                @endphp
                                <tr x-show="matchesDispatchSearch('{{ addslashes($pub['title']) }}', '{{ addslashes($pub['author_name']) }}')"
                                    class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors {{ $isCurrentHeadline ? 'bg-amber-500/[0.04]' : ($isCurrentCurated ? 'bg-emerald-500/[0.03]' : '') }}">
                                    
                                    <!-- Spotlight Controls Column: Compact Icon Buttons with Hover Legend -->
                                    <td class="py-3 px-3 whitespace-nowrap w-28">
                                        <div class="flex items-center justify-center gap-1.5">
                                            
                                            <!-- 1. Cover Headline Control -->
                                            @if($isCurrentHeadline)
                                                <div class="relative group/tip inline-flex">
                                                    <button type="button" @click="openHeadlinePreview({{ json_encode($pub) }})" 
                                                            class="w-8 h-8 rounded-xl bg-amber-500 text-white shadow-sm shadow-amber-500/30 flex items-center justify-center hover:scale-105 active:scale-95 transition-all ring-2 ring-amber-500/30"
                                                            title="Active Cover Headline (Click to preview hero)">
                                                        <span class="material-symbols-outlined text-[18px]">stars</span>
                                                    </button>
                                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tip:flex flex-col items-center pointer-events-none z-30">
                                                        <div class="bg-slate-900/95 dark:bg-slate-800 text-white text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap border border-white/10 flex items-center gap-1.5">
                                                            <span class="material-symbols-outlined text-xs text-amber-400">stars</span>
                                                            <span>Active Cover Headline (Click preview)</span>
                                                        </div>
                                                        <div class="w-2 h-2 bg-slate-900/95 dark:bg-slate-800 rotate-45 -mt-1 border-r border-b border-white/10"></div>
                                                    </div>
                                                </div>
                                            @else
                                                <form method="POST" action="{{ url('dashboard/editorial/articles/' . $pub['id'] . '/headline') }}" class="inline m-0 p-0">
                                                    <div class="relative group/tip inline-flex">
                                                        <button type="submit" 
                                                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-amber-500 text-slate-400 hover:text-white dark:bg-white/5 dark:hover:bg-amber-500 dark:text-slate-400 border border-slate-200 dark:border-white/10 shadow-sm flex items-center justify-center hover:scale-105 active:scale-95 transition-all"
                                                                title="Promote Headline: Set as main homepage cover headline">
                                                            <span class="material-symbols-outlined text-[18px]">star</span>
                                                        </button>
                                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tip:flex flex-col items-center pointer-events-none z-30">
                                                            <div class="bg-slate-900/95 dark:bg-slate-800 text-white text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap border border-white/10 flex items-center gap-1.5">
                                                                <span class="material-symbols-outlined text-xs text-amber-400">star</span>
                                                                <span>Promote Headline (Main homepage cover)</span>
                                                            </div>
                                                            <div class="w-2 h-2 bg-slate-900/95 dark:bg-slate-800 rotate-45 -mt-1 border-r border-b border-white/10"></div>
                                                        </div>
                                                    </div>
                                                </form>
                                            @endif

                                            <!-- 2. Curated School Stories Control -->
                                            @if($isCurrentCurated)
                                                <div class="inline-flex items-center gap-1">
                                                    <div class="relative group/tip inline-flex">
                                                        <button type="button" @click="openCuratedPreview({{ json_encode($pub) }})" 
                                                                class="w-8 h-8 rounded-xl bg-emerald-500 text-white shadow-sm shadow-emerald-500/30 flex items-center justify-center hover:scale-105 active:scale-95 transition-all ring-2 ring-emerald-500/30"
                                                                title="Active Curated: Homepage curated story (Click to preview)">
                                                            <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                                                        </button>
                                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tip:flex flex-col items-center pointer-events-none z-30">
                                                            <div class="bg-slate-900/95 dark:bg-slate-800 text-white text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap border border-white/10 flex items-center gap-1.5">
                                                                <span class="material-symbols-outlined text-xs text-emerald-400">auto_awesome</span>
                                                                <span>Active Curated (Click preview)</span>
                                                            </div>
                                                            <div class="w-2 h-2 bg-slate-900/95 dark:bg-slate-800 rotate-45 -mt-1 border-r border-b border-white/10"></div>
                                                        </div>
                                                    </div>
                                                    <form method="POST" action="{{ url('dashboard/editorial/articles/' . $pub['id'] . '/demote-curated') }}" class="inline m-0 p-0"
                                                          onsubmit="return confirm('Remove this article from 4 Curated School Stories on homepage?');">
                                                        <div class="relative group/tip inline-flex">
                                                            <button type="submit" 
                                                                    class="w-8 h-8 rounded-xl bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white border border-rose-500/20 shadow-sm flex items-center justify-center hover:scale-105 active:scale-95 transition-all"
                                                                    title="Demote Curated: Remove from homepage curated stories">
                                                                <span class="material-symbols-outlined text-[16px]">close</span>
                                                            </button>
                                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tip:flex flex-col items-center pointer-events-none z-30">
                                                                <div class="bg-slate-900/95 dark:bg-slate-800 text-white text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap border border-white/10 flex items-center gap-1.5">
                                                                    <span class="material-symbols-outlined text-xs text-rose-400">close</span>
                                                                    <span>Demote Curated (Remove from curated)</span>
                                                                </div>
                                                                <div class="w-2 h-2 bg-slate-900/95 dark:bg-slate-800 rotate-45 -mt-1 border-r border-b border-white/10"></div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            @else
                                                <form method="POST" action="{{ url('dashboard/editorial/articles/' . $pub['id'] . '/curated') }}" class="inline m-0 p-0">
                                                    <div class="relative group/tip inline-flex">
                                                        <button type="submit" 
                                                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-emerald-600 text-slate-400 hover:text-white dark:bg-white/5 dark:hover:bg-emerald-600 dark:text-slate-400 border border-slate-200 dark:border-white/10 shadow-sm flex items-center justify-center hover:scale-105 active:scale-95 transition-all"
                                                                title="Promote Curated: Add to 4 Curated School Stories on homepage">
                                                            <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                                                        </button>
                                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tip:flex flex-col items-center pointer-events-none z-30">
                                                            <div class="bg-slate-900/95 dark:bg-slate-800 text-white text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap border border-white/10 flex items-center gap-1.5">
                                                                <span class="material-symbols-outlined text-xs text-emerald-400">auto_awesome</span>
                                                                <span>Promote Curated (Max 4 on homepage)</span>
                                                            </div>
                                                            <div class="w-2 h-2 bg-slate-900/95 dark:bg-slate-800 rotate-45 -mt-1 border-r border-b border-white/10"></div>
                                                        </div>
                                                    </div>
                                                </form>
                                            @endif

                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white max-w-xs truncate">
                                        <div class="flex items-center gap-2">
                                            @if($isCurrentHeadline)
                                                <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0" title="Active Cover Headline"></span>
                                            @elseif($isCurrentCurated)
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" title="Active Curated School Story"></span>
                                            @endif
                                            <a href="{{ url('articles/' . $pub['slug']) }}" class="hover:text-primary transition-colors text-slate-900 dark:text-white truncate">
                                                {{ $pub['title'] }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold text-white shadow-sm flex items-center gap-1 w-max" 
                                              style="background-color: {{ $pub['category_color'] }};">
                                            <span class="material-symbols-outlined text-[11px]">{{ $pub['category_icon'] ?? 'tag' }}</span>
                                            <span>{{ $pub['category_name'] }}</span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ $pub['author_name'] }}</td>
                                    <td class="py-3.5 px-4 font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ number_format($pub['views_count']) }}</td>
                                    <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ date('M d, Y', strtotime($pub['published_at'])) }}</td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ url('articles/' . $pub['slug']) }}" 
                                               class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 transition-colors"
                                               title="View Article Live">
                                                <span class="material-symbols-outlined text-sm">visibility</span>
                                            </a>
                                            <a href="{{ url('articles/' . $pub['id'] . '/edit') }}" 
                                               class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-primary font-bold text-xs transition-colors">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</div>

    </div>
    <!-- END CONTENT AREA WRAPPER WITH VERTICALLY STACKED COLLAPSIBLE SECTIONS -->

    <!-- ACTIVE HEADLINE PREVIEW POP-UP MODAL -->
    <div x-cloak x-show="headlinePreviewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="headlinePreviewModalOpen = false"></div>
        
        <div class="relative w-full max-w-3xl rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-amber-500/40 z-10 overflow-hidden animate-in fade-in zoom-in-95 text-slate-900 dark:text-white max-h-[92vh] flex flex-col">
            
            <!-- Modal Top Bar -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/[0.02] shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">stars</span>
                    </span>
                    <div>
                        <h3 class="text-sm font-black font-display text-slate-900 dark:text-white">Active Homepage Cover Headline Preview</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Live preview of how this story is spotlighted on the index frontpage</p>
                    </div>
                </div>
                
                <button type="button" @click="headlinePreviewModalOpen = false" 
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>

            <!-- Modal Body: Realistic Hero Card Replica -->
            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <template x-if="previewHeadlineData">
                    <div class="relative rounded-2xl overflow-hidden border border-slate-200/80 dark:border-white/10 shadow-2xl group min-h-[360px] flex flex-col justify-end p-6 sm:p-8">
                        <!-- Background Image with Gradient Overlay -->
                        <div class="absolute inset-0 z-0">
                            <img :src="previewHeadlineData.featured_image || 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80'" 
                                 :alt="previewHeadlineData.title" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-slate-950/40"></div>
                            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/60 to-transparent"></div>
                        </div>

                        <!-- Foreground Content -->
                        <div class="relative z-10 max-w-2xl space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider text-white shadow-md flex items-center gap-1"
                                      :style="'background-color: ' + (previewHeadlineData.category_color || '#2563eb')">
                                    <span class="material-symbols-outlined text-xs" x-text="previewHeadlineData.category_icon || 'tag'"></span>
                                    <span x-text="previewHeadlineData.category_name || 'BEAT'"></span>
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500 text-white shadow-md flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">stars</span>
                                    <span>Cover Headline</span>
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-black/40 text-slate-300 backdrop-blur-md flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">visibility</span>
                                    <span x-text="Number(previewHeadlineData.views_count || 0).toLocaleString() + ' views'"></span>
                                </span>
                            </div>

                            <h2 class="text-xl sm:text-2xl lg:text-3xl font-black font-display text-white leading-tight drop-shadow-md"
                                x-text="previewHeadlineData.title">
                            </h2>

                            <p class="text-xs sm:text-sm text-slate-300 line-clamp-2 leading-relaxed"
                               x-text="previewHeadlineData.excerpt || ''">
                            </p>

                            <div class="flex items-center gap-3 pt-2">
                                <img :src="previewHeadlineData.author_avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'" 
                                     :alt="previewHeadlineData.author_name" 
                                     class="w-7 h-7 rounded-full object-cover ring-2 ring-primary/40">
                                <div>
                                    <p class="text-xs font-bold text-white leading-none" x-text="previewHeadlineData.author_name"></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5" x-text="'Published ' + formatDateStr(previewHeadlineData.published_at)"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Modal Footer Actions -->
            <div class="flex items-center justify-between px-6 py-4 bg-slate-50 dark:bg-white/[0.02] border-t border-slate-200 dark:border-white/10">
                <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Currently active as homepage cover headline</span>
                </span>

                <div class="flex items-center gap-2">
                    <template x-if="previewHeadlineData">
                        <a :href="'{{ url('articles/') }}/' + previewHeadlineData.slug" target="_blank"
                           class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-800 dark:text-white text-xs font-bold transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                            <span>Read Full Story</span>
                        </a>
                    </template>
                    <a href="{{ url('/') }}" target="_blank"
                       class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-all shadow-md shadow-amber-500/25 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                        <span>View on Frontpage Hero</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTIVE CURATED SCHOOL STORY PREVIEW POP-UP MODAL -->
    <div x-cloak x-show="curatedPreviewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="curatedPreviewModalOpen = false"></div>
        
        <div class="relative w-full max-w-2xl rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-emerald-500/40 z-10 overflow-hidden animate-in fade-in zoom-in-95 text-slate-900 dark:text-white max-h-[92vh] flex flex-col">
            
            <!-- Modal Top Bar -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/[0.02] shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">auto_awesome</span>
                    </span>
                    <div>
                        <h3 class="text-sm font-black font-display text-slate-900 dark:text-white">Active Curated School Story Preview</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Live preview of how this story is showcased in the 2x2 Curated School Stories grid on the index frontpage</p>
                    </div>
                </div>
                
                <button type="button" @click="curatedPreviewModalOpen = false" 
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>

            <!-- Modal Body: Realistic 2x2 Curated Grid Card Replica -->
            <div class="p-6 overflow-y-auto flex-1">
                <template x-if="previewCuratedData">
                    <article class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 shadow-lg overflow-hidden flex flex-col justify-between group bg-white dark:bg-[#1a202c]">
                        <div>
                            <!-- Card Image Banner with Category Tag & Views -->
                            <div class="relative w-full aspect-video overflow-hidden bg-slate-900">
                                <img :src="previewCuratedData.featured_image || 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80'" 
                                     :alt="previewCuratedData.title" 
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                                
                                <!-- Category Pill Badge Top-Left -->
                                <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider text-white shadow-md flex items-center gap-1"
                                      :style="'background-color: ' + (previewCuratedData.category_color || '#2563eb')">
                                    <span class="material-symbols-outlined text-xs" x-text="previewCuratedData.category_icon || 'tag'"></span>
                                    <span x-text="previewCuratedData.category_name || 'Beat'"></span>
                                </span>

                                <!-- Curated Story Badge Top-Right -->
                                <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-white shadow-md flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">auto_awesome</span>
                                    <span>Curated School Story</span>
                                </span>

                                <!-- Views Counter Bottom-Right -->
                                <span class="absolute bottom-3 right-3 flex items-center gap-1 text-[11px] font-bold text-white bg-black/60 backdrop-blur-md px-2.5 py-1 rounded-full">
                                    <span class="material-symbols-outlined text-xs">visibility</span>
                                    <span x-text="Number(previewCuratedData.views_count || 0).toLocaleString() + ' views'"></span>
                                </span>
                            </div>

                            <!-- Card Content Body -->
                            <div class="p-5 sm:p-6 pb-2">
                                <!-- Author & Time Subtitle -->
                                <div class="text-xs font-bold text-slate-400 mb-1.5 flex items-center gap-1">
                                    <span x-text="'By ' + (previewCuratedData.author_name || 'Student Journalist')"></span>
                                    <span>&bull;</span>
                                    <span x-text="formatDateStr(previewCuratedData.published_at)"></span>
                                </div>

                                <!-- Title -->
                                <h3 class="text-base sm:text-lg font-black font-display text-slate-900 dark:text-white leading-snug line-clamp-2 mb-2"
                                    x-text="previewCuratedData.title">
                                </h3>

                                <!-- Excerpt -->
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 font-body leading-relaxed line-clamp-2"
                                   x-text="previewCuratedData.excerpt || ''">
                                </p>
                            </div>
                        </div>

                        <!-- Card Action Footer: Like, Comment, Share, Save, and Read Article -->
                        <div class="p-5 sm:p-6 pt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 dark:border-white/5">
                            <!-- Left: Interactive Counters -->
                            <div class="flex items-center gap-3.5 text-slate-600 dark:text-slate-300">
                                <span class="flex items-center gap-1 text-xs font-bold">
                                    <span class="material-symbols-outlined text-lg text-rose-500 font-fill">favorite</span>
                                    <span x-text="previewCuratedData.likes_count || 0"></span>
                                </span>

                                <span class="flex items-center gap-1 text-xs font-bold">
                                    <span class="material-symbols-outlined text-lg">chat_bubble</span>
                                    <span x-text="previewCuratedData.comments_count || 0"></span>
                                </span>

                                <span class="text-slate-400">
                                    <span class="material-symbols-outlined text-lg">send</span>
                                </span>

                                <span class="flex items-center gap-1 text-xs font-bold">
                                    <span class="material-symbols-outlined text-lg text-primary font-fill">bookmark</span>
                                    <span x-text="previewCuratedData.saves_count || 0"></span>
                                </span>
                            </div>

                            <!-- Right: Read Article Link -->
                            <span class="inline-flex items-center gap-1 text-xs font-black text-primary">
                                <span>Read Story</span>
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </span>
                        </div>
                    </article>
                </template>
            </div>

            <!-- Modal Footer Actions -->
            <div class="flex items-center justify-between px-6 py-4 bg-slate-50 dark:bg-white/[0.02] border-t border-slate-200 dark:border-white/10">
                <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Active in Curated School Stories (1 of 4 featured slots on index)</span>
                </span>

                <div class="flex items-center gap-2">
                    <template x-if="previewCuratedData">
                        <a :href="'{{ url('articles/') }}/' + previewCuratedData.slug" target="_blank"
                           class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-800 dark:text-white text-xs font-bold transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                            <span>Read Full Story</span>
                        </a>
                    </template>
                    <a href="{{ url('/#curated-stories') }}" target="_blank"
                       class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-md shadow-emerald-600/25 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                        <span>View on Frontpage Curated Grid</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- REJECTION FEEDBACK MODAL -->
    <div x-cloak x-show="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-md" @click="rejectModalOpen = false"></div>
        <div class="relative w-full max-w-lg p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-rose-500/30 z-10 animate-in fade-in zoom-in-95 text-slate-900 dark:text-white">
            <h3 class="text-xl font-black font-display text-slate-900 dark:text-white mb-2">Reject Submission</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">
                Article: <strong class="text-slate-900 dark:text-white" x-text="rejectArticleTitle"></strong>
            </p>

            <form :action="'{{ url('dashboard/editorial/articles/') }}/' + rejectArticleId + '/reject'" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Editorial Feedback for Writer</label>
                    <textarea name="feedback" rows="4" required placeholder="Explain clearly what requires revision (e.g. Needs credible citations, adjust headline for tone, fix section 2 assertions)..."
                              class="w-full p-4 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/40 resize-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold shadow-lg shadow-rose-500/25">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CATEGORY MANAGER MODAL -->
    <div x-cloak x-show="categoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="categoryModalOpen = false"></div>
        <div class="relative w-full max-w-4xl p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-slate-200 dark:border-white/10 z-10 max-h-[92vh] overflow-y-auto space-y-7 text-slate-900 dark:text-white">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-primary/20 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">category</span>
                    </span>
                    <div>
                        <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Editorial Category Beats</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Manage beats, customize visual badges, and reassign story taxonomies</p>
                    </div>
                </div>
                <button type="button" @click="categoryModalOpen = false" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- SIMPLIFIED STYLING KNOWLEDGE & REFERENCE TOOLBAR -->
            <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-lg">palette</span>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Styling Resources:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="https://fonts.google.com/icons?icon.set=Material+Symbols" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition-all border border-indigo-500/20 shadow-sm"
                       title="Browse 3,000+ Google Material Symbols">
                        <span class="material-symbols-outlined text-sm">token</span>
                        <span>Material Symbols</span>
                        <span class="material-symbols-outlined text-[10px]">open_in_new</span>
                    </a>
                    <a href="https://coolors.co/palettes/trending" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-pink-500/10 hover:bg-pink-500/20 text-pink-700 dark:text-pink-300 text-xs font-bold transition-all border border-pink-500/20 shadow-sm"
                       title="Trending Gen-Z Color Palettes">
                        <span class="material-symbols-outlined text-sm">palette</span>
                        <span>Coolors Palettes</span>
                        <span class="material-symbols-outlined text-[10px]">open_in_new</span>
                    </a>
                    <a href="https://tailwindcss.com/docs/customizing-colors" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-800 dark:text-cyan-300 text-xs font-bold transition-all border border-cyan-500/20 shadow-sm"
                       title="Tailwind CSS Hex Color Swatches">
                        <span class="material-symbols-outlined text-sm">format_paint</span>
                        <span>Tailwind Colors</span>
                        <span class="material-symbols-outlined text-[10px]">open_in_new</span>
                    </a>
                    <a href="https://htmlcolorcodes.com/" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-800 dark:text-amber-300 text-xs font-bold transition-all border border-amber-500/20 shadow-sm"
                       title="HTML Color Picker & Codes">
                        <span class="material-symbols-outlined text-sm">colorize</span>
                        <span>HTML Color Codes</span>
                        <span class="material-symbols-outlined text-[10px]">open_in_new</span>
                    </a>
                </div>
            </div>

            <!-- Existing Categories List (Read, Edit, Delete) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                        <span>Active Category Beats</span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-200 dark:bg-white/10 text-slate-800 dark:text-white text-[10px]">{{ count($categories) }} total</span>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Click edit to update details, or delete to reassign stories</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($categories as $cat)
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/5 hover:border-slate-300 dark:hover:border-white/15 transition-all flex items-center justify-between gap-3 group">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                                     style="background-color: {{ $cat['color_code'] }}20; border: 1px solid {{ $cat['color_code'] }}50; color: {{ $cat['color_code'] }};">
                                    <span class="material-symbols-outlined text-xl">{{ $cat['icon'] ?: 'tag' }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-sm text-slate-900 dark:text-white truncate">{{ $cat['name'] }}</h4>
                                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-200/80 dark:bg-white/5 text-slate-600 dark:text-slate-400">#{{ $cat['slug'] }}</span>
                                    </div>
                                    @if(!empty($cat['description']))
                                        <p class="text-[11px] text-slate-600 dark:text-slate-400 truncate max-w-[200px] sm:max-w-xs mt-0.5">{{ $cat['description'] }}</p>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1.5 text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-200/70 dark:bg-white/5 text-slate-800 dark:text-white font-bold">{{ $cat['total_articles_count'] }} stories total</span>
                                        <span>&bull;</span>
                                        <span>{{ $cat['published_articles_count'] }} published</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons: Edit & Delete -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" 
                                        @click="openEditCategory({{ $cat['id'] }})"
                                        class="p-2 rounded-xl bg-slate-200/70 hover:bg-primary/20 dark:bg-white/5 dark:hover:bg-primary/20 text-slate-700 hover:text-primary dark:text-slate-300 dark:hover:text-primary transition-colors"
                                        title="Edit Beat details">
                                    <span class="material-symbols-outlined text-base">edit</span>
                                </button>
                                <button type="button" 
                                        @click="openDeleteCategory({{ $cat['id'] }})"
                                        class="p-2 rounded-xl bg-slate-200/70 hover:bg-rose-500/20 dark:bg-white/5 dark:hover:bg-rose-500/20 text-slate-700 hover:text-rose-500 dark:text-slate-300 dark:hover:text-rose-400 transition-colors"
                                        title="Delete Beat & Reassign Stories">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Add New Category Form (Create) -->
            <form action="{{ url('dashboard/editorial/categories') }}" method="POST" class="p-6 rounded-3xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 space-y-5">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base">add_circle</span>
                            <span>Create New Beat</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Fill in details or use quick pickers below</p>
                    </div>

                    <!-- Live Badge Preview -->
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Live Preview:</span>
                        <div class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-sm transition-all"
                             :style="'background-color: ' + newCategory.color_code + '25; border: 1px solid ' + newCategory.color_code + '60; color: ' + newCategory.color_code">
                            <span class="material-symbols-outlined text-sm" x-text="newCategory.icon || 'tag'"></span>
                            <span x-text="newCategory.name || 'New Beat Name'"></span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Beat Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="newCategory.name" required placeholder="e.g. Cinema & Pop Culture" 
                               class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Accent Color (Hex) <span class="text-rose-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="newCategory.color_code" 
                                   class="w-10 h-10 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 cursor-pointer p-0.5">
                            <input type="text" name="color_code" x-model="newCategory.color_code" required placeholder="#ec4899"
                                   class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-primary/40">
                        </div>
                    </div>
                </div>

                <!-- Quick Color Swatches Helper -->
                <div>
                    <span class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-2">Quick Palette Swatches (click to apply):</span>
                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $presetColors = [
                                ['hex' => '#ec4899', 'name' => 'Hot Pink'],
                                ['hex' => '#8b5cf6', 'name' => 'Electric Violet'],
                                ['hex' => '#06b6d4', 'name' => 'Neon Cyan'],
                                ['hex' => '#10b981', 'name' => 'Emerald'],
                                ['hex' => '#f59e0b', 'name' => 'Amber Gold'],
                                ['hex' => '#f43f5e', 'name' => 'Rose Red'],
                                ['hex' => '#3b82f6', 'name' => 'Bright Blue'],
                                ['hex' => '#84cc16', 'name' => 'Lime Pop'],
                                ['hex' => '#f97316', 'name' => 'Sunset Orange'],
                                ['hex' => '#6366f1', 'name' => 'Indigo'],
                                ['hex' => '#14b8a6', 'name' => 'Teal'],
                                ['hex' => '#64748b', 'name' => 'Slate']
                            ];
                        @endphp
                        @foreach($presetColors as $colorItem)
                            <button type="button"
                                    @click="newCategory.color_code = '{{ $colorItem['hex'] }}'"
                                    class="w-7 h-7 rounded-lg transition-transform hover:scale-110 shadow-sm border border-slate-300 dark:border-white/20 flex items-center justify-center"
                                    style="background-color: {{ $colorItem['hex'] }}"
                                    title="{{ $colorItem['name'] }} ({{ $colorItem['hex'] }})">
                                <span x-show="newCategory.color_code.toLowerCase() === '{{ strtolower($colorItem['hex']) }}'" class="material-symbols-outlined text-xs text-white drop-shadow">check</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Material Icon Input & Quick Pickers -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Material Icon Name</label>
                        <a href="https://fonts.google.com/icons?icon.set=Material+Symbols" target="_blank" rel="noopener noreferrer"
                           class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-semibold flex items-center gap-1">
                            <span>Browse 3,000+ Icons</span>
                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                        </a>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 flex items-center justify-center shrink-0 shadow-sm"
                             :style="'color: ' + newCategory.color_code">
                            <span class="material-symbols-outlined text-xl" x-text="newCategory.icon || 'tag'"></span>
                        </div>
                        <input type="text" name="icon" x-model="newCategory.icon" placeholder="e.g. movie, school, music_note, sports_esports" 
                               class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>

                    <!-- Quick Icon Badges -->
                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] text-slate-600 dark:text-slate-400 mr-1">Popular Beats Icons:</span>
                        @php
                            $presetIcons = [
                                'school' => 'Campus',
                                'movie' => 'Cinema',
                                'music_note' => 'Music',
                                'sports_esports' => 'Gaming',
                                'science' => 'Tech',
                                'palette' => 'Arts',
                                'local_fire_department' => 'Hype',
                                'psychology' => 'Wellness',
                                'podcasts' => 'Voice',
                                'travel_explore' => 'Explore',
                                'fitness_center' => 'Athletics',
                                'newspaper' => 'Press',
                                'favorite' => 'Life',
                                'star' => 'Featured'
                            ];
                        @endphp
                        @foreach($presetIcons as $iconName => $iconLabel)
                            <button type="button"
                                    @click="newCategory.icon = '{{ $iconName }}'"
                                    class="px-2.5 py-1 rounded-lg bg-slate-200/70 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 border border-slate-300/50 dark:border-white/5 text-[11px] text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white flex items-center gap-1 transition-colors"
                                    :class="newCategory.icon === '{{ $iconName }}' ? 'border-primary text-primary bg-primary/10' : ''">
                                <span class="material-symbols-outlined text-xs">{{ $iconName }}</span>
                                <span>{{ $iconLabel }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Scope Description</label>
                    <input type="text" name="description" x-model="newCategory.description" placeholder="Short description of stories covered in this beat..." 
                           class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="categoryModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold transition-colors">
                        Close
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">add</span>
                        <span>Add Category Beat</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT CATEGORY BEAT MODAL -->
    <div x-cloak x-show="editCategoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="editCategoryModalOpen = false"></div>
        <div class="relative w-full max-w-xl p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-primary/30 z-10 animate-in fade-in zoom-in-95 space-y-6 text-slate-900 dark:text-white">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-primary/20 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">edit_square</span>
                    </span>
                    <div>
                        <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Edit Category Beat</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Update taxonomy name, icon, accent color, and scope</p>
                    </div>
                </div>
                <button type="button" @click="editCategoryModalOpen = false" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Live Preview -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-between">
                <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Live Badge Preview:</span>
                <div class="px-3.5 py-1.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm transition-all"
                     :style="'background-color: ' + editCategory.color_code + '25; border: 1px solid ' + editCategory.color_code + '60; color: ' + editCategory.color_code">
                    <span class="material-symbols-outlined text-base" x-text="editCategory.icon || 'tag'"></span>
                    <span x-text="editCategory.name || 'Beat Name'"></span>
                </div>
            </div>

            <form :action="'{{ rtrim(url('dashboard/editorial/categories'), '/') }}/' + editCategory.id + '/update'" method="POST" class="space-y-4">
                <input type="hidden" name="id" :value="editCategory.id">
                <input type="hidden" name="category_id" :value="editCategory.id">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Beat Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editCategory.name" required
                           class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Accent Color (Hex) <span class="text-rose-500">*</span></label>
                    <div class="flex items-center gap-2">
                        <input type="color" x-model="editCategory.color_code" 
                               class="w-10 h-10 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 cursor-pointer p-0.5">
                        <input type="text" name="color_code" x-model="editCategory.color_code" required
                               class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>

                    <!-- Quick palette row -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        @foreach($presetColors as $colorItem)
                            <button type="button"
                                    @click="editCategory.color_code = '{{ $colorItem['hex'] }}'"
                                    class="w-6 h-6 rounded-md border border-slate-300 dark:border-white/20 transition-transform hover:scale-110"
                                    style="background-color: {{ $colorItem['hex'] }}"
                                    title="{{ $colorItem['name'] }}">
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Material Icon Name</label>
                        <a href="https://fonts.google.com/icons?icon.set=Material+Symbols" target="_blank" rel="noopener noreferrer"
                           class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-semibold flex items-center gap-1">
                            <span>Browse Symbols</span>
                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                        </a>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 flex items-center justify-center shrink-0 shadow-sm"
                             :style="'color: ' + editCategory.color_code">
                            <span class="material-symbols-outlined text-xl" x-text="editCategory.icon || 'tag'"></span>
                        </div>
                        <input type="text" name="icon" x-model="editCategory.icon" placeholder="e.g. movie, music_note, sports" 
                               class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                    <input type="text" name="description" x-model="editCategory.description" placeholder="Beat scope description..." 
                           class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-white/10">
                    <button type="button" @click="editCategoryModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-lg shadow-primary/25">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SAFE DELETE CATEGORY MODAL: STORY REASSIGNMENT DIALOG -->
    <div x-cloak x-show="deleteCategoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="deleteCategoryModalOpen = false"></div>
        <div class="relative w-full max-w-lg p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-amber-500/30 z-10 animate-in fade-in zoom-in-95 space-y-5 text-slate-900 dark:text-white">
            
            <div class="flex items-center gap-3 pb-3 border-b border-slate-200 dark:border-white/10">
                <span class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-500 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">swap_horiz</span>
                </span>
                <div>
                    <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Reassign Stories & Delete Beat</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Protecting content integrity before deleting category taxonomy</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 space-y-2">
                <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400 text-xs font-bold">
                    <span class="material-symbols-outlined text-base">shield</span>
                    <span>No Stories Will Be Deleted</span>
                </div>
                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                    You are deleting the category beat <strong class="text-slate-900 dark:text-white" x-text="deleteCategoryData.name"></strong>.
                    There are currently <strong class="text-amber-600 dark:text-amber-400" x-text="deleteCategoryData.total_stories"></strong> stories inside this beat.
                    Select the destination category below where all these stories will be moved safely.
                </p>
            </div>

            <!-- Destination Category Selection -->
            <form :action="'{{ rtrim(url('dashboard/editorial/categories'), '/') }}/' + deleteCategoryData.id + '/delete'" method="POST" class="space-y-4">
                <input type="hidden" name="id" :value="deleteCategoryData.id">
                <input type="hidden" name="category_id" :value="deleteCategoryData.id">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                        Move all stories to category: <span class="text-rose-500">*</span>
                    </label>

                    <template x-if="hasAlternativeCategories()">
                        <select name="target_category_id" x-model="targetCategoryId" required
                                class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                            <template x-for="cat in getAlternativeCategories()" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name + ' (' + cat.total_articles_count + ' stories currently)'"></option>
                            </template>
                        </select>
                    </template>

                    <template x-if="!hasAlternativeCategories()">
                        <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-300 text-xs font-semibold">
                            Cannot delete: This is the only category beat on the platform. Please create another category beat first so stories have a valid destination.
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-white/10">
                    <button type="button" @click="deleteCategoryModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="!hasAlternativeCategories()"
                            class="px-5 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold shadow-lg shadow-rose-500/25 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">delete_sweep</span>
                        <span>Move Stories & Delete Beat</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- HERO POPUP: CATEGORY CRUD SUCCESS (CREATED / UPDATED / DELETED) -->
    <div x-cloak x-show="heroPopupOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <!-- Backdrop with blur -->
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="heroPopupOpen = false"></div>

        <!-- Hero Card -->
        <div class="relative w-full max-w-lg p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl @if($notice === 'category_updated') border border-primary/40 @elseif($notice === 'category_created') border border-teal-500/40 @else border border-emerald-500/40 @endif z-10 text-center animate-in fade-in zoom-in-95 overflow-hidden text-slate-900 dark:text-white">
            <!-- Glowing background accents -->
            @if($notice === 'category_updated')
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-primary/25 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-violet-500/20 rounded-full blur-3xl pointer-events-none"></div>
            @elseif($notice === 'category_created')
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-teal-500/25 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
            @else
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
            @endif

            <div class="relative z-10 space-y-5">
                <!-- Hero Badge / Icon -->
                @if($notice === 'category_updated')
                    <div class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-tr from-primary via-indigo-500 to-violet-500 p-0.5 shadow-xl shadow-primary/30 flex items-center justify-center">
                        <div class="w-full h-full bg-slate-100 dark:bg-slate-900 rounded-[22px] flex items-center justify-center text-primary dark:text-indigo-400">
                            <span class="material-symbols-outlined text-4xl">task_alt</span>
                        </div>
                    </div>
                @elseif($notice === 'category_created')
                    <div class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-tr from-teal-400 via-emerald-500 to-primary p-0.5 shadow-xl shadow-teal-500/30 flex items-center justify-center">
                        <div class="w-full h-full bg-slate-100 dark:bg-slate-900 rounded-[22px] flex items-center justify-center text-teal-500 dark:text-teal-400">
                            <span class="material-symbols-outlined text-4xl">add_task</span>
                        </div>
                    </div>
                @else
                    <div class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-xl shadow-emerald-500/30 flex items-center justify-center">
                        <div class="w-full h-full bg-slate-100 dark:bg-slate-900 rounded-[22px] flex items-center justify-center text-emerald-500 dark:text-emerald-400">
                            <span class="material-symbols-outlined text-4xl">published_with_changes</span>
                        </div>
                    </div>
                @endif

                <div>
                    @if($notice === 'category_updated')
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-primary/20 text-primary dark:text-indigo-300 border border-primary/30">
                            Changes Saved &bull; Category Beat Updated
                        </span>
                        <h3 class="text-2xl font-black font-display text-slate-900 dark:text-white mt-2">Category Changes Saved Successfully!</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 leading-relaxed">
                            Category beat <strong class="text-slate-900 dark:text-white">{{ $categoryName ?? 'Category Beat' }}</strong> was successfully updated and permanently saved in the brighteen database.
                        </p>
                    @elseif($notice === 'category_created')
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-teal-500/20 text-teal-600 dark:text-teal-300 border border-teal-500/30">
                            New Beat Ready &bull; New Beat Created
                        </span>
                        <h3 class="text-2xl font-black font-display text-slate-900 dark:text-white mt-2">New Category Created Successfully!</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 leading-relaxed">
                            Category beat <strong class="text-slate-900 dark:text-white">{{ $categoryName ?? 'New Category' }}</strong> is now active and ready for story drafting and journalist assignments.
                        </p>
                    @else
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30">
                            Content Protected & Migrated
                        </span>
                        <h3 class="text-2xl font-black font-display text-slate-900 dark:text-white mt-2">Category Beat Deleted</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 leading-relaxed">
                            The category beat <strong class="text-slate-900 dark:text-white">{{ $deletedName ?? 'Deleted Category' }}</strong> was safely removed from Brighteen.
                        </p>
                    @endif
                </div>

                <!-- Detail Box -->
                @if($notice === 'category_updated' || $notice === 'category_created')
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-left space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">Live Beat Badge:</span>
                            <div class="px-3.5 py-1.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm"
                                 style="background-color: {{ ($categoryColor ?? '#ec4899') . '25' }}; border: 1px solid {{ ($categoryColor ?? '#ec4899') . '60' }}; color: {{ $categoryColor ?? '#ec4899' }};">
                                <span class="material-symbols-outlined text-base">{{ $categoryIcon ?? 'tag' }}</span>
                                <span>{{ $categoryName ?? 'Category Beat' }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs pt-2 border-t border-slate-200 dark:border-white/5">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400 block text-[10px] uppercase font-bold">Taxonomy Slug</span>
                                <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">#{{ $categorySlug ?? 'beat' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400 block text-[10px] uppercase font-bold">Accent Color</span>
                                <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold flex items-center gap-1.5">
                                    <span class="w-3.5 h-3.5 rounded-md inline-block shadow-sm" style="background-color: {{ $categoryColor ?? '#ec4899' }}"></span>
                                    <span>{{ $categoryColor ?? '#ec4899' }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200 dark:border-white/5 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-emerald-500">verified</span>
                            <span>Category data was successfully saved to the database and active across the entire platform!</span>
                        </div>
                    </div>
                @else
                    <!-- Migration Detail Box for Deletion -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-left space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">Stories Reassigned:</span>
                            <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm">{{ $movedCount ?? 0 }} stories</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">Destination Beat:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $targetName ?? 'Target Category' }}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200 dark:border-white/5 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-emerald-500 dark:text-emerald-400">verified</span>
                            <span>Zero stories were lost. Foreign key relational integrity preserved!</span>
                        </div>
                    </div>
                @endif

                <div>
                    <button type="button" @click="heroPopupOpen = false"
                            class="w-full py-3 px-6 rounded-2xl @if($notice === 'category_updated') bg-gradient-to-r from-primary to-violet-600 hover:from-primary-hover hover:to-violet-700 shadow-primary/25 @elseif($notice === 'category_created') bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 shadow-teal-500/25 @else bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-emerald-500/25 @endif text-white font-bold text-xs uppercase tracking-wider shadow-lg transition-all">
                        Awesome, Got It!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CREATE ASSIGNMENT MODAL -->
    <div x-cloak x-show="createAssignmentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="createAssignmentModalOpen = false"></div>
        <div class="relative w-full max-w-2xl p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-slate-200 dark:border-white/10 z-10 max-h-[92vh] overflow-y-auto space-y-6 text-slate-900 dark:text-white">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-primary/20 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">assignment_add</span>
                    </span>
                    <div>
                        <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Dispatch New Assignment</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Assign story topics to newsroom journalists with automated alerts</p>
                    </div>
                </div>
                <button type="button" @click="createAssignmentModalOpen = false" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <form action="{{ url('dashboard/editorial/assignments') }}" method="POST" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Assigned Journalist -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Assigned Journalist <span class="text-rose-500">*</span>
                        </label>
                        <select name="assigned_to_user_id" required
                                class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">-- Select Journalist / Contributor --</option>
                            @foreach($journalists as $j)
                                <option value="{{ $j['id'] }}">
                                    {{ $j['name'] }} ({{ ucfirst($j['role_display_name'] ?? $j['role_name']) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Beat -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Category Beat <span class="text-rose-500">*</span>
                        </label>
                        <select name="category_id" required
                                class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">-- Select Beat Category --</option>
                            @foreach($categories as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Topic Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Topic / Story Angle <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="topic" required placeholder="e.g. Youth Esports Championship Finals 2026 Coverage"
                           class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <!-- Description / Briefing -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Description & Briefing Instructions
                    </label>
                    <textarea name="description" rows="3" placeholder="Provide angle, interview subjects, target tone, key stats, or formatting requirements..."
                              class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Submission Deadline -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Submission Deadline <span class="text-rose-500">*</span>
                        </label>
                        <input type="datetime-local" name="submission_deadline" required
                               class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">Automatic reminder sent on this day if pending</span>
                    </div>

                    <!-- Publishing Schedule -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Publishing Schedule <span class="text-rose-500">*</span>
                        </label>
                        <input type="datetime-local" name="publishing_schedule" required
                               class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">Editor reminder triggered if story unpublished</span>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="p-3.5 rounded-2xl bg-primary/10 border border-primary/20 flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300">
                    <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5">notifications_active</span>
                    <span>The assigned journalist will instantly receive a notification linking directly to their Assignment Center with pre-filled composition links.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-white/10">
                    <button type="button" @click="createAssignmentModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-lg shadow-primary/25 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>Submit Assignment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT ASSIGNMENT MODAL -->
    <div x-cloak x-show="editAssignmentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="editAssignmentModalOpen = false"></div>
        <div class="relative w-full max-w-2xl p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-slate-200 dark:border-white/10 z-10 max-h-[92vh] overflow-y-auto space-y-6 text-slate-900 dark:text-white">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-500 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">edit_note</span>
                    </span>
                    <div>
                        <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Edit Assignment</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Modify journalist assignment, briefing notes, and milestone deadlines</p>
                    </div>
                </div>
                <button type="button" @click="editAssignmentModalOpen = false" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <form :action="'{{ url('dashboard/editorial/assignments/') }}/' + assignmentToEdit.id + '/update'" method="POST" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Assigned Journalist -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Assigned Journalist <span class="text-rose-500">*</span>
                        </label>
                        <select name="assigned_to_user_id" x-model="assignmentToEdit.assigned_to_user_id" required
                                class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                            @foreach($journalists as $j)
                                <option value="{{ $j['id'] }}">{{ $j['name'] }} ({{ ucfirst($j['role_display_name'] ?? $j['role_name']) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Beat -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Category Beat <span class="text-rose-500">*</span>
                        </label>
                        <select name="category_id" x-model="assignmentToEdit.category_id" required
                                class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                            @foreach($categories as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Topic Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Topic / Story Angle <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="topic" x-model="assignmentToEdit.topic" required
                           class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <!-- Description / Briefing -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Description & Briefing Instructions
                    </label>
                    <textarea name="description" x-model="assignmentToEdit.description" rows="3"
                              class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Submission Deadline -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Submission Deadline <span class="text-rose-500">*</span>
                        </label>
                        <input type="datetime-local" name="submission_deadline" x-model="assignmentToEdit.submission_deadline" required
                               class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>

                    <!-- Publishing Schedule -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Publishing Schedule <span class="text-rose-500">*</span>
                        </label>
                        <input type="datetime-local" name="publishing_schedule" x-model="assignmentToEdit.publishing_schedule" required
                               class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                </div>

                <!-- Status Override -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Status Manual Override
                    </label>
                    <select name="status" x-model="assignmentToEdit.status"
                            class="w-full px-4 py-2.5 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="pending">Pending / In Progress</option>
                        <option value="turned_in">Turned In</option>
                        <option value="approved">Approved / Live</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-white/10">
                    <button type="button" @click="editAssignmentModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-lg shadow-amber-500/25 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE ASSIGNMENT MODAL -->
    <div x-cloak x-show="deleteAssignmentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="deleteAssignmentModalOpen = false"></div>
        <div class="relative w-full max-w-md p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-rose-500/30 z-10 space-y-5 text-slate-900 dark:text-white">
            
            <div class="flex items-center gap-3 pb-3 border-b border-slate-200 dark:border-white/10">
                <span class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">delete_forever</span>
                </span>
                <div>
                    <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Delete Assignment</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Remove assignment from the editorial schedule</p>
                </div>
            </div>

            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                Are you sure you want to delete assignment <strong class="text-slate-900 dark:text-white" x-text="assignmentToDelete.topic"></strong>?
                This action cannot be undone. Any turned-in articles will remain safe in the system.
            </p>

            <form :action="'{{ url('dashboard/editorial/assignments/') }}/' + assignmentToDelete.id + '/delete'" method="POST" class="pt-2">
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 dark:border-white/10 pt-3">
                    <button type="button" @click="deleteAssignmentModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold shadow-lg shadow-rose-500/25 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">delete</span>
                        <span>Confirm Delete</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
