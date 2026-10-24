@extends('layouts.app')

@section('content')
<div class="py-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" 
     x-data="{
        heroImg: '{{ addslashes($page['hero_image'] ?? '') }}',
        activeTab: 'general'
     }">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-base">tune</span>
                <span>Editorial & Management Control</span>
            </div>
            <h1 class="text-3xl font-black font-display text-slate-900 dark:text-white">
                Edit About Us Page
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Customize platform narrative, mission manifesto, hero artwork, and live impact statistics.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ url('about') }}" 
               class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">visibility</span>
                <span>View Public Page</span>
            </a>
            @if(auth()->isEditor())
                <a href="{{ url('dashboard/editorial') }}" 
                   class="px-4 py-2.5 rounded-2xl bg-tertiary/10 hover:bg-tertiary/20 text-tertiary-light text-xs font-bold border border-tertiary/30 transition-all">
                    Editorial Desk
                </a>
            @endif
        </div>
    </div>

    @if(!empty($error))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-sm font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <span>{{ $error }}</span>
        </div>
    @endif

    <form action="{{ url('about/update') }}" method="POST" class="space-y-8">
        
        <!-- Section 1: Hero & Identity -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-6">
            <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider">
                <span class="material-symbols-outlined text-base">branding_watermark</span>
                <span>1. Hero Identity & Artwork</span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Page Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ $page['title'] ?? 'About Brighteen' }}" required
                           class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Subtitle / Tagline <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="subtitle" rows="2" required
                              class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 leading-relaxed resize-none">{{ $page['subtitle'] ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Hero Cover Image URL
                    </label>
                    <input type="url" name="hero_image" x-model="heroImg" placeholder="https://images.unsplash.com/..."
                           class="w-full px-4 py-2.5 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <!-- Live Image Preview -->
                <div class="space-y-1.5">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Live Cover Preview:</span>
                    <div class="aspect-21/9 max-h-56 w-full rounded-2xl overflow-hidden bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-center relative">
                        <template x-if="heroImg">
                            <img :src="heroImg" alt="Cover Preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!heroImg">
                            <div class="text-slate-400 text-xs flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">image</span>
                                <span>No cover image specified</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Mission & Manifesto -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-6">
            <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider">
                <span class="material-symbols-outlined text-base">flag</span>
                <span>2. Mission Statement & Manifesto</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Our Mission Statement <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="mission" rows="4" required placeholder="What is Brighteen's core mission?"
                              class="w-full p-4 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none leading-relaxed">{{ $page['mission'] ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Our Manifesto & Values <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="manifesto" rows="4" required placeholder="What does Brighteen stand for?"
                              class="w-full p-4 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none leading-relaxed">{{ $page['manifesto'] ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 3: Live Impact Metrics (Stats) -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider">
                    <span class="material-symbols-outlined text-base">query_stats</span>
                    <span>3. Platform Impact Metrics (4 Highlights)</span>
                </div>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Displayed in vibrant metric cards on public page</span>
            </div>

            @php
                $statsList = $page['stats'] ?? [];
                // Pad to 4 items
                while (count($statsList) < 4) {
                    $statsList[] = ['value' => '', 'label' => '', 'icon' => 'verified'];
                }
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($statsList as $idx => $st)
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 space-y-3">
                        <span class="text-[10px] font-black uppercase tracking-wider text-primary">Stat Card #{{ $idx + 1 }}</span>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Number / Value</label>
                            <input type="text" name="stat_val[]" value="{{ $st['value'] ?? '' }}" placeholder="e.g. 150K+"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Metric Label</label>
                            <input type="text" name="stat_label[]" value="{{ $st['label'] ?? '' }}" placeholder="e.g. Monthly Youth Readers"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Material Icon</label>
                            <input type="text" name="stat_icon[]" value="{{ $st['icon'] ?? 'verified' }}" placeholder="e.g. trending_up"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs font-mono">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Section 4: Narrative / Detailed Content -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider">
                    <span class="material-symbols-outlined text-base">article</span>
                    <span>4. Origin Story & Editorial Narrative</span>
                </div>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Supports standard HTML markup</span>
            </div>

            <textarea name="content" rows="12" required
                      class="w-full p-4 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/15 text-slate-900 dark:text-white text-xs font-mono focus:outline-none focus:ring-2 focus:ring-primary/40 leading-relaxed resize-y">{{ $page['content'] ?? '' }}</textarea>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                Tip: You can use paragraph tags (&lt;p&gt;), blockquotes (&lt;blockquote&gt;), subheaders (&lt;h3&gt;), and emphasis (&lt;strong&gt;, &lt;em&gt;).
            </p>
        </div>

        <!-- Form Submission Footer -->
        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-white/10">
            <a href="{{ url('about') }}" 
               class="px-5 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white text-xs font-bold transition-all text-center">
                Cancel
            </a>
            <button type="submit" 
                    class="px-8 py-3 rounded-2xl bg-gradient-to-r from-primary via-indigo-600 to-purple-600 hover:from-primary-hover hover:to-indigo-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                <span class="material-symbols-outlined text-base">save</span>
                <span>Save & Publish Changes</span>
            </button>
        </div>

    </form>
</div>
@endsection
