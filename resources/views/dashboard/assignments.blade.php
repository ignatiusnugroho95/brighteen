@extends('layouts.app')

@section('content')
<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8"
     x-data="{
         activeFilter: 'all',
         filterMatches(status) {
             if (this.activeFilter === 'all') return true;
             if (this.activeFilter === 'pending') return status === 'pending';
             if (this.activeFilter === 'turned_in') return status === 'turned_in';
             if (this.activeFilter === 'expired') return status === 'expired';
             if (this.activeFilter === 'approved') return status === 'approved';
             return true;
         }
     }">

    <!-- Page Header & Metrics Summary -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-base">assignment</span>
                <span>Journalist Workspace</span>
            </div>
            <h1 class="text-3xl font-black font-display text-slate-900 dark:text-white">Assignment Center</h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                Track your story briefs, deadlines, and turn in your articles directly to the editorial review queue.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ url('articles/create') }}" 
               class="px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-lg shadow-primary/25 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">edit_note</span>
                <span>Independent Story</span>
            </a>
        </div>
    </div>

    <!-- Notification Alert -->
    @if(!empty($notice))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <span>
                @if($notice === 'assignment_turned_in')
                    Awesome work! Your story was turned in successfully and submitted to the Editorial Review Queue.
                @else
                    Action completed successfully.
                @endif
            </span>
        </div>
    @endif

    <!-- Metric Stat Badges -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
        <div class="p-5 rounded-3xl glass-panel border border-primary/30 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Total Assigned</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-primary mt-1">{{ $totalAssigned }}</h3>
                </div>
                <span class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">folder_open</span>
                </span>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-2">All assigned briefs</p>
        </div>

        <div class="p-5 rounded-3xl glass-panel border border-amber-500/30 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Pending Tasks</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-amber-500 dark:text-amber-400 mt-1">{{ $totalPending }}</h3>
                </div>
                <span class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-500 dark:text-amber-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">pending_actions</span>
                </span>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-2">Ready to write</p>
        </div>

        <div class="p-5 rounded-3xl glass-panel border border-purple-500/30 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Turned In</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-purple-600 dark:text-purple-400 mt-1">{{ $totalTurnedIn }}</h3>
                </div>
                <span class="w-10 h-10 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">rule_folder</span>
                </span>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-2">In editorial review</p>
        </div>

        <div class="p-5 rounded-3xl glass-panel border border-rose-500/30 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Expired / Overdue</span>
                    <h3 class="text-2xl sm:text-3xl font-black font-display text-rose-500 dark:text-rose-400 mt-1">{{ $totalExpired }}</h3>
                </div>
                <span class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-500 dark:text-rose-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">schedule</span>
                </span>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-2">Submission still open</p>
        </div>
    </div>

    <!-- Filter Pills Toolbar -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
        <button type="button" @click="activeFilter = 'all'"
                :class="activeFilter === 'all' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-white dark:bg-surface-card text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/10 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl transition-all">
            All Assignments ({{ $totalAssigned }})
        </button>
        <button type="button" @click="activeFilter = 'pending'"
                :class="activeFilter === 'pending' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25' : 'bg-white dark:bg-surface-card text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/10 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl transition-all">
            Active Pending ({{ $totalPending }})
        </button>
        <button type="button" @click="activeFilter = 'expired'"
                :class="activeFilter === 'expired' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/25' : 'bg-white dark:bg-surface-card text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/10 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl transition-all">
            Expired / Overdue ({{ $totalExpired }})
        </button>
        <button type="button" @click="activeFilter = 'turned_in'"
                :class="activeFilter === 'turned_in' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/25' : 'bg-white dark:bg-surface-card text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/10 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl transition-all">
            Turned In ({{ $totalTurnedIn }})
        </button>
        <button type="button" @click="activeFilter = 'approved'"
                :class="activeFilter === 'approved' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25' : 'bg-white dark:bg-surface-card text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/10 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl transition-all">
            Published ({{ $totalApproved }})
        </button>
    </div>

    <!-- Assignments List: 1 Assignment Per Row -->
    <div class="space-y-4">
        @if(empty($assignments))
            <div class="p-12 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 text-center space-y-3">
                <span class="w-14 h-14 rounded-2xl bg-primary/10 text-primary mx-auto flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">task_alt</span>
                </span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Assignments Assigned Yet</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    You currently have no editorial assignments. You can still pitch and draft independent stories through the Writer Workbench!
                </p>
                <div class="pt-2">
                    <a href="{{ url('articles/create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold shadow-md shadow-primary/20">
                        <span class="material-symbols-outlined text-sm">edit_square</span>
                        <span>Write Independent Story</span>
                    </a>
                </div>
            </div>
        @else
            @foreach($assignments as $asg)
                <div id="{{ $asg['id'] }}"
                     x-show="filterMatches('{{ $asg['effective_status'] }}')"
                     class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200 dark:border-white/10 hover:border-primary/40 transition-all shadow-sm space-y-4">
                    
                    <!-- Row Top Bar: Category Pill & Status Badges -->
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-xl text-xs font-bold flex items-center gap-1.5"
                                  style="background-color: {{ $asg['category_color'] }}20; border: 1px solid {{ $asg['category_color'] }}50; color: {{ $asg['category_color'] }};">
                                <span class="material-symbols-outlined text-sm">{{ $asg['category_icon'] ?: 'tag' }}</span>
                                <span>{{ $asg['category_name'] }}</span>
                            </span>
                            <span class="text-xs text-slate-400 font-mono">#{{ $asg['id'] }}</span>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            @if($asg['effective_status'] === 'expired')
                                <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">warning</span>
                                    <span>Expired (Overdue) - Submission Still Open</span>
                                </span>
                            @elseif($asg['effective_status'] === 'turned_in')
                                <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">done_all</span>
                                    <span>Turned In &bull; Under Review</span>
                                </span>
                            @elseif($asg['effective_status'] === 'approved')
                                <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">verified</span>
                                    <span>Approved & Published</span>
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">hourglass_top</span>
                                    <span>In Progress</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Row Main Body: Topic & Briefing -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-center">
                        <div class="lg:col-span-2 space-y-1.5">
                            <h3 class="text-lg font-bold font-display text-slate-900 dark:text-white leading-snug">
                                {{ $asg['topic'] }}
                            </h3>
                            @if(!empty($asg['description']))
                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                    {{ $asg['description'] }}
                                </p>
                            @endif

                            <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 pt-1 flex-wrap">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">person</span>
                                    <span>Assigned by <strong>{{ $asg['editor_name'] ?? 'Editor' }}</strong></span>
                                </span>
                                <span>&bull;</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">calendar_month</span>
                                    <span>Created: {{ date('M j, Y', strtotime($asg['created_at'])) }}</span>
                                </span>
                                @if(!empty($asg['turned_in_at']))
                                    <span>&bull;</span>
                                    <span class="flex items-center gap-1 text-purple-600 dark:text-purple-400 font-semibold">
                                        <span class="material-symbols-outlined text-sm">check</span>
                                        <span>Turned in: {{ date('M j, H:i', strtotime($asg['turned_in_at'])) }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Right Column: Deadlines & Action Button -->
                        <div class="flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end justify-between gap-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.02] border border-slate-200/80 dark:border-white/5">
                            <div class="text-xs space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-slate-500 dark:text-slate-400">Submission Deadline:</span>
                                    <span class="font-bold {{ $asg['is_overdue'] ? 'text-rose-500' : 'text-amber-500 dark:text-amber-400' }}">
                                        {{ date('M j, Y - H:i', strtotime($asg['submission_deadline'])) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                    <span>Publishing Schedule:</span>
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">
                                        {{ date('M j, Y - H:i', strtotime($asg['publishing_schedule'])) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Action: Write / View -->
                            <div class="shrink-0 w-full sm:w-auto">
                                @if($asg['effective_status'] === 'pending' || $asg['effective_status'] === 'expired')
                                    <a href="{{ url('articles/create?assignment_id=' . $asg['id']) }}" 
                                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white font-bold text-xs shadow-md shadow-primary/25 transition-all transform hover:-translate-y-0.5">
                                        <span class="material-symbols-outlined text-base">edit</span>
                                        <span>Write Article</span>
                                    </a>
                                @elseif($asg['effective_status'] === 'turned_in')
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-purple-600 dark:text-purple-400 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">hourglass_bottom</span>
                                            <span>In Review</span>
                                        </span>
                                        @if(!empty($asg['article_id']))
                                            <a href="{{ url('articles/' . $asg['article_id'] . '/edit') }}" 
                                               class="px-3 py-1.5 rounded-xl bg-slate-200 dark:bg-white/10 hover:bg-slate-300 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-semibold">
                                                Edit Draft
                                            </a>
                                        @endif
                                    </div>
                                @elseif($asg['effective_status'] === 'approved')
                                    <a href="{{ url('articles/' . $asg['article_slug']) }}" 
                                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-xs font-bold transition-all">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        <span>View Published Story</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

</div>
@endsection
