@extends('layouts.app')

@section('content')
<!-- Chart.js for High-Fidelity Interactive Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function centralDashboard() {
    return {
        // Active role shortcut tab (defaults to current logged-in role or management)
        selectedRoleTab: '{{ in_array($currentRole, ["management", "editor", "journalist", "contributor"]) ? $currentRole : "management" }}',
        topListTab: 'views', // 'views' | 'likes' | 'rated_soon'
        chartViewMode: 'distribution', // 'distribution' | 'timeline' | 'health'

        init() {
            this.$nextTick(() => {
                this.initCharts();
            });
        },

        initCharts() {
            // 1. Comparative Category Distribution Chart
            const catCtx = document.getElementById('categoryDistributionChart');
            if (catCtx) {
                const catLabels = {!! json_encode(array_column($categoryStats, 'name')) !!};
                const catArticles = {!! json_encode(array_map('intval', array_column($categoryStats, 'article_count'))) !!};
                const catViews = {!! json_encode(array_map('intval', array_column($categoryStats, 'total_views'))) !!};
                const catColors = {!! json_encode(array_column($categoryStats, 'color_code')) !!};

                new Chart(catCtx, {
                    type: 'bar',
                    data: {
                        labels: catLabels,
                        datasets: [
                            {
                                label: 'Total Articles',
                                data: catArticles,
                                backgroundColor: catColors.map(c => c + 'cc'),
                                borderColor: catColors,
                                borderWidth: 1.5,
                                borderRadius: 8,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Total Views (Readers)',
                                data: catViews,
                                type: 'line',
                                borderColor: '#38bdf8',
                                backgroundColor: 'rgba(56, 189, 248, 0.15)',
                                pointBackgroundColor: '#38bdf8',
                                pointBorderColor: '#ffffff',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                fill: true,
                                tension: 0.35,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 14,
                                    font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600', size: 11 },
                                    color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleFont: { family: "'Plus Jakarta Sans', sans-serif", weight: 'bold' },
                                bodyFont: { family: "'Inter', sans-serif" },
                                padding: 12,
                                cornerRadius: 12
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600', size: 10 },
                                    color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b'
                                }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Articles',
                                    font: { size: 10, weight: 'bold' },
                                    color: '#94a3b8'
                                },
                                grid: {
                                    color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: { stepSize: 1, color: '#94a3b8' }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Views',
                                    font: { size: 10, weight: 'bold' },
                                    color: '#38bdf8'
                                },
                                grid: { drawOnChartArea: false },
                                ticks: { color: '#38bdf8' }
                            }
                        }
                    }
                });
            }

            // 2. Editorial Health Pipeline (Doughnut Chart)
            const healthCtx = document.getElementById('editorialHealthChart');
            if (healthCtx) {
                const published = {{ $articleStatusCounts['published'] ?? 0 }};
                const review = {{ $articleStatusCounts['pending_review'] ?? 0 }};
                const drafts = {{ $articleStatusCounts['draft'] ?? 0 }};
                const assignments = {{ $activeAssignmentsCount ?? 0 }};

                new Chart(healthCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Published (Live)', 'Under Review', 'Writer Drafts', 'Active Briefs'],
                        datasets: [{
                            data: [published, review, drafts, assignments],
                            backgroundColor: [
                                '#10b981', // Emerald for published
                                '#8b5cf6', // Purple for review
                                '#f59e0b', // Amber for drafts
                                '#6366f1'  // Indigo for briefs
                            ],
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 14,
                                    font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600', size: 11 },
                                    color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                padding: 10,
                                cornerRadius: 10
                            }
                        }
                    }
                });
            }

            // 3. Progressive Publication Velocity (Timeline Line Chart)
            const timelineCtx = document.getElementById('publicationTimelineChart');
            if (timelineCtx) {
                const rawTimeline = {!! json_encode($timelineData) !!};
                const timeLabels = rawTimeline.map(t => t.pub_date);
                const timeViews = rawTimeline.map(t => parseInt(t.views_count));
                const timeCounts = rawTimeline.map(t => parseInt(t.articles_count));

                new Chart(timelineCtx, {
                    type: 'line',
                    data: {
                        labels: timeLabels,
                        datasets: [
                            {
                                label: 'View Growth',
                                data: timeViews,
                                borderColor: '#8b5cf6',
                                backgroundColor: 'rgba(139, 92, 246, 0.18)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                yAxisID: 'yViews'
                            },
                            {
                                label: 'Published Works',
                                data: timeCounts,
                                borderColor: '#10b981',
                                backgroundColor: '#10b981',
                                type: 'bar',
                                borderRadius: 6,
                                yAxisID: 'yCount'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600', size: 11 },
                                    color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569'
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 }, color: '#94a3b8' }
                            },
                            yViews: {
                                position: 'left',
                                grid: { color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' },
                                ticks: { color: '#8b5cf6' }
                            },
                            yCount: {
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { stepSize: 1, color: '#10b981' }
                            }
                        }
                    }
                });
            }
        }
    };
}
</script>

