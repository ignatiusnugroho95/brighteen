@extends('layouts.app')

@section('content')
<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" 
     x-data="{
         activeTab: (function() {
             const hash = window.location.hash ? window.location.hash.replace('#', '') : '';
             if (['readers', 'applications', 'registered-readers'].includes(hash)) return 'readers';
             if (['settings', 'system'].includes(hash)) return 'settings';
             return 'users';
         })(),
         setTab(tab) {
             this.activeTab = tab;
             if (window.history && window.history.pushState) {
                 window.history.pushState(null, null, '#' + tab);
             } else {
                 window.location.hash = '#' + tab;
             }
         },
         staffSearch: '',
         readerSearch: '',
         matchesStaff(name, email) {
             if (!this.staffSearch || !this.staffSearch.trim()) return true;
             const q = this.staffSearch.toLowerCase();
             return (name && name.toLowerCase().includes(q)) || (email && email.toLowerCase().includes(q));
         },
         matchesReader(name, email, inst) {
             if (!this.readerSearch || !this.readerSearch.trim()) return true;
             const q = this.readerSearch.toLowerCase();
             return (name && name.toLowerCase().includes(q)) || (email && email.toLowerCase().includes(q)) || (inst && inst.toLowerCase().includes(q));
         },
         rejectModalOpen: false,
         rejectAppId: null,
         rejectApplicantName: '',
         openReject(id, name) {
             this.rejectAppId = id;
             this.rejectApplicantName = name;
             this.rejectModalOpen = true;
         }
     }">

    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-secondary-light uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-base">shield_person</span>
                <span>Executive Governance</span>
            </div>
            <h1 class="text-3xl font-black font-display text-slate-900 dark:text-white">Platform Management & Metrics</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <a href="{{ url('about/edit') }}" class="px-4 py-2.5 rounded-2xl bg-white dark:bg-surface-card hover:bg-slate-100 text-xs font-bold border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">info</span>
                <span>Edit About Us</span>
            </a>
            <a href="{{ url('dashboard/editorial') }}" class="px-4 py-2.5 rounded-2xl bg-white dark:bg-surface-card hover:bg-slate-100 text-xs font-bold border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">rule</span>
                <span>Editorial Queue</span>
            </a>
            <a href="{{ url('articles/create') }}" class="px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-lg shadow-primary/25 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">add</span>
                <span>Draft New Piece</span>
            </a>
        </div>
    </div>

    <!-- Notification Alert -->
    @if(!empty($notice))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold flex items-center justify-between gap-2 shadow-sm animate-in fade-in">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span>
                    @if($notice === 'role_updated') User RBAC role successfully updated!
                    @elseif($notice === 'application_approved') Contributor application approved! The reader has been promoted to Community Contributor.
                    @elseif($notice === 'application_rejected') Contributor application rejected with editorial feedback recorded.
                    @elseif($notice === 'settings_saved') Platform system settings saved successfully.
                    @elseif($notice === 'article_deleted') Article permanently deleted from database.
                    @else Action completed successfully.
                    @endif
                </span>
            </div>
            <a href="{{ url('dashboard/management') }}" class="text-xs font-bold underline hover:text-emerald-300">Dismiss</a>
        </div>
    @endif

    <!-- 1. HIGH LEVEL PLATFORM METRICS (Clickable Shortcuts) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 rounded-3xl glass-panel border border-primary/30">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Dispatches</span>
            <h3 class="text-3xl font-black font-display text-primary mt-1">{{ $metrics['total_articles'] }}</h3>
            <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-2">
                <span class="text-emerald-400">{{ $metrics['published'] }} Live</span> &bull; 
                <span class="text-amber-400">{{ $metrics['pending'] }} Review</span>
            </div>
        </div>

        <div class="p-6 rounded-3xl glass-panel border border-secondary/30">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pageviews</span>
            <h3 class="text-3xl font-black font-display text-secondary-light mt-1">{{ number_format($metrics['total_views']) }}</h3>
            <p class="text-[11px] text-slate-400 mt-2">Aggregated across all stories</p>
        </div>

        <!-- Staff & Creators Metric Button -->
        <button type="button" @click="setTab('users')"
                class="p-6 rounded-3xl glass-panel border border-indigo-500/30 text-left hover:border-indigo-500/60 transition-all group relative overflow-hidden"
                :class="activeTab === 'users' ? 'ring-2 ring-indigo-500/50 bg-indigo-500/[0.04]' : ''">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Staff & Creators</span>
                    <h3 class="text-3xl font-black font-display text-indigo-400 mt-1">{{ $metrics['total_staff'] ?? count($staffUsers) }}</h3>
                </div>
                <span class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-xl">badge</span>
                </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 flex items-center gap-1 font-semibold">
                <span>Management, Editors, Writers</span>
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </p>
        </button>

        <!-- Reader Administration Metric Button -->
        <button type="button" @click="setTab('readers')"
                class="p-6 rounded-3xl glass-panel border border-tertiary/30 text-left hover:border-tertiary/60 transition-all group relative overflow-hidden"
                :class="activeTab === 'readers' ? 'ring-2 ring-tertiary/50 bg-tertiary/[0.04]' : ''">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Registered Readers</span>
                    <h3 class="text-3xl font-black font-display text-tertiary-light mt-1">{{ $metrics['total_readers'] ?? count($readers) }}</h3>
                </div>
                <span class="w-10 h-10 rounded-2xl bg-tertiary/10 text-tertiary-light flex items-center justify-center group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-xl">group</span>
                </span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px]">
                <span class="text-slate-400 font-semibold">Reader Administration</span>
                @if($pendingApplicationsCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-400 animate-pulse">
                        {{ $pendingApplicationsCount }} Pending
                    </span>
                @endif
            </div>
        </button>
    </div>

    <!-- MAIN MANAGEMENT NAVIGATION TABS -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-white/10 pb-4 overflow-x-auto">
        <!-- Tab 1: Staff & Creator Administration -->
        <button type="button" @click="setTab('users')"
                class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-2.5 transition-all whitespace-nowrap shadow-sm"
                :class="activeTab === 'users' 
                    ? 'bg-primary text-white shadow-primary/25 shadow-md font-black' 
                    : 'bg-white dark:bg-surface-card hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10'">
            <span class="material-symbols-outlined text-base">manage_accounts</span>
            <span>User Administration (Staff & Creators)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                  :class="activeTab === 'users' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ count($staffUsers) }}
            </span>
        </button>

        <!-- Tab 2: Reader Administration (Registered Readers & Contributor Applications) -->
        <button type="button" @click="setTab('readers')"
                class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-2.5 transition-all whitespace-nowrap shadow-sm"
                :class="activeTab === 'readers' 
                    ? 'bg-purple-600 text-white shadow-purple-600/25 shadow-md font-black' 
                    : 'bg-white dark:bg-surface-card hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10'">
            <span class="material-symbols-outlined text-base">how_to_reg</span>
            <span>Reader Administration</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                  :class="activeTab === 'readers' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                {{ count($readers) }}
            </span>
            @if($pendingApplicationsCount > 0)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-sm flex items-center gap-0.5 animate-pulse">
                    <span class="material-symbols-outlined text-[11px]">pending</span>
                    <span>{{ $pendingApplicationsCount }}</span>
                </span>
            @endif
        </button>

        <!-- Tab 3: System Settings -->
        <button type="button" @click="setTab('settings')"
                class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-2.5 transition-all whitespace-nowrap shadow-sm"
                :class="activeTab === 'settings' 
                    ? 'bg-slate-800 dark:bg-white text-white dark:text-slate-900 shadow-md font-black' 
                    : 'bg-white dark:bg-surface-card hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10'">
            <span class="material-symbols-outlined text-base">tune</span>
            <span>Platform System Settings</span>
        </button>
    </div>

    <!-- TAB 1: USER ADMINISTRATION (STAFF & CREATORS ONLY) -->
    <div id="users" x-show="activeTab === 'users'" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 translate-y-2" 
         x-transition:enter-end="opacity-100 translate-y-0" 
         class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">manage_accounts</span>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Staff & Creators Administration</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                            {{ count($staffUsers) }} Members
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Executive directors, managing editors, staff journalists, and verified community contributors</p>
                </div>
            </div>

            <!-- Search Filter -->
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
                <input type="text" x-model="staffSearch" placeholder="Search staff by name or email..." 
                       class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-white/10">
                    <tr>
                        <th class="py-3 px-4">Staff Member</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Role Badge</th>
                        <th class="py-3 px-4">Joined Date</th>
                        <th class="py-3 px-4 text-right">Assign RBAC Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-300">
                    @foreach($staffUsers as $usr)
                        <tr x-show="matchesStaff('{{ addslashes($usr['name']) }}', '{{ addslashes($usr['email']) }}')" 
                            class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white flex items-center gap-3">
                                <img src="{{ $usr['avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                                     alt="{{ $usr['name'] }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary/20">
                                <div class="truncate">
                                    <div class="truncate">{{ $usr['name'] }}</div>
                                    @if(!empty($usr['bio']))
                                        <div class="text-[10px] text-slate-400 font-normal truncate max-w-xs">{{ $usr['bio'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400">{{ $usr['email'] }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase
                                    {{ $usr['role_name'] === 'management' ? 'bg-secondary/20 text-secondary-light border border-secondary/30' : '' }}
                                    {{ $usr['role_name'] === 'editor' ? 'bg-tertiary/20 text-tertiary-light border border-tertiary/30' : '' }}
                                    {{ $usr['role_name'] === 'journalist' ? 'bg-primary/20 text-primary-light border border-primary/30' : '' }}
                                    {{ $usr['role_name'] === 'contributor' ? 'bg-sky-500/20 text-sky-400 border border-sky-500/30' : '' }}">
                                    {{ $usr['role_display_name'] ?? $usr['role_name'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ date('M d, Y', strtotime($usr['created_at'])) }}</td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <form action="{{ url('dashboard/management/users/' . $usr['id'] . '/role') }}" method="POST" class="inline-flex items-center gap-2">
                                    <input type="hidden" name="source" value="users">
                                    <select name="role_id" onchange="this.form.submit()" 
                                            class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-primary">
                                        @foreach($roles as $r)
                                            <option value="{{ $r['id'] }}" {{ $usr['role_id'] == $r['id'] ? 'selected' : '' }}>
                                                {{ $r['name'] }} ({{ $r['display_name'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: READER ADMINISTRATION (REGISTERED READERS & CONTRIBUTOR PROMOTION APPLICATIONS) -->
    <div id="readers" x-show="activeTab === 'readers'" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 translate-y-2" 
         x-transition:enter-end="opacity-100 translate-y-0" 
         class="space-y-8">
        
        <!-- SUB-SECTION 1: CONTRIBUTOR PROMOTION APPLICATIONS QUEUE -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-purple-500/30 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-purple-500/10 text-purple-400 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">draw</span>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Contributor Promotion Applications</h3>
                            @if($pendingApplicationsCount > 0)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500/20 text-amber-500 dark:text-amber-300 border border-amber-500/30 animate-pulse">
                                    {{ $pendingApplicationsCount }} Awaiting Approval
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-500 dark:text-emerald-400">
                                    Queue Clear
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Registered readers who submitted their author credentials on the About page requesting Contributor writer access
                        </p>
                    </div>
                </div>
            </div>

            @if(empty($applications))
                <div class="py-12 text-center text-slate-400 space-y-2">
                    <span class="material-symbols-outlined text-4xl text-slate-500">assignment_turned_in</span>
                    <p class="text-sm font-semibold">No contributor applications submitted yet.</p>
                    <p class="text-xs text-slate-500">Registered readers can apply anytime via the "Get Involved" section on the About Us page.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-white/10">
                            <tr>
                                <th class="py-3 px-4">Applicant</th>
                                <th class="py-3 px-4">School / Institution & Grade</th>
                                <th class="py-3 px-4">Contact & Birth Date</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Applied</th>
                                <th class="py-3 px-4 text-right">Management Review</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-300">
                            @foreach($applications as $app)
                                <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors {{ $app['status'] === 'pending' ? 'bg-amber-500/[0.03]' : '' }}">
                                    <!-- Applicant Info -->
                                    <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $app['user_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                                                 alt="{{ $app['full_name'] }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-purple-500/20">
                                            <div>
                                                <div class="font-bold flex items-center gap-1.5">
                                                    <span>{{ $app['full_name'] }}</span>
                                                    @if(!empty($app['author_name']))
                                                        <span class="text-[10px] font-mono font-normal text-purple-400" title="Author Pen Name">
                                                            ("{{ $app['author_name'] }}")
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] font-mono text-slate-400 font-normal">{{ $app['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- School & Grade -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-900 dark:text-white flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs text-primary">school</span>
                                            <span>{{ $app['institution'] }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $app['grade_or_position'] }}</div>
                                    </td>

                                    <!-- Contact & Birth Date -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            @if(!empty($app['whatsapp']))
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $app['whatsapp']) }}" target="_blank"
                                                   class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 font-mono text-[11px] flex items-center gap-1 font-bold"
                                                   title="Contact via WhatsApp">
                                                    <span class="material-symbols-outlined text-xs">chat</span>
                                                    <span>{{ $app['whatsapp'] }}</span>
                                                </a>
                                            @else
                                                <span class="text-slate-400 text-[11px]">No WA</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">cake</span>
                                            <span>Born {{ date('M d, Y', strtotime($app['birth_date'])) }}</span>
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($app['status'] === 'pending')
                                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-500 dark:text-amber-300 border border-amber-500/30 flex items-center gap-1 w-max">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span>Awaiting Approval</span>
                                            </span>
                                        @elseif($app['status'] === 'approved')
                                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1 w-max">
                                                <span class="material-symbols-outlined text-xs">check_circle</span>
                                                <span>Approved & Contributor</span>
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-rose-500/15 text-rose-500 dark:text-rose-400 border border-rose-500/30 flex items-center gap-1 w-max"
                                                  title="{{ $app['rejection_reason'] ?: 'No reason stated' }}">
                                                <span class="material-symbols-outlined text-xs">cancel</span>
                                                <span>Rejected</span>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Applied Date -->
                                    <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        {{ date('M d, Y', strtotime($app['created_at'])) }}
                                    </td>

                                    <!-- Action Buttons -->
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        @if($app['status'] === 'pending')
                                            <div class="flex items-center justify-end gap-2">
                                                <!-- Approve Form (Promote to Contributor) -->
                                                <form action="{{ url('dashboard/management/applications/' . $app['id'] . '/approve') }}" method="POST" class="inline">
                                                    <button type="submit" 
                                                            class="px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs shadow-md shadow-emerald-500/20 transition-all flex items-center gap-1"
                                                            title="Approve credentials and promote reader to Community Contributor (role_id = 4)">
                                                        <span class="material-symbols-outlined text-sm">verified</span>
                                                        <span>Approve & Promote</span>
                                                    </button>
                                                </form>

                                                <!-- Reject Modal Trigger -->
                                                <button type="button" @click="openReject({{ $app['id'] }}, '{{ addslashes($app['full_name']) }}')"
                                                        class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-bold text-xs border border-rose-500/30 transition-all flex items-center gap-1"
                                                        title="Reject application and provide editorial feedback">
                                                    <span class="material-symbols-outlined text-sm">close</span>
                                                    <span>Reject</span>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-[11px] text-slate-400 italic">
                                                Reviewed by {{ $app['reviewer_name'] ?? 'Management' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- SUB-SECTION 2: REGISTERED READERS DIRECTORY -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-tertiary/10 text-tertiary-light flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">groups</span>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Registered Readers Directory</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-tertiary/10 text-tertiary-light border border-tertiary/20">
                                {{ count($readers) }} Readers
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Authenticated platform readers who participate in discussions, bookmark lore, and read stories</p>
                    </div>
                </div>

                <!-- Search Filter -->
                <div class="relative w-full sm:w-64">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
                    <input type="text" x-model="readerSearch" placeholder="Search readers by name or email..." 
                           class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-white/10">
                        <tr>
                            <th class="py-3 px-4">Reader Profile</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Community Engagement</th>
                            <th class="py-3 px-4">Contributor Application</th>
                            <th class="py-3 px-4">Registered Date</th>
                            <th class="py-3 px-4 text-right">Promote Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-300">
                        @foreach($readers as $rdr)
                            <tr x-show="matchesReader('{{ addslashes($rdr['name']) }}', '{{ addslashes($rdr['email']) }}', '{{ addslashes($rdr['institution'] ?? '') }}')" 
                                class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white flex items-center gap-3">
                                    <img src="{{ $rdr['avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                                         alt="{{ $rdr['name'] }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-tertiary/20">
                                    <div class="truncate">
                                        <div class="truncate">{{ $rdr['name'] }}</div>
                                        @if(!empty($rdr['institution']))
                                            <div class="text-[10px] text-slate-400 font-normal truncate">{{ $rdr['institution'] }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400">{{ $rdr['email'] }}</td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3 text-slate-600 dark:text-slate-400">
                                        <span class="flex items-center gap-1" title="Comments Posted">
                                            <span class="material-symbols-outlined text-xs text-rose-500">forum</span>
                                            <span>{{ number_format($rdr['comments_count'] ?? 0) }} comments</span>
                                        </span>
                                        <span>&bull;</span>
                                        <span class="flex items-center gap-1" title="Saved Bookmarks">
                                            <span class="material-symbols-outlined text-xs text-primary">bookmark</span>
                                            <span>{{ number_format($rdr['bookmarks_count'] ?? 0) }} saved</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if(!empty($rdr['application_status']))
                                        @if($rdr['application_status'] === 'pending')
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-500/15 text-amber-500 dark:text-amber-300 border border-amber-500/25 flex items-center gap-1 w-max">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span>Application Pending</span>
                                            </span>
                                        @elseif($rdr['application_status'] === 'approved')
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25 flex items-center gap-1 w-max">
                                                <span class="material-symbols-outlined text-[11px]">verified</span>
                                                <span>Promoted to Contributor</span>
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-500/15 text-rose-500 dark:text-rose-400 border border-rose-500/25 flex items-center gap-1 w-max">
                                                <span>Application Rejected</span>
                                            </span>
                                        @endif
                                    @else
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400">
                                            Standard Reader
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ date('M d, Y', strtotime($rdr['created_at'])) }}</td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <form action="{{ url('dashboard/management/users/' . $rdr['id'] . '/role') }}" method="POST" class="inline-flex items-center gap-2">
                                        <input type="hidden" name="source" value="readers">
                                        <select name="role_id" onchange="this.form.submit()" 
                                                class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-primary">
                                            @foreach($roles as $r)
                                                <option value="{{ $r['id'] }}" {{ $rdr['role_id'] == $r['id'] ? 'selected' : '' }}>
                                                    {{ $r['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: PLATFORM SYSTEM SETTINGS -->
    <div id="settings" x-show="activeTab === 'settings'" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 translate-y-2" 
         x-transition:enter-end="opacity-100 translate-y-0" 
         class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-6">
        
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-tertiary/10 text-tertiary-light flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">tune</span>
            </span>
            <div>
                <h3 class="text-xl font-black font-display text-slate-900 dark:text-white">Platform System Settings</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure core metadata, Gemini AI models, and registration protocols</p>
            </div>
        </div>

        <form action="{{ url('dashboard/management/settings') }}" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($settings as $st)
                    <div class="p-5 rounded-2xl bg-surface-card border border-white/5 space-y-2">
                        <label class="block text-xs font-bold text-slate-300 font-mono">{{ $st['key'] }}</label>
                        <p class="text-[11px] text-slate-400 mb-2">{{ $st['description'] }}</p>
                        
                        @if($st['key'] === 'allow_registration' || $st['key'] === 'ai_assistant_enabled' || $st['key'] === 'maintenance_mode')
                            <select name="settings[{{ $st['key'] }}]" class="w-full px-4 py-2.5 rounded-xl bg-background border border-white/10 text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                <option value="true" {{ $st['value'] === 'true' ? 'selected' : '' }}>Enabled (true)</option>
                                <option value="false" {{ $st['value'] === 'false' ? 'selected' : '' }}>Disabled (false)</option>
                            </select>
                        @elseif($st['key'] === 'gemini_model')
                            <select name="settings[{{ $st['key'] }}]" class="w-full px-4 py-2.5 rounded-xl bg-background border border-white/10 text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                <option value="gemini-1.5-flash" {{ $st['value'] === 'gemini-1.5-flash' ? 'selected' : '' }}>gemini-1.5-flash (Fast & Creative)</option>
                                <option value="gemini-1.5-pro" {{ $st['value'] === 'gemini-1.5-pro' ? 'selected' : '' }}>gemini-1.5-pro (High Reasoning)</option>
                            </select>
                        @else
                            <input type="text" name="settings[{{ $st['key'] }}]" value="{{ $st['value'] }}" 
                                   class="w-full px-4 py-2.5 rounded-xl bg-background border border-white/10 text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="text-right">
                <button type="submit" class="px-6 py-3 rounded-2xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-lg shadow-primary/25 transition-all">
                    Save System Settings
                </button>
            </div>
        </form>
    </div>

    <!-- REJECTION FEEDBACK MODAL FOR CONTRIBUTOR APPLICATIONS -->
    <div x-cloak x-show="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="rejectModalOpen = false"></div>
        <div class="relative w-full max-w-lg p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-rose-500/30 z-10 animate-in fade-in zoom-in-95 text-slate-900 dark:text-white space-y-4 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">cancel</span>
                </span>
                <div>
                    <h3 class="text-xl font-black font-display">Reject Contributor Application</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Applicant: <strong class="text-slate-900 dark:text-white" x-text="rejectApplicantName"></strong>
                    </p>
                </div>
            </div>

            <form :action="'{{ url('dashboard/management/applications/') }}/' + rejectAppId + '/reject'" method="POST" class="space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Feedback / Reason for Rejection <span class="text-slate-400 text-[10px] font-normal">(Visible to applicant)</span>
                    </label>
                    <textarea name="rejection_reason" rows="3" required
                              placeholder="Explain what details need revision before this reader can be approved as a contributor..."
                              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rose-500/40"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="rejectModalOpen = false" 
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-xs font-bold text-slate-700 dark:text-slate-300 transition-all">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">cancel</span>
                        <span>Submit Rejection</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
