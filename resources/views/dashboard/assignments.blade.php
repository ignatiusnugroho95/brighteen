@extends('layouts.app')

@section('content')
<script>
function assignmentsDesk() {
    return {
        // Tab state: 'active' | 'drafts' | 'review' | 'published'
        activeTab: (function() {
            const hash = window.location.hash ? window.location.hash.replace('#', '').split('?')[0] : '';
            if (['drafts', 'draft', 'wip', 'saved'].includes(hash)) return 'drafts';
            if (['review', 'turned-in', 'turned_in', 'queue'].includes(hash)) return 'review';
            if (['published', 'approved', 'portfolio', 'dispatches'].includes(hash)) return 'published';
            return 'active';
        })(),

        isMobile: typeof window !== 'undefined' ? window.innerWidth < 768 : false,

        mobileSections: {
            active: true,
            drafts: false,
            review: false,
            published: false
        },

        loadedTabs: {
            active: false,
            drafts: false,
            review: false,
            published: false
        },

        searchQuery: '',
        activeSubFilter: 'all', // 'all' | 'pending' | 'expired'
        draftSubFilter: 'all',  // 'all' | 'draft' | 'pending_review' | 'rejected'

        init() {
            // Activate initial tab and mobile section
            this.loadedTabs[this.activeTab] = true;
            this.mobileSections = {
                active: this.activeTab === 'active',
                drafts: this.activeTab === 'drafts',
                review: this.activeTab === 'review',
                published: this.activeTab === 'published'
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
            if (this.isMobile) {
                this.toggleMobileSection(tab, true);
            }
        },

        toggleMobileSection(tab, forceOpen = false) {
            if (this.mobileSections[tab] && !forceOpen) {
                // Allow collapsing the active section to save maximum space
                this.mobileSections[tab] = false;
            } else {
                // Accordion behavior: close other sections to keep mobile uncluttered
                this.mobileSections.active = false;
                this.mobileSections.drafts = false;
                this.mobileSections.review = false;
                this.mobileSections.published = false;
                this.mobileSections[tab] = true;
                this.loadedTabs[tab] = true;
                this.activeTab = tab;
                this.$nextTick(() => {
                    const el = document.getElementById(tab + '-section');
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }
        },

        expandAllMobile() {
            this.mobileSections.active = true;
            this.mobileSections.drafts = true;
            this.mobileSections.review = true;
            this.mobileSections.published = true;
            this.loadedTabs.active = true;
            this.loadedTabs.drafts = true;
            this.loadedTabs.review = true;
            this.loadedTabs.published = true;
        },

        collapseAllMobile() {
            this.mobileSections.active = false;
            this.mobileSections.drafts = false;
            this.mobileSections.review = false;
            this.mobileSections.published = false;
        },

        matchesAssignment(topic, desc, cat, editor, title) {
            if (!this.searchQuery || !this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase();
            return (topic && topic.toLowerCase().includes(q)) ||
                   (desc && desc.toLowerCase().includes(q)) ||
                   (cat && cat.toLowerCase().includes(q)) ||
                   (editor && editor.toLowerCase().includes(q)) ||
                   (title && title.toLowerCase().includes(q));
        },

        matchesDraft(title, excerpt, cat, status, briefTopic) {
            if (this.draftSubFilter !== 'all' && status !== this.draftSubFilter) return false;
            if (!this.searchQuery || !this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase();
            return (title && title.toLowerCase().includes(q)) ||
                   (excerpt && excerpt.toLowerCase().includes(q)) ||
                   (cat && cat.toLowerCase().includes(q)) ||
                   (briefTopic && briefTopic.toLowerCase().includes(q));
        },

        matchesActiveFilter(status) {
            if (this.activeSubFilter === 'all') return true;
            if (this.activeSubFilter === 'pending') return status === 'pending';
            if (this.activeSubFilter === 'expired') return status === 'expired';
            return true;
        }
    };
}
</script>

<div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8" x-data="assignmentsDesk()">

    <!-- 1. PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-base">assignment</span>
                <span>Author Workspace & Pitch Center</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">Assignments & Saved Drafts</h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
                Manage your assigned briefs, resume your unpublished drafts, and track stories turned in to the Editorial Review Queue.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            @if(auth()->isEditor() || auth()->isManagement())
                <a href="{{ url('dashboard/editorial') }}" 
                   class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-2xl bg-white dark:bg-surface-card hover:bg-slate-100 dark:hover:bg-white/10 text-xs font-bold border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 transition-all flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-base text-primary">rule</span>
                    <span>Editorial Queue</span>
                </a>
            @endif

            <a href="{{ url('articles/create') }}" 
               class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-primary/25 transition-all flex items-center gap-1.5 transform hover:-translate-y-0.5">
                <span class="material-symbols-outlined text-base">edit_note</span>
                <span>Write / Pitch Story</span>
            </a>
        </div>
    </div>

    <!-- Notification Alert -->
    @if(!empty($notice))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm font-semibold flex items-center justify-between gap-2 shadow-xs animate-in fade-in">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="material-symbols-outlined text-xl shrink-0">check_circle</span>
                <span class="truncate">
                    @if($notice === 'assignment_turned_in')
                        Awesome work! Your story was turned in successfully and submitted to the Editorial Review Queue.
                    @elseif($notice === 'draft_saved')
                        Draft saved successfully! You can resume writing anytime from your Saved Drafts section.
                    @elseif($notice === 'draft_updated')
                        Draft updated successfully.
                    @elseif($notice === 'draft_deleted')
                        Draft has been deleted successfully.
                    @elseif($notice === 'submitted_for_review')
                        Your story draft has been submitted to the Editorial Review Queue!
                    @else
                        Action completed successfully.
                    @endif
                </span>
            </div>
            <a href="{{ url('dashboard/assignments') }}" class="text-xs font-bold underline opacity-80 hover:opacity-100 shrink-0">Dismiss</a>
        </div>
    @endif

    <!-- 2. METRIC STAT CARDS (CLICKABLE TABS / FILTERS) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        
        <!-- Metric 1: Total Assigned Briefs -->
        <button type="button" @click="setTab('active')"
                class="p-4 sm:p-5 rounded-3xl glass-panel border border-primary/30 text-left hover:border-primary/60 transition-all group relative overflow-hidden shadow-xs"
                :class="activeTab === 'active' ? 'ring-2 ring-primary/50 bg-primary/[0.04]' : ''">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Briefs</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-primary mt-1">{{ $totalAssigned }}</h3>
                </div>
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-lg sm:text-xl">folder_open</span>
                </span>
            </div>
            <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1 font-semibold truncate">
                <span>All editorial briefs</span>
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </p>
        </button>

        <!-- Metric 2: Active Pending & Overdue Tasks -->
        <button type="button" @click="setTab('active')"
                class="p-4 sm:p-5 rounded-3xl glass-panel border border-amber-500/30 text-left hover:border-amber-500/60 transition-all group relative overflow-hidden shadow-xs"
                :class="activeTab === 'active' ? 'ring-2 ring-amber-500/50 bg-amber-500/[0.04]' : ''">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Active To-Do</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-amber-500 dark:text-amber-400 mt-1">{{ $totalActive }}</h3>
                </div>
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-amber-500/10 text-amber-500 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-lg sm:text-xl">pending_actions</span>
                </span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[10px] sm:text-[11px]">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ $totalPending }} Pending</span>
                @if($totalExpired > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-500 dark:text-rose-400 animate-pulse shrink-0">
                        {{ $totalExpired }} Overdue
                    </span>
                @endif
            </div>
        </button>

        <!-- Metric 3: My Saved Drafts (Unpublished Stories) -->
        <button type="button" @click="setTab('drafts')"
                class="p-4 sm:p-5 rounded-3xl glass-panel border border-sky-500/30 text-left hover:border-sky-500/60 transition-all group relative overflow-hidden shadow-xs"
                :class="activeTab === 'drafts' ? 'ring-2 ring-sky-500/50 bg-sky-500/[0.04]' : ''">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">My Drafts</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-sky-500 dark:text-sky-400 mt-1">{{ $totalDrafts }}</h3>
                </div>
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-sky-500/10 text-sky-500 dark:text-sky-400 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-lg sm:text-xl">edit_document</span>
                </span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[10px] sm:text-[11px]">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ $draftOnlyCount }} WIP</span>
                @if($draftRejectedCount > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-rose-500/20 text-rose-500 dark:text-rose-400 shrink-0">
                        {{ $draftRejectedCount }} Revision
                    </span>
                @else
                    <span class="text-slate-400 dark:text-slate-500 font-medium">{{ $draftReviewCount }} In Review</span>
                @endif
            </div>
        </button>

        <!-- Metric 4: Turned In & Under Review -->
        <button type="button" @click="setTab('review')"
                class="p-4 sm:p-5 rounded-3xl glass-panel border border-purple-500/30 text-left hover:border-purple-500/60 transition-all group relative overflow-hidden shadow-xs"
                :class="activeTab === 'review' ? 'ring-2 ring-purple-500/50 bg-purple-500/[0.04]' : ''">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">In Review</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-purple-600 dark:text-purple-400 mt-1">{{ $totalTurnedIn }}</h3>
                </div>
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-lg sm:text-xl">rule_folder</span>
                </span>
            </div>
            <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1 font-semibold truncate">
                <span>In review queue</span>
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </p>
        </button>

        <!-- Metric 5: Published Stories -->
        <button type="button" @click="setTab('published')"
                class="p-4 sm:p-5 rounded-3xl glass-panel border border-emerald-500/30 text-left hover:border-emerald-500/60 transition-all group relative overflow-hidden shadow-xs col-span-2 sm:col-span-1"
                :class="activeTab === 'published' ? 'ring-2 ring-emerald-500/50 bg-emerald-500/[0.04]' : ''">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Published</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-emerald-600 dark:text-emerald-400 mt-1">{{ $totalApproved }}</h3>
                </div>
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-lg sm:text-xl">verified</span>
                </span>
            </div>
            <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1 font-semibold truncate">
                <span>Live on Brighteen</span>
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </p>
        </button>
    </div>

    <!-- 3. SEARCH & REAL-TIME FILTER BAR -->
    <div class="p-3 sm:p-4 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 flex flex-col md:flex-row items-center justify-between gap-3 shadow-sm">
        <div class="relative w-full md:max-w-md">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input type="text" 
                   x-model="searchQuery" 
                   placeholder="Search briefs or drafts by topic, title, category or keywords..." 
                   class="w-full pl-10 pr-9 py-2 rounded-2xl bg-slate-100/80 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
            <button type="button" 
                    x-show="searchQuery.trim() !== ''" 
                    @click="searchQuery = ''" 
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white text-xs">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto justify-end flex-wrap text-xs">
            <!-- Active Sub-filter toggles (when viewing active tasks) -->
            <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10"
                 x-show="activeTab === 'active'">
                <button type="button" 
                        @click="activeSubFilter = 'all'" 
                        :class="activeSubFilter === 'all' ? 'bg-white dark:bg-surface-card text-primary font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="px-3 py-1 rounded-xl text-[11px] transition-all">
                    All Active ({{ $totalActive }})
                </button>
                <button type="button" 
                        @click="activeSubFilter = 'pending'" 
                        :class="activeSubFilter === 'pending' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="px-3 py-1 rounded-xl text-[11px] transition-all">
                    Pending ({{ $totalPending }})
                </button>
                <button type="button" 
                        @click="activeSubFilter = 'expired'" 
                        :class="activeSubFilter === 'expired' ? 'bg-rose-500 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="px-3 py-1 rounded-xl text-[11px] transition-all">
                    Overdue ({{ $totalExpired }})
                </button>
            </div>

            <!-- Drafts Sub-filter toggles (when viewing saved drafts) -->
            <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10"
                 x-show="activeTab === 'drafts'">
                <button type="button" 
                        @click="draftSubFilter = 'all'" 
                        :class="draftSubFilter === 'all' ? 'bg-white dark:bg-surface-card text-sky-600 dark:text-sky-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="px-2.5 sm:px-3 py-1 rounded-xl text-[11px] transition-all">
                    All Drafts ({{ $totalDrafts }})
                </button>
                <button type="button" 
                        @click="draftSubFilter = 'draft'" 
                        :class="draftSubFilter === 'draft' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="px-2.5 sm:px-3 py-1 rounded-xl text-[11px] transition-all">
                    WIP ({{ $draftOnlyCount }})
                </button>
                <button type="button" 
                        @click="draftSubFilter = 'pending_review'" 
                        :class="draftSubFilter === 'pending_review' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="px-2.5 sm:px-3 py-1 rounded-xl text-[11px] transition-all">
                    In Review ({{ $draftReviewCount }})
                </button>
                @if($draftRejectedCount > 0)
                    <button type="button" 
                            @click="draftSubFilter = 'rejected'" 
                            :class="draftSubFilter === 'rejected' ? 'bg-rose-500 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-2.5 sm:px-3 py-1 rounded-xl text-[11px] transition-all">
                        Revision ({{ $draftRejectedCount }})
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- 4. NAVIGATION CONTROLS: MOBILE COLLAPSIBLE HELPER BAR & DESKTOP TAB BAR -->

    <!-- Mobile Collapsible Sections Helper Bar (< 768px): Replaces cramped horizontal tabs -->
    <div class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-100/90 dark:bg-white/[0.04] border border-slate-200 dark:border-white/10 md:hidden text-xs">
        <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-bold px-1 min-w-0">
            <span class="material-symbols-outlined text-sm text-primary shrink-0">view_agenda</span>
            <span class="truncate">Workspace Sections</span>
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

    <!-- Desktop View (≥ 768px): Full-Width 4-Column Tab Bar -->
    <div class="hidden md:grid md:grid-cols-4 gap-3 p-2 rounded-3xl bg-slate-100/80 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10 shadow-sm">
        
        <!-- Desktop Tab 1: Active Tasks & Briefs -->
        <button type="button" @click="setTab('active')"
                class="p-4 rounded-2xl flex items-center justify-between text-left transition-all group"
                :class="activeTab === 'active' 
                    ? 'bg-white dark:bg-[#1a202c] shadow-lg border border-primary/40 ring-2 ring-primary/20' 
                    : 'hover:bg-white/60 dark:hover:bg-white/5 border border-transparent'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                      :class="activeTab === 'active' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-primary/10 text-primary'">
                    <span class="material-symbols-outlined text-xl">pending_actions</span>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">Active Briefs</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Pending & overdue tasks</div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-xl text-xs font-black shrink-0 transition-colors"
                  :class="activeTab === 'active' ? 'bg-primary text-white shadow-sm' : 'bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ $totalActive }}
            </span>
        </button>

        <!-- Desktop Tab 2: Saved Drafts (Unpublished Stories) -->
        <button type="button" @click="setTab('drafts')"
                class="p-4 rounded-2xl flex items-center justify-between text-left transition-all group"
                :class="activeTab === 'drafts' 
                    ? 'bg-white dark:bg-[#1a202c] shadow-lg border border-sky-500/40 ring-2 ring-sky-500/20' 
                    : 'hover:bg-white/60 dark:hover:bg-white/5 border border-transparent'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                      :class="activeTab === 'drafts' ? 'bg-sky-500 text-white shadow-md shadow-sky-500/25' : 'bg-sky-500/10 text-sky-500 dark:text-sky-400'">
                    <span class="material-symbols-outlined text-xl">edit_document</span>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">Saved Drafts</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">WIP & unpublished stories</div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-xl text-xs font-black shrink-0 transition-colors"
                  :class="activeTab === 'drafts' ? 'bg-sky-500 text-white shadow-sm' : 'bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ $totalDrafts }}
            </span>
        </button>

        <!-- Desktop Tab 3: Turned-In & Under Editorial Review -->
        <button type="button" @click="setTab('review')"
                class="p-4 rounded-2xl flex items-center justify-between text-left transition-all group"
                :class="activeTab === 'review' 
                    ? 'bg-white dark:bg-[#1a202c] shadow-lg border border-purple-600/40 ring-2 ring-purple-600/20' 
                    : 'hover:bg-white/60 dark:hover:bg-white/5 border border-transparent'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                      :class="activeTab === 'review' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/25' : 'bg-purple-500/10 text-purple-400'">
                    <span class="material-symbols-outlined text-xl">rule_folder</span>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">Under Review</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Submitted drafts in queue</div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-xl text-xs font-black shrink-0 transition-colors"
                  :class="activeTab === 'review' ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ $totalTurnedIn }}
            </span>
        </button>

        <!-- Desktop Tab 4: Completed & Published Stories -->
        <button type="button" @click="setTab('published')"
                class="p-4 rounded-2xl flex items-center justify-between text-left transition-all group"
                :class="activeTab === 'published' 
                    ? 'bg-white dark:bg-[#1a202c] shadow-lg border border-emerald-600/40 ring-2 ring-emerald-600/20' 
                    : 'hover:bg-white/60 dark:hover:bg-white/5 border border-transparent'">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                      :class="activeTab === 'published' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25' : 'bg-emerald-500/10 text-emerald-400'">
                    <span class="material-symbols-outlined text-xl">verified</span>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-black text-slate-900 dark:text-white truncate">Published Portfolio</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Live approved dispatches</div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-xl text-xs font-black shrink-0 transition-colors"
                  :class="activeTab === 'published' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ $totalApproved }}
            </span>
        </button>
    </div>

    <!-- 5. CONTENT AREA WRAPPER WITH VERTICALLY STACKED COLLAPSIBLE SECTIONS -->
    <div class="space-y-4 sm:space-y-6">

        <!-- ========================================== -->
        <!-- SECTION 1: ACTIVE TASKS & BRIEFS           -->
        <!-- ========================================== -->
        <div id="active-section" 
             class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 overflow-hidden transition-all duration-300 shadow-sm"
             :class="{
                 'ring-2 ring-primary/40 border-primary/40 shadow-md': isMobile && mobileSections.active,
                 'md:hidden': activeTab !== 'active'
             }">
            
            <!-- Mobile Collapsible Header Button (< 768px) -->
            <button type="button" 
                    @click="toggleMobileSection('active')"
                    class="w-full p-4 sm:p-5 flex items-center justify-between text-left transition-colors md:hidden select-none focus:outline-none"
                    :class="mobileSections.active 
                        ? 'bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-b border-slate-200/80 dark:border-white/10' 
                        : 'bg-white/60 dark:bg-surface-card/60 hover:bg-slate-100/80 dark:hover:bg-white/5'">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md transition-transform"
                          :class="mobileSections.active ? 'bg-primary text-white shadow-primary/30 scale-105' : 'bg-primary/10 text-primary'">
                        <span class="material-symbols-outlined text-xl">pending_actions</span>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white truncate">Active Briefs & To-Do</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 transition-colors"
                                  :class="mobileSections.active ? 'bg-primary text-white shadow-xs' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                {{ $totalActive }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Pending research, writing & overdue briefs</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-[11px] font-bold text-primary hidden xs:inline" x-text="mobileSections.active ? 'Collapse' : 'Expand'"></span>
                    <span class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 transition-transform duration-200"
                          :class="mobileSections.active ? 'rotate-180 bg-primary/20 text-primary' : ''">
                        <span class="material-symbols-outlined text-base">expand_more</span>
                    </span>
                </div>
            </button>

            <!-- Section Body Content -->
            <div id="active" 
                 x-show="!isMobile || mobileSections.active" 
                 class="p-4 sm:p-6 lg:p-8 space-y-6">

                <!-- Desktop Section Top Bar -->
                <div class="hidden md:flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">pending_actions</span>
                        </span>
                        <div>
                            <h2 class="text-lg font-black font-display text-slate-900 dark:text-white">Active Assignment Briefs</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Select an assignment to draft your story and turn it in directly to your assigned editor.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-primary/10 text-primary border border-primary/20">
                        {{ $totalActive }} Active Task{{ $totalActive === 1 ? '' : 's' }}
                    </span>
                </div>

                <!-- Assignment Cards List -->
                @if(empty($activeAssignments))
                    <div class="p-10 sm:p-14 rounded-3xl glass-panel border border-dashed border-slate-300 dark:border-white/10 text-center space-y-4">
                        <span class="w-16 h-16 rounded-3xl bg-primary/10 text-primary mx-auto flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined text-3xl">task_alt</span>
                        </span>
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">All Caught Up! No Active Briefs</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                                You do not have any pending or overdue assignments from editors right now. Feel free to pitch an independent story to the workbench!
                            </p>
                        </div>
                        <div class="pt-2">
                            <a href="{{ url('articles/create') }}" 
                               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/25 transition-all">
                                <span class="material-symbols-outlined text-base">edit_note</span>
                                <span>Pitch Independent Story</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($activeAssignments as $asg)
                            <div id="asg-{{ $asg['id'] }}"
                                 x-show="matchesAssignment('{{ addslashes($asg['topic']) }}', '{{ addslashes($asg['description']) }}', '{{ addslashes($asg['category_name']) }}', '{{ addslashes($asg['editor_name']) }}', '') && matchesActiveFilter('{{ $asg['effective_status'] }}')"
                                 class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/90 dark:border-white/10 hover:border-primary/40 transition-all shadow-sm hover:shadow-md space-y-4 relative overflow-hidden group">

                                <!-- Overdue Accent Ribbon (if expired) -->
                                @if($asg['effective_status'] === 'expired')
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 to-amber-500"></div>
                                @else
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary to-indigo-500 opacity-60"></div>
                                @endif

                                <!-- Top Row: Category Tag, ID & Status Badge -->
                                <div class="flex items-center justify-between flex-wrap gap-2 pt-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs"
                                              style="background-color: {{ $asg['category_color'] }}15; border: 1px solid {{ $asg['category_color'] }}40; color: {{ $asg['category_color'] }};">
                                            <span class="material-symbols-outlined text-sm">{{ $asg['category_icon'] ?: 'tag' }}</span>
                                            <span>{{ $asg['category_name'] }}</span>
                                        </span>
                                        <span class="text-xs text-slate-400 font-mono bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-white/10">
                                            #BRIEF-{{ $asg['id'] }}
                                        </span>
                                    </div>

                                    <!-- Status Badge -->
                                    <div>
                                        @if($asg['effective_status'] === 'expired')
                                            <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center gap-1.5 shadow-xs animate-pulse">
                                                <span class="material-symbols-outlined text-xs">warning</span>
                                                <span>Overdue &bull; Submission Open</span>
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center gap-1.5 shadow-xs">
                                                <span class="material-symbols-outlined text-xs">hourglass_top</span>
                                                <span>Ready to Write</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Middle: Topic Title & Briefing Guidelines -->
                                <div class="space-y-2">
                                    <h3 class="text-base sm:text-lg font-black font-display text-slate-900 dark:text-white leading-snug group-hover:text-primary transition-colors">
                                        {{ $asg['topic'] }}
                                    </h3>
                                    
                                    @if(!empty($asg['description']))
                                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">format_quote</span>
                                                <span>Editorial Briefing & Angles</span>
                                            </div>
                                            {{ $asg['description'] }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Bottom Row: Metadata & Action -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-white/5">
                                    
                                    <!-- Meta Pills -->
                                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
                                        <div class="flex items-center gap-1.5">
                                            @if(!empty($asg['editor_avatar']))
                                                <img src="{{ $asg['editor_avatar'] }}" alt="{{ $asg['editor_name'] }}" class="w-5 h-5 rounded-full object-cover">
                                            @else
                                                <span class="w-5 h-5 rounded-full bg-primary/20 text-primary flex items-center justify-center text-[10px] font-black">
                                                    {{ strtoupper(substr($asg['editor_name'] ?? 'E', 0, 1)) }}
                                                </span>
                                            @endif
                                            <span>Assigned by <strong class="text-slate-800 dark:text-slate-200">{{ $asg['editor_name'] ?? 'Editor' }}</strong></span>
                                        </div>

                                        <span class="hidden sm:inline text-slate-300 dark:text-slate-600">&bull;</span>

                                        <div class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-slate-400">alarm</span>
                                            <span>Deadline:</span>
                                            <strong class="{{ $asg['is_overdue'] ? 'text-rose-500 dark:text-rose-400 font-black' : 'text-amber-500 dark:text-amber-400 font-bold' }}">
                                                {{ date('M j, Y - H:i', strtotime($asg['submission_deadline'])) }}
                                            </strong>
                                        </div>

                                        <span class="hidden sm:inline text-slate-300 dark:text-slate-600">&bull;</span>

                                        <div class="flex items-center gap-1 text-[11px] text-slate-400">
                                            <span class="material-symbols-outlined text-xs">calendar_today</span>
                                            <span>Publish target: {{ date('M j, Y', strtotime($asg['publishing_schedule'])) }}</span>
                                        </div>
                                    </div>

                                    <!-- Action Button: Write Article -->
                                    <div class="shrink-0 w-full sm:w-auto">
                                        <a href="{{ url('articles/create?assignment_id=' . $asg['id']) }}" 
                                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white font-bold text-xs shadow-md shadow-primary/25 transition-all transform hover:-translate-y-0.5">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                            <span>Write & Turn In Story</span>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>


        <!-- ========================================== -->
        <!-- SECTION 2: MY SAVED DRAFTS (UNPUBLISHED)   -->
        <!-- ========================================== -->
        <div id="drafts-section" 
             class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 overflow-hidden transition-all duration-300 shadow-sm"
             :class="{
                 'ring-2 ring-sky-500/40 border-sky-500/40 shadow-md': isMobile && mobileSections.drafts,
                 'md:hidden': activeTab !== 'drafts'
             }">
            
            <!-- Mobile Collapsible Header Button (< 768px) -->
            <button type="button" 
                    @click="toggleMobileSection('drafts')"
                    class="w-full p-4 sm:p-5 flex items-center justify-between text-left transition-colors md:hidden select-none focus:outline-none"
                    :class="mobileSections.drafts 
                        ? 'bg-gradient-to-r from-sky-500/10 via-sky-500/5 to-transparent border-b border-slate-200/80 dark:border-white/10' 
                        : 'bg-white/60 dark:bg-surface-card/60 hover:bg-slate-100/80 dark:hover:bg-white/5'">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md transition-transform"
                          :class="mobileSections.drafts ? 'bg-sky-500 text-white shadow-sky-500/30 scale-105' : 'bg-sky-500/10 text-sky-500 dark:text-sky-400'">
                        <span class="material-symbols-outlined text-xl">edit_document</span>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white truncate">Saved Drafts</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 transition-colors"
                                  :class="mobileSections.drafts ? 'bg-sky-500 text-white shadow-xs' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                {{ $totalDrafts }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">WIP stories, reviews & revision requests</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-[11px] font-bold text-sky-500 hidden xs:inline" x-text="mobileSections.drafts ? 'Collapse' : 'Expand'"></span>
                    <span class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 transition-transform duration-200"
                          :class="mobileSections.drafts ? 'rotate-180 bg-sky-500/20 text-sky-400' : ''">
                        <span class="material-symbols-outlined text-base">expand_more</span>
                    </span>
                </div>
            </button>

            <!-- Section Body Content -->
            <div id="drafts" 
                 x-show="!isMobile || mobileSections.drafts" 
                 class="p-4 sm:p-6 lg:p-8 space-y-6">

                <!-- Desktop Section Top Bar -->
                <div class="hidden md:flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-500 dark:text-sky-400 flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">edit_document</span>
                        </span>
                        <div>
                            <h2 class="text-lg font-black font-display text-slate-900 dark:text-white">Saved Drafts & Work In Progress</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">All your unfinished stories, queued submissions, and returned drafts. Pick up right where you left off!</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="px-3 py-1 rounded-xl text-xs font-black bg-sky-500/10 text-sky-500 dark:text-sky-400 border border-sky-500/20">
                            {{ $totalDrafts }} Unpublished
                        </span>
                        <a href="{{ url('articles/create') }}" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-xs transition-all">
                            <span class="material-symbols-outlined text-sm">add</span>
                            <span>New Story</span>
                        </a>
                    </div>
                </div>

                @if(empty($myDrafts))
                    <div class="p-10 sm:p-14 rounded-3xl glass-panel border border-dashed border-slate-300 dark:border-white/10 text-center space-y-4">
                        <span class="w-16 h-16 rounded-3xl bg-sky-500/10 text-sky-500 dark:text-sky-400 mx-auto flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined text-3xl">note_alt</span>
                        </span>
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">No Saved Drafts Yet</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                                You don't have any saved drafts or unpublished articles right now. Every time you save your work or submit for review, it will be securely accessible here.
                            </p>
                        </div>
                        <div class="pt-2">
                            <a href="{{ url('articles/create') }}" 
                               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 text-white text-xs font-bold shadow-md shadow-primary/25 hover:from-primary-hover hover:to-indigo-700 transition-all">
                                <span class="material-symbols-outlined text-base">edit_note</span>
                                <span>Start Writing a Story</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($myDrafts as $draft)
                            <div id="draft-{{ $draft['id'] }}"
                                 x-show="matchesDraft('{{ addslashes($draft['title']) }}', '{{ addslashes($draft['excerpt'] ?? '') }}', '{{ addslashes($draft['category_name']) }}', '{{ $draft['status'] }}', '{{ addslashes($draft['linked_assignment_topic'] ?? '') }}')"
                                 class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/90 dark:border-white/10 hover:border-sky-500/40 transition-all shadow-sm space-y-4 relative overflow-hidden group">

                                <!-- Status Color Ribbon -->
                                @if($draft['status'] === 'draft')
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-sky-400"></div>
                                @elseif($draft['status'] === 'pending_review')
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                                @elseif($draft['status'] === 'rejected')
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 to-pink-500"></div>
                                @else
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-slate-300 dark:bg-slate-700"></div>
                                @endif

                                <!-- Top Row: Category Tag, Linked Brief & Status Badge -->
                                <div class="flex items-center justify-between flex-wrap gap-2 pt-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs"
                                              style="background-color: {{ $draft['category_color'] }}15; border: 1px solid {{ $draft['category_color'] }}40; color: {{ $draft['category_color'] }};">
                                            <span class="material-symbols-outlined text-sm">{{ $draft['category_icon'] ?: 'tag' }}</span>
                                            <span>{{ $draft['category_name'] }}</span>
                                        </span>

                                        <span class="text-xs text-slate-400 font-mono bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-white/10">
                                            #DRAFT-{{ $draft['id'] }}
                                        </span>

                                        @if(!empty($draft['linked_assignment_id']))
                                            <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 flex items-center gap-1"
                                                  title="Linked to assignment brief #BRIEF-{{ $draft['linked_assignment_id'] }}">
                                                <span class="material-symbols-outlined text-xs">link</span>
                                                <span class="truncate max-w-[150px] sm:max-w-xs">Brief #{{ $draft['linked_assignment_id'] }}: {{ $draft['linked_assignment_topic'] }}</span>
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Status Pill -->
                                    <div>
                                        @if($draft['status'] === 'draft')
                                            <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center gap-1.5 shadow-xs">
                                                <span class="material-symbols-outlined text-xs">edit_note</span>
                                                <span>Work In Progress</span>
                                            </span>
                                        @elseif($draft['status'] === 'pending_review')
                                            <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/30 flex items-center gap-1.5 shadow-xs">
                                                <span class="w-2 h-2 rounded-full bg-purple-500 animate-ping"></span>
                                                <span>In Editorial Review</span>
                                            </span>
                                        @elseif($draft['status'] === 'rejected')
                                            <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center gap-1.5 shadow-xs">
                                                <span class="material-symbols-outlined text-xs">refresh</span>
                                                <span>Needs Revision</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Middle: Draft Title & Excerpt -->
                                <div class="space-y-2">
                                    <h3 class="text-base sm:text-lg font-black font-display text-slate-900 dark:text-white leading-snug group-hover:text-sky-500 transition-colors">
                                        <a href="{{ url('articles/' . $draft['id'] . '/edit') }}" class="hover:underline">
                                            {{ !empty($draft['title']) ? $draft['title'] : 'Untitled Story Draft' }}
                                        </a>
                                    </h3>

                                    @php
                                        $snippet = !empty($draft['excerpt']) ? $draft['excerpt'] : strip_tags($draft['content']);
                                        $snippet = mb_strimwidth($snippet, 0, 150, '...');
                                    @endphp

                                    @if(!empty($snippet))
                                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                            {{ $snippet }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Bottom Row: Metadata & Actions -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-3 border-t border-slate-100 dark:border-white/5">
                                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
                                        <div class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-slate-400">schedule</span>
                                            <span>Saved: <strong class="text-slate-700 dark:text-slate-300">{{ date('M j, Y \a\t H:i', strtotime($draft['updated_at'])) }}</strong></span>
                                        </div>

                                        <span class="hidden sm:inline text-slate-300 dark:text-slate-600">&bull;</span>

                                        <div class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-slate-400">history_edu</span>
                                            <span>Approx. {{ max(1, (int)ceil(str_word_count(strip_tags($draft['content'])) / 180)) }} min read</span>
                                        </div>
                                    </div>

                                    <!-- Actions: Resume Writing, Preview, Delete -->
                                    <div class="flex items-center gap-2 self-end sm:self-auto flex-wrap">
                                        {{-- Delete Draft Form --}}
                                        <form action="{{ url('articles/' . $draft['id'] . '/delete') }}" 
                                              method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this draft? This action cannot be undone.');" 
                                              class="inline">
                                            <button type="submit" 
                                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 border border-transparent hover:border-rose-500/30 transition-all" 
                                                    title="Delete this draft">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </form>

                                        {{-- Preview Draft --}}
                                        <a href="{{ url('articles/' . $draft['slug']) }}" 
                                           target="_blank"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-white/10 text-xs font-bold transition-all shadow-xs"
                                           title="Preview how this draft will look">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                            <span>Preview</span>
                                        </a>

                                        {{-- Resume Editing --}}
                                        <a href="{{ url('articles/' . $draft['id'] . '/edit') }}" 
                                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold shadow-xs shadow-sky-500/20 transition-all">
                                            <span class="material-symbols-outlined text-sm">edit_note</span>
                                            <span>Resume Writing</span>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>


        <!-- ========================================== -->
        <!-- SECTION 3: UNDER EDITORIAL REVIEW          -->
        <!-- ========================================== -->
        <div id="review-section" 
             class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 overflow-hidden transition-all duration-300 shadow-sm"
             :class="{
                 'ring-2 ring-purple-500/40 border-purple-500/40 shadow-md': isMobile && mobileSections.review,
                 'md:hidden': activeTab !== 'review'
             }">
            
            <!-- Mobile Collapsible Header Button (< 768px) -->
            <button type="button" 
                    @click="toggleMobileSection('review')"
                    class="w-full p-4 sm:p-5 flex items-center justify-between text-left transition-colors md:hidden select-none focus:outline-none"
                    :class="mobileSections.review 
                        ? 'bg-gradient-to-r from-purple-500/10 via-purple-500/5 to-transparent border-b border-slate-200/80 dark:border-white/10' 
                        : 'bg-white/60 dark:bg-surface-card/60 hover:bg-slate-100/80 dark:hover:bg-white/5'">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md transition-transform"
                          :class="mobileSections.review ? 'bg-purple-600 text-white shadow-purple-600/30 scale-105' : 'bg-purple-500/10 text-purple-400'">
                        <span class="material-symbols-outlined text-xl">rule_folder</span>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white truncate">Under Editorial Review</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 transition-colors"
                                  :class="mobileSections.review ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                {{ $totalTurnedIn }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Submitted drafts undergoing proofreading & editor curation</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-[11px] font-bold text-purple-500 hidden xs:inline" x-text="mobileSections.review ? 'Collapse' : 'Expand'"></span>
                    <span class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 transition-transform duration-200"
                          :class="mobileSections.review ? 'rotate-180 bg-purple-500/20 text-purple-400' : ''">
                        <span class="material-symbols-outlined text-base">expand_more</span>
                    </span>
                </div>
            </button>

            <!-- Section Body Content -->
            <div id="review" 
                 x-show="!isMobile || mobileSections.review" 
                 class="p-4 sm:p-6 lg:p-8 space-y-6">

                <!-- Desktop Section Top Bar -->
                <div class="hidden md:flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">rule_folder</span>
                        </span>
                        <div>
                            <h2 class="text-lg font-black font-display text-slate-900 dark:text-white">Editorial Review Queue</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Drafts turned in for review. You can make adjustments to your draft while editors evaluate it.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-purple-500/10 text-purple-400 border border-purple-500/20">
                        {{ $totalTurnedIn }} In Review
                    </span>
                </div>

                @if(empty($reviewAssignments))
                    <div class="p-10 sm:p-14 rounded-3xl glass-panel border border-dashed border-slate-300 dark:border-white/10 text-center space-y-4">
                        <span class="w-16 h-16 rounded-3xl bg-purple-500/10 text-purple-400 mx-auto flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined text-3xl">fact_check</span>
                        </span>
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">No Stories in Review Right Now</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                                Once you turn in an assigned brief or draft, it will appear here so you can monitor editor evaluation and progress.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($reviewAssignments as $asg)
                            <div id="asg-{{ $asg['id'] }}"
                                 x-show="matchesAssignment('{{ addslashes($asg['topic']) }}', '{{ addslashes($asg['description']) }}', '{{ addslashes($asg['category_name']) }}', '{{ addslashes($asg['editor_name']) }}', '{{ addslashes($asg['article_title'] ?? '') }}')"
                                 class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/90 dark:border-white/10 hover:border-purple-500/40 transition-all shadow-sm space-y-4 relative overflow-hidden group">

                                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>

                                <!-- Top Row: Category Tag, ID & Status Badge -->
                                <div class="flex items-center justify-between flex-wrap gap-2 pt-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs"
                                              style="background-color: {{ $asg['category_color'] }}15; border: 1px solid {{ $asg['category_color'] }}40; color: {{ $asg['category_color'] }};">
                                            <span class="material-symbols-outlined text-sm">{{ $asg['category_icon'] ?: 'tag' }}</span>
                                            <span>{{ $asg['category_name'] }}</span>
                                        </span>
                                        <span class="text-xs text-slate-400 font-mono bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-white/10">
                                            #BRIEF-{{ $asg['id'] }}
                                        </span>
                                    </div>

                                    <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/30 flex items-center gap-1.5 shadow-xs">
                                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-ping"></span>
                                        <span>Turned In &bull; Under Review</span>
                                    </span>
                                </div>

                                <!-- Middle: Topic & Submitted Article Title -->
                                <div class="space-y-2">
                                    <h3 class="text-base sm:text-lg font-black font-display text-slate-900 dark:text-white leading-snug">
                                        {{ $asg['topic'] }}
                                    </h3>
                                    
                                    @if(!empty($asg['article_title']))
                                        <div class="p-3.5 rounded-2xl bg-purple-500/[0.04] border border-purple-500/20 text-xs text-slate-700 dark:text-slate-300 flex items-start gap-2.5">
                                            <span class="material-symbols-outlined text-base text-purple-500 shrink-0 mt-0.5">article</span>
                                            <div>
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-500 dark:text-purple-400 block">Draft Title Submitted</span>
                                                <strong class="text-slate-900 dark:text-white">{{ $asg['article_title'] }}</strong>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Bottom Row: Metadata & Action -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-white/5">
                                    
                                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
                                        <div class="flex items-center gap-1 text-purple-600 dark:text-purple-400 font-semibold">
                                            <span class="material-symbols-outlined text-sm">done_all</span>
                                            <span>Turned in: {{ !empty($asg['turned_in_at']) ? date('M j, Y \a\t H:i', strtotime($asg['turned_in_at'])) : 'Recently' }}</span>
                                        </div>

                                        <span class="hidden sm:inline text-slate-300 dark:text-slate-600">&bull;</span>

                                        <div class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-slate-400">person</span>
                                            <span>Evaluating Editor: <strong class="text-slate-800 dark:text-slate-200">{{ $asg['editor_name'] ?? 'Desk Editor' }}</strong></span>
                                        </div>
                                    </div>

                                    <!-- Action: Edit Draft -->
                                    <div class="shrink-0 w-full sm:w-auto flex items-center gap-2">
                                        @if(!empty($asg['article_id']))
                                            <a href="{{ url('articles/' . $asg['article_id'] . '/edit') }}" 
                                               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-purple-50 dark:bg-purple-950/30 hover:bg-purple-100 dark:hover:bg-purple-900/40 text-purple-600 dark:text-purple-300 border border-purple-500/20 text-xs font-bold transition-all shadow-xs">
                                                <span class="material-symbols-outlined text-sm">edit_note</span>
                                                <span>Edit Draft Story</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>


        <!-- ========================================== -->
        <!-- SECTION 4: PUBLISHED PORTFOLIO             -->
        <!-- ========================================== -->
        <div id="published-section" 
             class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 overflow-hidden transition-all duration-300 shadow-sm"
             :class="{
                 'ring-2 ring-emerald-500/40 border-emerald-500/40 shadow-md': isMobile && mobileSections.published,
                 'md:hidden': activeTab !== 'published'
             }">
            
            <!-- Mobile Collapsible Header Button (< 768px) -->
            <button type="button" 
                    @click="toggleMobileSection('published')"
                    class="w-full p-4 sm:p-5 flex items-center justify-between text-left transition-colors md:hidden select-none focus:outline-none"
                    :class="mobileSections.published 
                        ? 'bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent border-b border-slate-200/80 dark:border-white/10' 
                        : 'bg-white/60 dark:bg-surface-card/60 hover:bg-slate-100/80 dark:hover:bg-white/5'">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md transition-transform"
                          :class="mobileSections.published ? 'bg-emerald-600 text-white shadow-emerald-600/30 scale-105' : 'bg-emerald-500/10 text-emerald-400'">
                        <span class="material-symbols-outlined text-xl">verified</span>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white truncate">Published Portfolio</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 transition-colors"
                                  :class="mobileSections.published ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                {{ $totalApproved }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Approved dispatches currently live on the public feed</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-[11px] font-bold text-emerald-500 hidden xs:inline" x-text="mobileSections.published ? 'Collapse' : 'Expand'"></span>
                    <span class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 transition-transform duration-200"
                          :class="mobileSections.published ? 'rotate-180 bg-emerald-500/20 text-emerald-400' : ''">
                        <span class="material-symbols-outlined text-base">expand_more</span>
                    </span>
                </div>
            </button>

            <!-- Section Body Content -->
            <div id="published" 
                 x-show="!isMobile || mobileSections.published" 
                 class="p-4 sm:p-6 lg:p-8 space-y-6">

                <!-- Desktop Section Top Bar -->
                <div class="hidden md:flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">verified</span>
                        </span>
                        <div>
                            <h2 class="text-lg font-black font-display text-slate-900 dark:text-white">Published Dispatches & Archives</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Your assigned stories that have been reviewed, approved, and published to Brighteen readers.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        {{ $totalApproved }} Live
                    </span>
                </div>

                @if(empty($publishedAssignments))
                    <div class="p-10 sm:p-14 rounded-3xl glass-panel border border-dashed border-slate-300 dark:border-white/10 text-center space-y-4">
                        <span class="w-16 h-16 rounded-3xl bg-emerald-500/10 text-emerald-400 mx-auto flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined text-3xl">auto_stories</span>
                        </span>
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">No Published Stories Yet</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                                Complete and turn in your active assignments. Once the editorial team approves your story, it will appear here in your live portfolio!
                            </p>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($publishedAssignments as $asg)
                            <div id="asg-{{ $asg['id'] }}"
                                 x-show="matchesAssignment('{{ addslashes($asg['topic']) }}', '{{ addslashes($asg['description']) }}', '{{ addslashes($asg['category_name']) }}', '{{ addslashes($asg['editor_name']) }}', '{{ addslashes($asg['article_title'] ?? '') }}')"
                                 class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/90 dark:border-white/10 hover:border-emerald-500/40 transition-all shadow-sm space-y-4 relative overflow-hidden group">

                                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>

                                <!-- Top Row: Category Tag, ID & Status Badge -->
                                <div class="flex items-center justify-between flex-wrap gap-2 pt-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs"
                                              style="background-color: {{ $asg['category_color'] }}15; border: 1px solid {{ $asg['category_color'] }}40; color: {{ $asg['category_color'] }};">
                                            <span class="material-symbols-outlined text-sm">{{ $asg['category_icon'] ?: 'tag' }}</span>
                                            <span>{{ $asg['category_name'] }}</span>
                                        </span>
                                        <span class="text-xs text-slate-400 font-mono bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-white/10">
                                            #BRIEF-{{ $asg['id'] }}
                                        </span>
                                    </div>

                                    <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5 shadow-xs">
                                        <span class="material-symbols-outlined text-xs">verified</span>
                                        <span>Approved & Published Live</span>
                                    </span>
                                </div>

                                <!-- Middle: Topic & Published Title -->
                                <div class="space-y-2">
                                    <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">
                                        Original Brief: <span class="text-slate-600 dark:text-slate-300 font-normal normal-case">{{ $asg['topic'] }}</span>
                                    </div>
                                    <h3 class="text-base sm:text-lg font-black font-display text-slate-900 dark:text-white leading-snug">
                                        {{ !empty($asg['article_title']) ? $asg['article_title'] : $asg['topic'] }}
                                    </h3>
                                </div>

                                <!-- Bottom Row: Metadata & Action -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-white/5">
                                    
                                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
                                        <div class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold">
                                            <span class="material-symbols-outlined text-sm">schedule</span>
                                            <span>Published: {{ !empty($asg['article_published_at']) ? date('M j, Y', strtotime($asg['article_published_at'])) : date('M j, Y', strtotime($asg['updated_at'])) }}</span>
                                        </div>

                                        <span class="hidden sm:inline text-slate-300 dark:text-slate-600">&bull;</span>

                                        <div class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-slate-400">person</span>
                                            <span>Approved by: <strong class="text-slate-800 dark:text-slate-200">{{ $asg['editor_name'] ?? 'Editorial Desk' }}</strong></span>
                                        </div>
                                    </div>

                                    <!-- Action: View Live Story -->
                                    <div class="shrink-0 w-full sm:w-auto">
                                        @if(!empty($asg['article_slug']))
                                            <a href="{{ url('articles/' . $asg['article_slug']) }}" 
                                               class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-xs font-bold transition-all shadow-xs group-hover:scale-105">
                                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                                                <span>View Live Story</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>

    </div>

</div>
@endsection
