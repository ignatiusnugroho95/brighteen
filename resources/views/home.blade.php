@extends('layouts.app')

@section('content')
<div class="space-y-10 py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8"
     x-data="{
         viewMode: 'feed', // 'feed' or 'grid'
         shareModalOpen: false,
         sharePost: { title: '', url: '', image: '' },
         pollVoted: false,
         pollSelection: null,
         pollVotes: { yes: 82, no: 18 },

         votePoll(option) {
             if (this.pollVoted) return;
             this.pollVoted = true;
             this.pollSelection = option;
             $root.closest('body').__x.$data.showToast('Poll vote recorded! 📊');
         },

         openShare(title, url, image) {
             this.sharePost = { title: title, url: url, image: image };
             this.shareModalOpen = true;
         },
         copyLink(customUrl = null) {
             const target = customUrl || this.sharePost.url || window.location.href;
             navigator.clipboard.writeText(target);
             $root.closest('body').__x.$data.showToast('Story link copied to clipboard! 📋');
             this.shareModalOpen = false;
         }
     }">

    <!-- 1. LIVE RUNNING NEWSROOM ANNOUNCEMENT & 5 LATEST STORIES TICKER -->
    <div class="rounded-2xl bg-slate-100 dark:bg-white/[0.04] border border-slate-200 dark:border-white/10 p-2 sm:px-3 sm:py-2 text-xs shadow-sm overflow-hidden flex items-center gap-3 transition-colors duration-300">
        <!-- Ticker Static Label -->
        <div class="flex items-center gap-1.5 shrink-0 bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-light px-2.5 py-1 rounded-xl font-black text-[11px] uppercase tracking-wider z-10 shadow-xs">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="material-symbols-outlined text-[16px]">campaign</span>
            <span class="hidden sm:inline">Dispatches Ticker</span>
        </div>

        <!-- Marquee Scrolling Track -->
        <div class="relative overflow-hidden flex-1 group">
            <div class="animate-marquee-smooth items-center gap-8 py-0.5">
                @for($repeat = 0; $repeat < 2; $repeat++)
                    <div class="inline-flex items-center gap-6 sm:gap-8">
                        @if(!empty($announcement))
                            <div class="inline-flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white font-black text-[10px] uppercase tracking-wider flex items-center gap-1 shadow-xs">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                    <span>ANNOUNCEMENT</span>
                                </span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $announcement }}</span>
                            </div>
                            <span class="text-slate-300 dark:text-slate-700 select-none">•</span>
                        @endif

                        @if(!empty($latestPromotedContributors))
                            @foreach($latestPromotedContributors as $lpc)
                                @php
                                    $displayName = !empty($lpc['author_name']) ? $lpc['author_name'] : (!empty($lpc['full_name']) ? $lpc['full_name'] : $lpc['user_name']);
                                @endphp
                                <div class="inline-flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-full bg-gradient-to-r from-sky-500 to-indigo-500 text-white font-black text-[10px] uppercase tracking-wider flex items-center gap-1 shadow-xs">
                                        <span class="material-symbols-outlined text-[13px]">verified</span>
                                        <span>NEW CONTRIBUTOR</span>
                                    </span>
                                    <span class="font-bold text-slate-800 dark:text-slate-100">
                                        Congratulations to <strong class="text-sky-600 dark:text-sky-400 font-black">{{ $displayName }}</strong>{{ !empty($lpc['institution']) ? ' (' . $lpc['institution'] . ')' : '' }} on being officially promoted to Community Contributor! 🎉
                                    </span>
                                </div>
                                <span class="text-slate-300 dark:text-slate-700 select-none">•</span>
                            @endforeach
                        @endif

                        @if(!empty($latestUpdatedStories))
                            <div class="inline-flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 font-black text-[10px] uppercase tracking-wider">
                                    5 LATEST UPDATES
                                </span>
                            </div>
                            @foreach($latestUpdatedStories as $lst)
                                <a href="{{ url('articles/' . $lst['slug']) }}" class="inline-flex items-center gap-2 hover:text-primary transition-colors group/item">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $lst['category_color'] ?: '#3b82f6' }};"></span>
                                    <span class="font-semibold text-slate-700 dark:text-slate-300 group-hover/item:text-primary transition-colors">
                                        {{ $lst['title'] }}
                                    </span>
                                    <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500">
                                        ({{ date('d M H:i', strtotime($lst['updated_at'] ?: $lst['published_at'])) }})
                                    </span>
                                </a>
                                <span class="text-slate-300 dark:text-slate-700 select-none">•</span>
                            @endforeach
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </div>


    <!-- 2. DYNAMIC CATEGORIES & BEATS TRAY (From Database including Brightonista) -->
    <section class="relative overflow-hidden py-1">
        <div class="flex items-center gap-4 sm:gap-5 overflow-x-auto pb-3 scrollbar-none px-1">
            
            @foreach($categories as $cat)
                @php
                    $isActive = ($activeCategory && $activeCategory['slug'] === $cat['slug']);
                @endphp
                <a href="{{ url('?category=' . $cat['slug']) }}" 
                   class="flex flex-col items-center gap-1.5 group flex-shrink-0 cursor-pointer">
                    <div class="p-0.5 rounded-full transition-all transform group-hover:scale-105 duration-300 {{ $isActive ? 'ring-2 ring-primary ring-offset-2 ring-offset-background' : 'story-gradient' }}">
                        <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-full p-1 bg-white dark:bg-background flex flex-col items-center justify-center text-center shadow-inner"
                             style="border: 2px solid {{ $cat['color_code'] ?? '#2563eb' }}40;">
                            <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform" 
                                  style="color: {{ $cat['color_code'] ?? '#2563eb' }};">
                                {{ $cat['icon'] ?: 'tag' }}
                            </span>
                            @if(!empty($cat['published_articles_count']))
                                <span class="text-[9px] font-bold text-slate-400 -mt-0.5">
                                    {{ $cat['published_articles_count'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <span class="text-[11px] font-bold transition-colors text-center truncate max-w-[76px] {{ $isActive ? 'text-primary' : 'text-slate-700 dark:text-slate-300 group-hover:text-primary' }}">
                        {{ $cat['name'] }}
                    </span>
                </a>
            @endforeach

            <!-- Submit Story Option -->
            <a href="{{ url('articles/create') }}" class="flex flex-col items-center gap-1.5 group flex-shrink-0 cursor-pointer">
                <div class="p-0.5 rounded-full bg-slate-200 dark:bg-white/10 group-hover:story-gradient transition-all transform group-hover:scale-105 duration-300">
                    <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-full p-0.5 bg-white dark:bg-background flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl text-slate-400 group-hover:text-primary transition-colors">add_circle</span>
                    </div>
                </div>
                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 group-hover:text-primary transition-colors text-center truncate max-w-[76px]">
                    Submit Story
                </span>
            </a>

        </div>
    </section>


    <!-- 3. ARTICLE HEADLINE (HERO BANNER AS SHOWN IN REFERENCE IMAGE) -->
    @if(!empty($headline))
        <section class="relative rounded-3xl overflow-hidden glass-panel border border-slate-200/80 dark:border-white/10 shadow-2xl transition-all duration-300 group"
                 x-data="{
                     liked: {{ $headline['is_liked'] ? 'true' : 'false' }},
                     likeCount: {{ $headline['likes_count'] ?: 1240 }},
                     saved: {{ $headline['is_saved'] ? 'true' : 'false' }},
                     saveCount: {{ $headline['saves_count'] ?: 312 }},
                     heartAnimating: false,
                     saveAnimating: false,
                     toggleLike() {
                         this.heartAnimating = true;
                         setTimeout(() => { this.heartAnimating = false; }, 650);
                         this.liked = !this.liked;
                         this.likeCount += this.liked ? 1 : -1;
                         fetch('{{ url('api/articles/' . $headline['id'] . '/like') }}', { method: 'POST' })
                             .then(res => res.json())
                             .then(data => {
                                 this.liked = data.liked;
                                 this.likeCount = data.count;
                             });
                     },
                     toggleSave() {
                         @guest
                             $root.closest('body').__x.$data.guestActionText = 'save headline stories';
                             $root.closest('body').__x.$data.guestModalOpen = true;
                             return;
                         @endguest
                         this.saveAnimating = true;
                         setTimeout(() => { this.saveAnimating = false; }, 650);
                         this.saved = !this.saved;
                         this.saveCount += this.saved ? 1 : -1;
                         fetch('{{ url('api/articles/' . $headline['id'] . '/save') }}', { method: 'POST' })
                             .then(res => res.json())
                             .then(data => {
                                 this.saved = data.saved;
                                 if (data.count !== undefined) this.saveCount = data.count;
                                 $root.closest('body').__x.$data.showToast(this.saved ? 'Headline saved to bookmarks! 🔖' : 'Removed from bookmarks');
                             });
                     }
                 }">
            
            <!-- Background Image with Gradient Masking -->
            <div class="absolute inset-0 z-0">
                <img src="{{ $headline['featured_image'] }}" alt="{{ $headline['title'] }}" 
                     class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-slate-950/40"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/60 to-transparent"></div>
            </div>

            <!-- Foreground Content -->
            <div class="relative z-10 p-6 sm:p-10 lg:p-12 max-w-3xl flex flex-col justify-end min-h-[440px] sm:min-h-[500px]">
                
                <!-- Category Badges & Meta Row -->
                <div class="flex flex-wrap items-center gap-2.5 mb-4">
                    <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider text-white shadow-md flex items-center gap-1.5"
                          style="background-color: {{ $headline['category_color'] ?? '#2563eb' }};">
                        <span class="material-symbols-outlined text-xs">{{ $headline['category_icon'] ?? 'tag' }}</span>
                        <span>{{ $headline['category_name'] ?? 'FEATURED BEAT' }}</span>
                    </span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-300 bg-black/40 backdrop-blur-md px-2.5 py-1 rounded-full">
                        <span class="material-symbols-outlined text-xs">schedule</span>
                        <span>{{ reading_time($headline['content'] ?? '') }}</span>
                    </span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-300 bg-black/40 backdrop-blur-md px-2.5 py-1 rounded-full">
                        <span class="material-symbols-outlined text-xs">visibility</span>
                        <span>{{ number_format($headline['views_count'] ?: 1840) }} views</span>
                    </span>
                </div>

                <!-- Headline Title -->
                <a href="{{ url('articles/' . $headline['slug']) }}" class="group/title block mb-3">
                    <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black font-display text-white tracking-tight leading-[1.15] group-hover/title:text-primary transition-colors drop-shadow-md">
                        {{ $headline['title'] }}
                    </h1>
                </a>

                <!-- Excerpt Subdeck -->
                <p class="text-sm sm:text-base text-slate-300 font-body leading-relaxed mb-6 line-clamp-3 max-w-2xl text-shadow">
                    {{ $headline['excerpt'] }}
                </p>

                <!-- Author & Interactive Action Bar -->
                <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-white/10">
                    
                    <!-- Author Info -->
                    <div class="flex items-center gap-3">
                        <div class="p-0.5 rounded-full story-gradient">
                            <img src="{{ $headline['author_avatar'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80' }}" 
                                 alt="{{ $headline['author_name'] }}" 
                                 class="w-10 h-10 rounded-full object-cover ring-2 ring-white">
                        </div>
                        <div>
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-sm text-white">{{ $headline['author_name'] }}</span>
                                <span class="w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-black">✓</span>
                            </div>
                            <span class="text-xs text-slate-400">Grade 9 Student Journalist</span>
                        </div>
                    </div>

                    <!-- Actions: Love, Comment (Opens full article), Save, Share, Read Story -->
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <!-- Love Button (Radiant Glow on Click) -->
                        <button type="button" @click="toggleLike()" 
                                class="flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-2xl bg-black/40 hover:bg-black/60 backdrop-blur-md transition-all text-xs font-bold focus:outline-none"
                                :class="liked ? 'text-rose-500 glow-red' : 'text-white hover:text-rose-400'">
                            <span class="material-symbols-outlined text-base transition-colors"
                                  :class="{
                                      'font-fill text-rose-500 glow-red': liked,
                                      'animate-heart-glow': heartAnimating
                                  }">favorite</span>
                            <span x-text="likeCount"></span><span class="hidden sm:inline">&nbsp;loves</span>
                        </button>

                        <!-- Comment Button (Navigates to full article to view story & post comment) -->
                        <a href="{{ url('articles/' . $headline['slug'] . '#comments') }}" 
                           class="flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-2xl bg-black/40 hover:bg-black/60 backdrop-blur-md transition-all text-xs font-bold text-white hover:text-primary"
                           title="Read full article to comment">
                            <span class="material-symbols-outlined text-base">chat_bubble</span>
                            <span>{{ $headline['comments_count'] ?: 48 }}</span><span class="hidden sm:inline">&nbsp;comments</span>
                        </a>

                        <!-- Save Bookmark Button (Radiant Glow on Click) -->
                        <button type="button" @click="toggleSave()" 
                                class="flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-2xl bg-black/40 hover:bg-black/60 backdrop-blur-md transition-all focus:outline-none text-xs font-bold"
                                :class="saved ? 'text-primary glow-primary' : 'text-white hover:text-primary'"
                                title="Save to bookmarks">
                            <span class="material-symbols-outlined text-base transition-colors"
                                  :class="{
                                      'font-fill text-primary glow-primary': saved,
                                      'animate-bookmark-glow': saveAnimating
                                  }">bookmark</span>
                            <span x-text="saveCount"></span><span class="hidden sm:inline">&nbsp;saves</span>
                        </button>

                        <!-- Share Button -->
                        <button type="button" @click="openShare('{{ addslashes($headline['title']) }}', '{{ url('articles/' . $headline['slug']) }}', '{{ $headline['featured_image'] }}')" 
                                class="p-2 sm:p-2.5 rounded-2xl bg-black/40 hover:bg-black/60 backdrop-blur-md transition-all text-white hover:text-sky-400"
                                title="Share story">
                            <span class="material-symbols-outlined text-base">send</span>
                        </button>

                        <!-- Read Story Pill Button -->
                        <a href="{{ url('articles/' . $headline['slug']) }}" 
                           class="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2 sm:py-2.5 rounded-full bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white font-bold text-xs shadow-lg shadow-primary/30 transition-all hover:scale-105">
                            <span>Read Story</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>

                </div>

            </div>
        </section>
    @endif


    <!-- 4. CURATED SCHOOL STORIES (4 SUB-HEADLINE GRID: 2x2 AS SHOWN IN REFERENCE IMAGE) -->
    @if(!empty($subHeadlines) && count($subHeadlines) >= 4)
        <section class="space-y-4" id="curated-stories">
            
            <!-- Section Title Header -->
            <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h2 class="text-xl sm:text-2xl font-black font-display text-slate-900 dark:text-white">
                        Curated School Stories
                    </h2>
                </div>
                <span class="text-xs font-semibold text-slate-400">
                    Issue #24 Curations / 4 Featured
                </span>
            </div>

            <!-- 2x2 Sub-Headline Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                @foreach($subHeadlines as $idx => $sub)
                    <article class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 shadow-lg overflow-hidden flex flex-col justify-between group hover:shadow-2xl hover:border-slate-300 dark:hover:border-white/20 transition-all duration-300"
                             x-data="{
                                 liked: {{ $sub['is_liked'] ? 'true' : 'false' }},
                                 likeCount: {{ $sub['likes_count'] ?: 28 }},
                                 saved: {{ $sub['is_saved'] ? 'true' : 'false' }},
                                 saveCount: {{ $sub['saves_count'] ?: ($idx * 4 + 14) }},
                                 heartAnimating: false,
                                 saveAnimating: false,
                                 toggleLike() {
                                     this.heartAnimating = true;
                                     setTimeout(() => { this.heartAnimating = false; }, 650);
                                     this.liked = !this.liked;
                                     this.likeCount += this.liked ? 1 : -1;
                                     fetch('{{ url('api/articles/' . $sub['id'] . '/like') }}', { method: 'POST' })
                                         .then(res => res.json())
                                         .then(data => {
                                             this.liked = data.liked;
                                             this.likeCount = data.count;
                                         });
                                 },
                                 toggleSave() {
                                     @guest
                                         $root.closest('body').__x.$data.guestActionText = 'save curated stories';
                                         $root.closest('body').__x.$data.guestModalOpen = true;
                                         return;
                                     @endguest
                                     this.saveAnimating = true;
                                     setTimeout(() => { this.saveAnimating = false; }, 650);
                                     this.saved = !this.saved;
                                     this.saveCount += this.saved ? 1 : -1;
                                     fetch('{{ url('api/articles/' . $sub['id'] . '/save') }}', { method: 'POST' })
                                         .then(res => res.json())
                                         .then(data => {
                                             this.saved = data.saved;
                                             if (data.count !== undefined) this.saveCount = data.count;
                                             $root.closest('body').__x.$data.showToast(this.saved ? 'Saved to bookmarks! 🔖' : 'Removed from bookmarks');
                                         });
                                 }
                             }">
                        <div>
                            <!-- Card Image Banner with Category Tag & Views -->
                            <div class="relative w-full aspect-video overflow-hidden bg-slate-900">
                                <img src="{{ $sub['featured_image'] }}" alt="{{ $sub['title'] }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
                                
                                <!-- Category Pill Badge & Reading Time Top-Left -->
                                <div class="absolute top-3 left-3 flex items-center gap-1.5">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider text-white shadow-md"
                                          style="background-color: {{ $sub['category_color'] ?: '#2563eb' }};">
                                        {{ $sub['category_name'] }}
                                    </span>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold text-white bg-black/60 backdrop-blur-md shadow-md flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[11px]">schedule</span>
                                        <span>{{ reading_time($sub['content'] ?? '') }}</span>
                                    </span>
                                </div>

                                <!-- Views Counter Bottom-Right -->
                                <span class="absolute bottom-3 right-3 flex items-center gap-1 text-[11px] font-bold text-white bg-black/60 backdrop-blur-md px-2.5 py-1 rounded-full">
                                    <span class="material-symbols-outlined text-xs">visibility</span>
                                    <span>{{ number_format($sub['views_count'] ?: 940) }} views</span>
                                </span>
                            </div>

                            <!-- Card Content Body -->
                            <div class="p-5 sm:p-6 pb-2">
                                <!-- Author & Time Subtitle -->
                                <div class="text-xs font-bold text-slate-400 mb-1.5 flex items-center gap-1">
                                    <span>By {{ $sub['author_name'] }}</span>
                                    <span>&bull;</span>
                                    <span>{{ date('M d', strtotime($sub['published_at'] ?: $sub['created_at'])) }}</span>
                                </div>

                                <!-- Title -->
                                <a href="{{ url('articles/' . $sub['slug']) }}" class="group/sub">
                                    <h3 class="text-base sm:text-lg font-black font-display text-slate-900 dark:text-white leading-snug group-hover/sub:text-primary transition-colors line-clamp-2 mb-2">
                                        {{ $sub['title'] }}
                                    </h3>
                                </a>

                                <!-- Excerpt -->
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 font-body leading-relaxed line-clamp-2">
                                    {{ $sub['excerpt'] }}
                                </p>
                            </div>
                        </div>

                        <!-- Card Action Footer: Like, Comment, Share, Save, and Read Article -->
                        <div class="p-5 sm:p-6 pt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 dark:border-white/5">
                            <!-- Left: Interactive Actions (Like, Comment, Share, Save) -->
                            <div class="flex items-center gap-3.5 text-slate-600 dark:text-slate-300">
                                <!-- Like Button (Radiant Glow & Synchronous Counter) -->
                                <button type="button" @click="toggleLike()" 
                                        class="flex items-center gap-1 text-xs font-bold transition-all focus:outline-none"
                                        :class="liked ? 'text-rose-500 font-bold glow-red' : 'hover:text-rose-500'"
                                        title="Love this story">
                                    <span class="material-symbols-outlined text-lg transition-colors"
                                          :class="{
                                              'font-fill text-rose-500 glow-red': liked,
                                              'animate-heart-glow': heartAnimating
                                          }">favorite</span>
                                    <span x-text="likeCount"></span>
                                </button>

                                <!-- Comment Link (Direct to Comments) -->
                                <a href="{{ url('articles/' . $sub['slug'] . '#comments') }}" 
                                   class="flex items-center gap-1 text-xs font-bold hover:text-primary transition-transform active:scale-125"
                                   title="Comments">
                                    <span class="material-symbols-outlined text-lg">chat_bubble</span>
                                    <span>{{ $sub['comments_count'] ?: 24 }}</span>
                                </a>

                                <!-- Share Button -->
                                <button type="button" @click="openShare('{{ addslashes($sub['title']) }}', '{{ url('articles/' . $sub['slug']) }}', '{{ $sub['featured_image'] }}')" 
                                        class="hover:text-sky-400 transition-transform active:scale-125 focus:outline-none"
                                        title="Share story">
                                    <span class="material-symbols-outlined text-lg">send</span>
                                </button>

                                <!-- Save / Bookmark Button (Radiant Glow & Synchronous Counter) -->
                                <button type="button" @click="toggleSave()" 
                                        class="flex items-center gap-1 text-xs font-bold transition-all focus:outline-none"
                                        :class="saved ? 'text-primary glow-primary' : 'hover:text-primary'"
                                        title="Save to bookmarks">
                                    <span class="material-symbols-outlined text-lg transition-colors"
                                          :class="{
                                              'font-fill text-primary glow-primary': saved,
                                              'animate-bookmark-glow': saveAnimating
                                          }">bookmark</span>
                                    <span x-text="saveCount"></span>
                                </button>
                            </div>

                            <!-- Right: Read Article Link -->
                            <a href="{{ url('articles/' . $sub['slug']) }}" 
                               class="inline-flex items-center gap-1 text-xs font-black text-primary hover:text-primary-hover group/btn">
                                <span>Read Article</span>
                                <span class="material-symbols-outlined text-sm transform group-hover/btn:translate-x-1 transition-transform">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                @endforeach

            </div>
        </section>
    @endif


    <!-- 5. CATEGORY FILTER TABS & SEARCH BAR (As seen in reference image) -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 pb-2 border-b border-slate-200 dark:border-white/10">
        
        <!-- Category Filter Pills -->
        <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-2 sm:pb-0 scrollbar-none">
            <a href="{{ url('') }}" 
               class="px-4 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all {{ empty($activeCategory) && empty($search) ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-slate-100 dark:bg-surface-card text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                All Stories
            </a>
            @foreach($categories as $cat)
                <a href="{{ url('?category=' . $cat['slug']) }}" 
                   class="px-4 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all {{ ($activeCategory && $activeCategory['slug'] === $cat['slug']) ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-slate-100 dark:bg-surface-card text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                    {{ $cat['name'] }}
                </a>
            @endforeach
        </div>

        <!-- Right Side: View Mode Switcher -->
        <div class="flex items-center gap-2 self-end sm:self-auto">
            <div class="flex items-center gap-1 p-1 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/5">
                <button type="button" @click="viewMode = 'feed'" 
                        class="p-2 rounded-xl transition-all flex items-center gap-1 text-xs font-bold"
                        :class="viewMode === 'feed' ? 'bg-white dark:bg-surface-high text-primary shadow-sm' : 'text-slate-400 hover:text-white'">
                    <span class="material-symbols-outlined text-lg">view_stream</span>
                    <span class="hidden sm:inline">Feed</span>
                </button>
                <button type="button" @click="viewMode = 'grid'" 
                        class="p-2 rounded-xl transition-all flex items-center gap-1 text-xs font-bold"
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-surface-high text-primary shadow-sm' : 'text-slate-400 hover:text-white'">
                    <span class="material-symbols-outlined text-lg">grid_view</span>
                    <span class="hidden sm:inline">Grid</span>
                </button>
            </div>
        </div>

    </div>


    <!-- 6. STUDENT PULSE & SNAP FEED (INSTAGRAM POSTS SECTION) -->
    <section class="space-y-8">
        
        <!-- Feed Title Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">✨</span>
                <h2 class="text-2xl font-black font-display text-slate-900 dark:text-white">
                    Student Pulse &amp; Snap Feed
                </h2>
            </div>
            <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                Campus Live Network Active
            </span>
        </div>

        <!-- FEED VIEW (INSTAGRAM POST CARDS) -->
        <div x-show="viewMode === 'feed'" class="space-y-10 max-w-xl mx-auto">
            @if(empty($feedPosts))
                <div class="p-12 text-center rounded-3xl glass-panel border border-slate-200 dark:border-white/10 text-slate-400 space-y-3">
                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600">article</span>
                    <p class="text-sm font-semibold">No stories published in this category yet.</p>
                </div>
            @endif

            <!-- PUBLISHED ARTICLES IN INSTAGRAM FEED -->
            @foreach($feedPosts as $art)
                <article class="rounded-3xl glass-panel border border-slate-200 dark:border-white/10 shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl"
                         x-data="{
                             liked: {{ $art['is_liked'] ? 'true' : 'false' }},
                             likeCount: {{ $art['likes_count'] }},
                             saved: {{ $art['is_saved'] ? 'true' : 'false' }},
                             saveCount: {{ $art['saves_count'] ?: 14 }},
                             heartAnimating: false,
                             saveAnimating: false,
                             commentsCount: {{ $art['comments_count'] }},
                             heartPopping: false,
                             quickComment: '',
                             isSubmittingComment: false,

                             toggleLike() {
                                 this.heartAnimating = true;
                                 setTimeout(() => { this.heartAnimating = false; }, 650);
                                 this.liked = !this.liked;
                                 this.likeCount += this.liked ? 1 : -1;
                                 fetch('{{ url('api/articles/' . $art['id'] . '/like') }}', { method: 'POST' })
                                     .then(res => res.json())
                                     .then(data => {
                                         this.liked = data.liked;
                                         this.likeCount = data.count;
                                     });
                             },

                             doubleTap() {
                                 if (!this.liked) {
                                     this.toggleLike();
                                 } else {
                                     this.heartAnimating = true;
                                     setTimeout(() => { this.heartAnimating = false; }, 650);
                                 }
                             },

                             toggleSave() {
                                 @guest
                                     $root.closest('body').__x.$data.guestActionText = 'bookmark stories to your saved list';
                                     $root.closest('body').__x.$data.guestModalOpen = true;
                                     return;
                                 @endguest

                                 this.saveAnimating = true;
                                 setTimeout(() => { this.saveAnimating = false; }, 650);
                                 this.saved = !this.saved;
                                 this.saveCount += this.saved ? 1 : -1;

                                 fetch('{{ url('api/articles/' . $art['id'] . '/save') }}', { method: 'POST' })
                                     .then(res => res.json())
                                     .then(data => {
                                         this.saved = data.saved;
                                         if (data.count !== undefined) this.saveCount = data.count;
                                         const msg = this.saved ? 'Saved to bookmarks! 🔖' : 'Removed from bookmarks';
                                         $root.closest('body').__x.$data.showToast(msg);
                                     });
                             },

                             submitQuickComment() {
                                 @guest
                                     $root.closest('body').__x.$data.guestActionText = 'leave comments on stories';
                                     $root.closest('body').__x.$data.guestModalOpen = true;
                                     return;
                                 @endguest

                                 if (!this.quickComment.trim()) return;

                                 this.isSubmittingComment = true;
                                 const fd = new FormData();
                                 fd.append('content', this.quickComment);

                                 fetch('{{ url('api/articles/' . $art['id'] . '/comment') }}', {
                                     method: 'POST',
                                     body: fd
                                 })
                                 .then(res => res.json())
                                 .then(data => {
                                     this.isSubmittingComment = false;
                                     if (data.success) {
                                         this.commentsCount++;
                                         this.quickComment = '';
                                         $root.closest('body').__x.$data.showToast('Comment posted! 💬');
                                     } else {
                                         $root.closest('body').__x.$data.showToast(data.error || 'Failed to post comment', 'error');
                                     }
                                 });
                             }
                         }">
                    
                    <!-- Post Header -->
                    <div class="p-4 sm:p-5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-0.5 rounded-full story-gradient">
                                <img src="{{ $art['author_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                                     alt="{{ $art['author_name'] }}" 
                                     class="w-10 h-10 rounded-full object-cover ring-2 ring-white dark:ring-background">
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-sm text-slate-900 dark:text-white hover:text-primary transition-colors">
                                        {{ '@' . strtolower(str_replace(' ', '', $art['author_name'])) }}
                                    </span>
                                    <span class="w-4 h-4 rounded-full bg-primary text-white flex items-center justify-center text-[10px]">✓</span>
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ date('M d, Y', strtotime($art['published_at'] ?: $art['created_at'])) }}
                                </div>
                            </div>
                        </div>

                        <!-- Category Pill Badge & Reading Time -->
                        <div class="flex items-center gap-1.5">
                            <a href="{{ url('?category=' . $art['category_slug']) }}" 
                               class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider text-white shadow-sm hover:scale-105 transition-transform"
                               style="background-color: {{ $art['category_color'] }};">
                                {{ $art['category_name'] }}
                            </a>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-white/10 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[11px]">schedule</span>
                                <span>{{ reading_time($art['content'] ?? '') }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Post Media (Picture with Double-Tap Heart Pop) -->
                    <div class="relative w-full aspect-square sm:aspect-[4/3] bg-black overflow-hidden group select-none cursor-pointer"
                         @dblclick="doubleTap()">
                        <img src="{{ $art['featured_image'] }}" alt="{{ $art['title'] }}" 
                             class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-500">
                        
                        <!-- Floating Big Animated Heart Pop on double tap / like -->
                        <div x-show="heartPopping" class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
                            <span class="material-symbols-outlined text-white text-8xl drop-shadow-2xl animate-heart-pop font-fill text-rose-500">
                                favorite
                            </span>
                        </div>

                        <!-- Read Story Overlay Pill (hover) -->
                        <a href="{{ url('articles/' . $art['slug']) }}" class="absolute bottom-4 right-4 px-4 py-2 rounded-2xl bg-black/60 hover:bg-primary backdrop-blur-md text-white text-xs font-bold transition-all flex items-center gap-1 opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0">
                            <span>Read Story</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>

                    <!-- Action Bar -->
                    <div class="p-4 sm:p-5 pb-3">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-4 text-slate-800 dark:text-slate-100">
                                <!-- Love Button (Radiant Glow & Synchronous Counter) -->
                                <button type="button" @click="toggleLike()" 
                                        class="flex items-center gap-1.5 transition-all focus:outline-none"
                                        :class="liked ? 'text-rose-500 font-bold glow-red' : 'text-slate-700 dark:text-slate-300 hover:text-rose-500'">
                                    <span class="material-symbols-outlined text-2xl sm:text-3xl transition-colors"
                                          :class="{
                                              'font-fill text-rose-500 glow-red': liked,
                                              'animate-heart-glow': heartAnimating
                                          }">favorite</span>
                                    <span class="text-sm font-bold" x-text="likeCount"></span>
                                </button>

                                <!-- Comment Icon -->
                                <a href="{{ url('articles/' . $art['slug'] . '#comments') }}" 
                                   class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 hover:text-primary transition-transform active:scale-125">
                                    <span class="material-symbols-outlined text-2xl sm:text-3xl">chat_bubble</span>
                                </a>

                                <!-- Share Icon (Available directly on index!) -->
                                <button type="button" @click="openShare('{{ addslashes($art['title']) }}', '{{ url('articles/' . $art['slug']) }}', '{{ $art['featured_image'] }}')" 
                                        class="text-slate-700 dark:text-slate-300 hover:text-sky-400 transition-transform active:scale-125 focus:outline-none"
                                        title="Share Story">
                                    <span class="material-symbols-outlined text-2xl sm:text-3xl">send</span>
                                </button>

                                <!-- Views Counter Pill -->
                                <span class="flex items-center gap-1 text-xs font-semibold text-slate-400 bg-slate-100 dark:bg-white/5 px-2.5 py-1 rounded-xl">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>{{ number_format($art['views_count']) }}</span>
                                </span>
                            </div>

                            <!-- Right: Save / Bookmark Ribbon (Radiant Glow & Synchronous Counter) -->
                            <button type="button" @click="toggleSave()" 
                                    class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 hover:text-primary transition-all focus:outline-none"
                                    :class="saved ? 'text-primary glow-primary' : ''"
                                    title="Save to bookmarks">
                                <span class="material-symbols-outlined text-2xl sm:text-3xl transition-colors"
                                      :class="{
                                          'font-fill text-primary glow-primary': saved,
                                          'animate-bookmark-glow': saveAnimating
                                      }">bookmark</span>
                                <span class="text-sm font-bold" x-text="saveCount"></span>
                            </button>
                        </div>

                        <!-- Title & Excerpt Caption -->
                        <div class="space-y-1 mb-3">
                            <a href="{{ url('articles/' . $art['slug']) }}" class="group/title">
                                <h3 class="text-base sm:text-lg font-black font-display text-slate-900 dark:text-white leading-snug group-hover/title:text-primary transition-colors">
                                    {{ $art['title'] }}
                                </h3>
                            </a>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-body">
                                <span class="font-bold text-slate-900 dark:text-white mr-1">{{ '@' . strtolower(str_replace(' ', '', $art['author_name'])) }}</span>
                                {{ $art['excerpt'] }}
                            </p>
                        </div>

                        <!-- View Comments Link -->
                        <div class="mb-3">
                            <a href="{{ url('articles/' . $art['slug'] . '#comments') }}" class="text-xs font-bold text-slate-400 hover:text-primary transition-colors">
                                <span x-show="commentsCount > 0">View all <span x-text="commentsCount"></span> comments</span>
                                <span x-show="commentsCount === 0">Be the first to comment</span>
                            </a>
                        </div>

                        <!-- Quick Add Comment Input -->
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-white/5">
                            @auth
                                <input type="text" x-model="quickComment" @keydown.enter="submitQuickComment()"
                                       placeholder="Add a comment as {{ auth()->user()['name'] }}..." 
                                       class="flex-1 bg-transparent text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none py-1">
                                <button type="button" @click="submitQuickComment()" :disabled="!quickComment.trim() || isSubmittingComment"
                                        class="text-xs font-bold text-primary hover:text-primary-hover disabled:opacity-30 transition-all">
                                    Post
                                </button>
                            @else
                                <a href="{{ url('login') }}" 
                                   class="flex-1 bg-transparent text-xs text-slate-400 dark:text-slate-500 hover:text-primary transition-colors py-1 cursor-pointer flex items-center select-none"
                                   title="Sign in to comment!">
                                    <span class="placeholder-text">Sign in to comment!</span>
                                </a>
                                <a href="{{ url('login') }}" 
                                   class="text-xs font-bold text-primary hover:text-primary-hover transition-all flex items-center gap-1 shrink-0"
                                   title="Sign in to your account">
                                    <span class="material-symbols-outlined text-sm">login</span>
                                    <span>Sign in</span>
                                </a>
                            @endauth
                        </div>

                    </div>
                </article>
            @endforeach

        </div>

        <!-- GRID VIEW (INSTAGRAM 3-COLUMN EXPLORE GRID) -->
        <div x-show="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
            @foreach($articles as $art)
                <a href="{{ url('articles/' . $art['slug']) }}" 
                   class="relative aspect-square rounded-3xl overflow-hidden group bg-slate-900 block shadow-md hover:shadow-2xl transition-all">
                    <img src="{{ $art['featured_image'] }}" alt="{{ $art['title'] }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    
                    <!-- Category Badge & Reading Time Top Left -->
                    <div class="absolute top-3 left-3 flex items-center gap-1 z-10">
                        <span class="px-2.5 py-0.5 rounded-xl text-[9px] font-black uppercase text-white shadow-md"
                              style="background-color: {{ $art['category_color'] }};">
                            {{ $art['category_name'] }}
                        </span>
                        <span class="px-2 py-0.5 rounded-xl text-[9px] font-semibold text-white bg-black/60 backdrop-blur-md shadow-md flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-[10px]">schedule</span>
                            <span>{{ reading_time($art['content'] ?? '') }}</span>
                        </span>
                    </div>

                    <!-- Hover Overlay with Loves and Comments -->
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-xs flex flex-col items-center justify-center p-4 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10">
                        <h4 class="text-xs sm:text-sm font-bold text-white mb-3 line-clamp-2">{{ $art['title'] }}</h4>
                        <div class="flex items-center gap-4 text-white font-bold text-xs">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-base text-rose-500 font-fill">favorite</span>
                                <span>{{ $art['likes_count'] }}</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-base">chat_bubble</span>
                                <span>{{ $art['comments_count'] }}</span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

    </section>


    <!-- 7. UNIVERSAL SHARE POST MODAL (Works directly on index!) -->
    <div x-cloak x-show="shareModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="shareModalOpen = false"></div>

        <!-- Modal Dialog -->
        <div class="relative w-full max-w-md p-6 rounded-3xl glass-panel shadow-2xl border border-white/10 z-10 space-y-5 animate-scale-in">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">share</span>
                    <h3 class="text-lg font-bold font-display text-white">Share Story</h3>
                </div>
                <button type="button" @click="shareModalOpen = false" class="text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Preview Card -->
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 border border-white/5">
                <img :src="sharePost.image" alt="Preview" class="w-14 h-14 rounded-xl object-cover">
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold text-white truncate" x-text="sharePost.title"></h4>
                    <p class="text-[11px] text-slate-400 truncate" x-text="sharePost.url"></p>
                </div>
            </div>

            <!-- Share Channels -->
            <div class="grid grid-cols-3 gap-3 text-center">
                <!-- Copy Link Button -->
                <button type="button" @click="copyLink()" class="p-3 rounded-2xl bg-white/5 hover:bg-primary/20 border border-white/5 hover:border-primary/40 text-white transition-all group flex flex-col items-center gap-1.5">
                    <span class="w-10 h-10 rounded-full bg-primary/20 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-lg">link</span>
                    </span>
                    <span class="text-xs font-bold">Copy Link</span>
                </button>

                <!-- X (Twitter) -->
                <a :href="'https://twitter.com/intent/tweet?text=' + encodeURIComponent(sharePost.title) + '&url=' + encodeURIComponent(sharePost.url)" 
                   target="_blank" rel="noopener noreferrer" 
                   class="p-3 rounded-2xl bg-white/5 hover:bg-sky-500/20 border border-white/5 hover:border-sky-500/40 text-white transition-all group flex flex-col items-center gap-1.5">
                    <span class="w-10 h-10 rounded-full bg-sky-500/20 text-sky-400 flex items-center justify-center group-hover:scale-110 transition-transform font-black text-sm">
                        𝕏
                    </span>
                    <span class="text-xs font-bold">Post on X</span>
                </a>

                <!-- WhatsApp -->
                <a :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent(sharePost.title + ' ' + sharePost.url)" 
                   target="_blank" rel="noopener noreferrer" 
                   class="p-3 rounded-2xl bg-white/5 hover:bg-emerald-500/20 border border-white/5 hover:border-emerald-500/40 text-white transition-all group flex flex-col items-center gap-1.5">
                    <span class="w-10 h-10 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform font-bold text-sm">
                        💬
                    </span>
                    <span class="text-xs font-bold">WhatsApp</span>
                </a>
            </div>

            <!-- Direct URL Field with Copy Button -->
            <div class="flex items-center gap-2 p-2 rounded-2xl bg-slate-900 border border-white/10">
                <input type="text" :value="sharePost.url" readonly 
                       class="flex-1 bg-transparent text-xs text-slate-300 px-2 focus:outline-none select-all truncate">
                <button type="button" @click="copyLink()" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all">
                    Copy
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