<div class="space-y-8 pb-16 min-w-0" x-data="centralDashboard()">

    <!-- 1. EXECUTIVE HERO BANNER & GREETING -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950 border border-indigo-500/30 p-6 sm:p-8 text-white shadow-xl">
        <!-- Glow Orbs -->
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-white/10 text-primary-light border border-white/15 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Executive & Editorial Hub
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-500/20 text-purple-200 border border-purple-500/30 capitalize">
                    Role: {{ auth()->user()['role_name'] ?? 'Guest' }}
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black font-display tracking-tight text-white leading-tight">
                Hello, <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-indigo-300 to-purple-300">{{ auth()->user()['name'] ?? 'Young Creator' }}</span>!
            </h1>

            <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                Welcome to the centralized Brighteen command hub. Instant access to your editorial workspaces, progressive magazine analytics, story curation, and outstanding student achievements.
            </p>
        </div>

        <!-- Role Preview Selector Bar -->
        <div class="mt-6 pt-5 border-t border-white/10 flex items-center justify-between flex-wrap gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm text-purple-300">swap_horiz</span>
                <span class="font-bold text-slate-300">Explore Role Shortcuts:</span>
            </div>
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach(['management' => 'Management', 'editor' => 'Editor', 'journalist' => 'Journalist', 'contributor' => 'Contributor'] as $roleKey => $roleLabel)
                    <button type="button" 
                            @click="selectedRoleTab = '{{ $roleKey }}'"
                            :class="selectedRoleTab === '{{ $roleKey }}' ? 'bg-white text-slate-950 font-black shadow-md' : 'bg-white/10 hover:bg-white/20 text-white font-semibold'"
                            class="px-3 py-1.5 rounded-xl transition-all text-xs flex items-center gap-1">
                        @if($currentRole === $roleKey)
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        @endif
                        <span>{{ $roleLabel }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 2. ROLE WORKSPACE SHORTCUTS (PINTASAN SESUAI ROLE) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">bolt</span>
                    </span>
                    <h2 class="text-lg sm:text-xl font-black font-display text-slate-900 dark:text-white">
                        Quick Access & Workspaces
                    </h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Direct access to operational tools and desks tailored to your role.
                </p>
            </div>

            <!-- Role Badge Tag -->
            <div class="flex items-center gap-2">
                <template x-if="selectedRoleTab === 'management'">
                    <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        Shortcuts: Platform Director
                    </span>
                </template>
                <template x-if="selectedRoleTab === 'editor'">
                    <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
                        Shortcuts: Managing Editor
                    </span>
                </template>
                <template x-if="selectedRoleTab === 'journalist'">
                    <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                        Shortcuts: Staff Journalist
                    </span>
                </template>
                <template x-if="selectedRoleTab === 'contributor'">
                    <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20">
                        Shortcuts: Student Contributor
                    </span>
                </template>
            </div>
        </div>

        <!-- Dynamic Role Shortcuts Grid -->
        @foreach($roleShortcuts as $rKey => $roleGroup)
            <div x-show="selectedRoleTab === '{{ $rKey }}'" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                @foreach($roleGroup['items'] as $item)
                    <a href="{{ url($item['url']) }}" 
                       class="group p-4 sm:p-5 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 hover:border-primary/50 hover:shadow-lg dark:hover:shadow-primary/5 transition-all flex flex-col justify-between space-y-3 relative overflow-hidden">
                        
                        <div class="flex items-start justify-between gap-3">
                            <span class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 border transition-transform group-hover:scale-110 {{ $item['color'] }}">
                                <span class="material-symbols-outlined text-xl">{{ $item['icon'] }}</span>
                            </span>
                            <span class="material-symbols-outlined text-slate-300 dark:text-slate-600 group-hover:text-primary group-hover:translate-x-0.5 transition-all text-base">
                                arrow_outward
                            </span>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors leading-snug">
                                {{ $item['title'] }}
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                {{ $item['desc'] }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-slate-100 dark:border-white/5 flex items-center text-[10px] font-bold text-primary">
                            <span>Open Page</span>
                            <span class="material-symbols-outlined text-xs ml-1">chevron_right</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>

    <!-- 3. STATISTIK UTAMA (KEY PERFORMANCE METRICS) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">insights</span>
                </span>
                <h2 class="text-lg sm:text-xl font-black font-display text-slate-900 dark:text-white">
                    Publication Statistics & Metrics
                </h2>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500 hidden sm:inline font-mono">
                Real-time Data
            </span>
        </div>

        <!-- 8 Metric Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">

            <!-- Metric 1: Jumlah Artikel Pernah Terbit -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-emerald-500/30 text-left relative overflow-hidden group shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                            Published Articles
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($totalPublishedArticles) }}
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">auto_stories</span>
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>Live on website</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                        100% Curated
                    </span>
                </div>
            </div>

            <!-- Metric 2: Jumlah Rubrik / Kategori -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-sky-500/30 text-left relative overflow-hidden group shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                            Total Beats
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-sky-500 dark:text-sky-400 mt-1">
                            {{ number_format($totalCategories) }}
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-sky-500/10 text-sky-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">category</span>
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>Editorial Channels</span>
                    <span class="text-sky-500 font-bold text-[10px]">Active Beats</span>
                </div>
            </div>

            <!-- Metric 3: Jumlah Menit Reading Time -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-amber-500/30 text-left relative overflow-hidden group shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                            Reading Time
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-amber-500 dark:text-amber-400 mt-1">
                            {{ $totalReadingMinutes }} <span class="text-base font-bold text-slate-400">Min</span>
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">schedule</span>
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>~{{ number_format($totalWordCount) }} words total</span>
                    <span class="text-amber-500 font-bold text-[10px]">200 WPM</span>
                </div>
            </div>

            <!-- Metric 4: Jumlah Views Total -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-purple-500/30 text-left relative overflow-hidden group shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                            Total Views
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-purple-600 dark:text-purple-400 mt-1">
                            {{ number_format($totalViews) }}
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">visibility</span>
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>Total Readers</span>
                    <span class="text-purple-500 font-bold text-[10px] flex items-center gap-0.5">
                        <span class="material-symbols-outlined text-xs">trending_up</span> Viral
                    </span>
                </div>
            </div>

            <!-- Metric 5: Jumlah Interaksi (Likes, Saves, Shares, Comments) -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-rose-500/30 text-left relative overflow-hidden group shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                            Total Interactions
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-rose-500 dark:text-rose-400 mt-1">
                            {{ number_format($totalInteractions) }}
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">favorite</span>
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>{{ $totalLikes }} likes · {{ $totalComments }} comments</span>
                    <span class="px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-500 font-bold">
                        {{ $totalSaves }} saves
                    </span>
                </div>
            </div>

            <!-- Metric 6: Jumlah Reader (Registered Readers) -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-indigo-500/30 text-left relative overflow-hidden group shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                            Reader Community
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-indigo-500 dark:text-indigo-400 mt-1">
                            {{ number_format($totalRegisteredReaders) }}
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">school</span>
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>Registered Students</span>
                    <span class="text-indigo-400 font-bold text-[10px]">Bright Students</span>
                </div>
            </div>

            <!-- Metric 7: Jumlah Kontributor -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-teal-500/30 text-left relative overflow-hidden group shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                            Active Contributors
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-teal-500 dark:text-teal-400 mt-1">
                            {{ number_format($totalContributors) }}
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-teal-500/10 text-teal-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">palette</span>
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>{{ $totalJournalists }} staff journalists</span>
                    <span class="text-teal-500 font-bold text-[10px]">Creators</span>
                </div>
            </div>

            <!-- Metric 8: Total Donasi (Coming Soon) -->
            <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-dashed border-amber-500/40 text-left relative overflow-hidden group shadow-xs bg-amber-500/[0.02]">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                                Reader Donations
                            </span>
                            <span class="px-1.5 py-0.2 rounded text-[8px] font-black bg-amber-500 text-slate-900 uppercase">
                                Soon
                            </span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black font-display text-slate-400 dark:text-slate-500 mt-1">
                            $0
                        </h3>
                    </div>
                    <span class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">volunteer_activism</span>
                    </span>
                </div>
                <div class="mt-2.5 text-[11px] text-amber-600 dark:text-amber-400/90 font-medium leading-tight">
                    <span>Student appreciation fund for print edition</span>
                </div>
            </div>

        </div>
    </div>

    <!-- 4. COMPARATIVE & PROGRESSIVE INTERACTIVE CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Chart 1: Komparasi Rubrik & Pembaca (2 Cols) -->
        <div class="lg:col-span-2 p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100 dark:border-white/5">
                <div>
                    <h3 class="text-base font-black font-display text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">bar_chart</span>
                        <span>Content & Readership by Beat</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Comparison of published article volume against accumulated readers per beat.
                    </p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300">
                    Comparative Beat Analysis
                </span>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-64 sm:h-72 w-full">
                <canvas id="categoryDistributionChart"></canvas>
            </div>

            <!-- Category Breakdown Pills -->
            <div class="pt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                @foreach(array_slice($categoryStats, 0, 4) as $cat)
                    <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/60 dark:border-white/5 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $cat['color_code'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ $cat['name'] }}</div>
                            <div class="text-[10px] text-slate-500">{{ $cat['article_count'] }} articles · {{ number_format($cat['total_views']) }} views</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Chart 2: Pipeline Kesehatan Produksi Redaksi (1 Col) -->
        <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm flex flex-col justify-between space-y-4">
            <div class="pb-3 border-b border-slate-100 dark:border-white/5">
                <h3 class="text-base font-black font-display text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-purple-500 text-xl">donut_large</span>
                    <span>Editorial Room Pipeline</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Manuscript lifecycle health from active briefs to publication.
                </p>
            </div>

            <div class="relative h-56 w-full flex items-center justify-center">
                <canvas id="editorialHealthChart"></canvas>
            </div>

            <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-white/5 text-xs">
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Published Articles
                    </span>
                    <strong class="text-slate-900 dark:text-white">{{ $articleStatusCounts['published'] ?? 0 }}</strong>
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Pending Review
                    </span>
                    <strong class="text-slate-900 dark:text-white">{{ $articleStatusCounts['pending_review'] ?? 0 }}</strong>
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Drafts in Progress
                    </span>
                    <strong class="text-slate-900 dark:text-white">{{ $articleStatusCounts['draft'] ?? 0 }}</strong>
                </div>
            </div>
        </div>

    </div>

    <!-- 5. PROGRESSIVE VELOCITY TIMELINE -->
    <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100 dark:border-white/5">
            <div>
                <h3 class="text-base font-black font-display text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500 text-xl">show_chart</span>
                    <span>Progressive Growth: Publication Velocity & Traffic</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Trajectory of student readership momentum alongside daily publication output.
                </p>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">
                Progressive Trajectory
            </span>
        </div>

        <div class="relative h-60 sm:h-64 w-full">
            <canvas id="publicationTimelineChart"></canvas>
        </div>
    </div>

    <!-- 6. TOP 5 RANKINGS & HALL OF FAME -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Top 5 Views Artikel -->
        <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/5">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">local_fire_department</span>
                    </span>
                    <div>
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Top 5 Most-Read Articles (Views)</h3>
                        <p class="text-xs text-slate-500">Highest readership across all student readers and visitors.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($topViewsArticles as $index => $art)
                    <div class="p-3 sm:p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/70 dark:border-white/5 flex items-center gap-3 group hover:border-purple-500/30 transition-all">
                        <!-- Rank Badge -->
                        <span class="w-7 h-7 rounded-xl flex items-center justify-center text-xs font-black shrink-0 {{ $index === 0 ? 'bg-amber-400 text-slate-950 font-black shadow-sm' : ($index === 1 ? 'bg-slate-300 dark:bg-slate-700 text-slate-900 dark:text-white' : ($index === 2 ? 'bg-amber-600/30 text-amber-500' : 'bg-slate-200 dark:bg-white/10 text-slate-600 dark:text-slate-400')) }}">
                            #{{ $index + 1 }}
                        </span>

                        <!-- Thumbnail -->
                        <img src="{{ $art['featured_image'] ?: 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=150&q=80' }}" 
                             alt="{{ $art['title'] }}" 
                             class="w-12 h-12 rounded-xl object-cover shrink-0 ring-1 ring-slate-200 dark:ring-white/10">

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-bold" 
                                      style="background-color: {{ $art['category_color'] }}15; color: {{ $art['category_color'] }};">
                                    {{ $art['category_name'] }}
                                </span>
                                <span class="text-[10px] text-slate-400">by {{ $art['author_name'] }}</span>
                            </div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors truncate">
                                <a href="{{ url('articles/' . $art['slug']) }}" class="hover:underline">
                                    {{ $art['title'] }}
                                </a>
                            </h4>
                        </div>

                        <!-- Views Counter -->
                        <div class="text-right shrink-0">
                            <div class="text-xs sm:text-sm font-black text-purple-600 dark:text-purple-400 font-mono">
                                {{ number_format($art['views_count']) }}
                            </div>
                            <span class="text-[10px] text-slate-400">views</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top 5 Most Liked Artikel -->
        <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/5">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">thumb_up</span>
                    </span>
                    <div>
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Top 5 Most Liked Articles</h3>
                        <p class="text-xs text-slate-500">Stories receiving the strongest student acclaim and appreciation.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($topLikedArticles as $index => $art)
                    <div class="p-3 sm:p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/70 dark:border-white/5 flex items-center gap-3 group hover:border-rose-500/30 transition-all">
                        <!-- Rank Badge -->
                        <span class="w-7 h-7 rounded-xl flex items-center justify-center text-xs font-black shrink-0 {{ $index === 0 ? 'bg-rose-500 text-white font-black shadow-sm' : ($index === 1 ? 'bg-slate-300 dark:bg-slate-700 text-slate-900 dark:text-white' : ($index === 2 ? 'bg-rose-600/30 text-rose-500' : 'bg-slate-200 dark:bg-white/10 text-slate-600 dark:text-slate-400')) }}">
                            #{{ $index + 1 }}
                        </span>

                        <!-- Thumbnail -->
                        <img src="{{ $art['featured_image'] ?: 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=150&q=80' }}" 
                             alt="{{ $art['title'] }}" 
                             class="w-12 h-12 rounded-xl object-cover shrink-0 ring-1 ring-slate-200 dark:ring-white/10">

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-bold" 
                                      style="background-color: {{ $art['category_color'] }}15; color: {{ $art['category_color'] }};">
                                    {{ $art['category_name'] }}
                                </span>
                                <span class="text-[10px] text-slate-400">by {{ $art['author_name'] }}</span>
                            </div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-rose-500 transition-colors truncate">
                                <a href="{{ url('articles/' . $art['slug']) }}" class="hover:underline">
                                    {{ $art['title'] }}
                                </a>
                            </h4>
                        </div>

                        <!-- Likes Counter -->
                        <div class="text-right shrink-0">
                            <div class="text-xs sm:text-sm font-black text-rose-500 font-mono flex items-center gap-1 justify-end">
                                <span class="material-symbols-outlined text-xs">favorite</span>
                                <span>{{ $art['likes_count'] }}</span>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ number_format($art['views_count']) }} views</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <!-- 7. COMING SOON CARDS & STUDENT CONTRIBUTOR HALL OF FAME -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Coming Soon Card: Top 5 Artikel dengan Nilai Tertinggi -->
        <div class="p-5 sm:p-6 rounded-3xl bg-gradient-to-br from-amber-500/[0.06] via-slate-50 to-amber-500/[0.02] dark:from-amber-500/[0.06] dark:via-surface-card dark:to-transparent border border-dashed border-amber-500/40 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-amber-500/20">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">grade</span>
                    </span>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h3 class="text-sm sm:text-base font-black font-display text-slate-900 dark:text-white">
                                Top Rated Articles
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">
                                Coming Soon
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Curated Peer & Editorial Review Score</p>
                    </div>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                Automated manuscript quality assessment and editorial review scoring criteria are currently in active rollout.
            </p>

            <div class="space-y-2 text-xs">
                <div class="p-2.5 rounded-xl bg-white/60 dark:bg-white/5 border border-amber-500/20 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Originality & News Value</span>
                    <span class="font-bold text-amber-500">Weight 25%</span>
                </div>
                <div class="p-2.5 rounded-xl bg-white/60 dark:bg-white/5 border border-amber-500/20 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Research Depth & Interviews</span>
                    <span class="font-bold text-amber-500">Weight 30%</span>
                </div>
                <div class="p-2.5 rounded-xl bg-white/60 dark:bg-white/5 border border-amber-500/20 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Youth Tone & Clarity</span>
                    <span class="font-bold text-amber-500">Weight 25%</span>
                </div>
                <div class="p-2.5 rounded-xl bg-white/60 dark:bg-white/5 border border-amber-500/20 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Visual Aesthetics & Layout</span>
                    <span class="font-bold text-amber-500">Weight 20%</span>
                </div>
            </div>

            <div class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1.5 pt-1">
                <span class="material-symbols-outlined text-sm">verified_user</span>
                <span>School Journalism Council Quality Standards</span>
            </div>
        </div>

        <!-- Student Contributor & Journalist Hall of Fame (2 Cols) -->
        <div class="lg:col-span-2 p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/5 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">military_tech</span>
                    </span>
                    <div>
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white">
                            Student Contributors & Journalists Hall of Fame
                        </h3>
                        <p class="text-xs text-slate-500">Most prolific creators with the highest total readership.</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-indigo-500 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                    Creator Leaderboard
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($authorLeaderboard as $idx => $authItem)
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/70 dark:border-white/5 flex items-center gap-3">
                        <img src="{{ $authItem['avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                             alt="{{ $authItem['name'] }}" 
                             class="w-11 h-11 rounded-2xl object-cover ring-2 ring-indigo-500/20 shrink-0">

                        <div class="min-w-0 flex-1 space-y-0.5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ $authItem['name'] }}
                                </h4>
                                <span class="text-[10px] font-black text-indigo-500 bg-indigo-500/10 px-2 py-0.5 rounded-lg shrink-0">
                                    #{{ $idx + 1 }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-500 truncate">{{ $authItem['role_display_name'] }}</p>
                            <div class="flex items-center gap-2 pt-1 text-[10px] text-slate-600 dark:text-slate-400 font-semibold">
                                <span class="text-emerald-500 font-bold">{{ $authItem['published_count'] }} Published Works</span>
                                <span>·</span>
                                <span>{{ number_format($authItem['total_views']) }} Views</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection

