@extends('layouts.app')

@section('content')
<div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8"
     x-data="{
         liked: {{ $article['is_liked'] ? 'true' : 'false' }},
         likeCount: {{ $article['likes_count'] }},
         saved: {{ $article['is_saved'] ? 'true' : 'false' }},
         saveCount: {{ (int)($article['saves_count'] ?? 0) }},
         heartAnimating: false,
         saveAnimating: false,
         heartPopping: false,
         shareModalOpen: false,
         newComment: '',
         isSubmittingComment: false,
         commentsList: {{ json_encode($comments) }},

         toggleLike() {
             this.heartAnimating = true;
             setTimeout(() => { this.heartAnimating = false; }, 650);
             this.liked = !this.liked;
             this.likeCount += this.liked ? 1 : -1;
             fetch('{{ url('api/articles/' . $article['id'] . '/like') }}', { method: 'POST' })
                 .then(res => res.json())
                 .then(data => {
                     this.liked = data.liked;
                     this.likeCount = data.count;
                     if (this.liked) {
                         $root.closest('body').__x.$data.showToast('You loved this story! ❤️');
                     }
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
                 $root.closest('body').__x.$data.guestActionText = 'bookmark stories to your reading list';
                 $root.closest('body').__x.$data.guestModalOpen = true;
                 return;
             @endguest

             this.saveAnimating = true;
             setTimeout(() => { this.saveAnimating = false; }, 650);
             this.saved = !this.saved;
             this.saveCount += this.saved ? 1 : -1;

             fetch('{{ url('api/articles/' . $article['id'] . '/save') }}', { method: 'POST' })
                 .then(res => res.json())
                 .then(data => {
                     this.saved = data.saved;
                     if (data.count !== undefined) this.saveCount = data.count;
                     const msg = this.saved ? 'Saved to your bookmarks! 🔖' : 'Removed from bookmarks';
                     $root.closest('body').__x.$data.showToast(msg);
                 });
         },

         addEmoji(emoji) {
             @guest
                 $root.closest('body').__x.$data.guestActionText = 'leave comments on stories';
                 $root.closest('body').__x.$data.guestModalOpen = true;
                 return;
             @endguest
             this.newComment += emoji + ' ';
             const el = document.getElementById('commentInput');
             if (el) el.focus();
         },

         postComment() {
             @guest
                 $root.closest('body').__x.$data.guestActionText = 'post comments on stories';
                 $root.closest('body').__x.$data.guestModalOpen = true;
                 return;
             @endguest

             if (!this.newComment.trim()) return;

             this.isSubmittingComment = true;
             const formData = new FormData();
             formData.append('content', this.newComment);

             fetch('{{ url('api/articles/' . $article['id'] . '/comment') }}', {
                 method: 'POST',
                 body: formData
             })
             .then(res => res.json())
             .then(data => {
                 this.isSubmittingComment = false;
                 if (data.success) {
                     this.commentsList.unshift(data.comment);
                     this.newComment = '';
                     $root.closest('body').__x.$data.showToast('Comment published! 💬');
                 } else {
                     $root.closest('body').__x.$data.showToast(data.error || 'Failed to post comment', 'error');
                 }
             })
             .catch(err => {
                 this.isSubmittingComment = false;
                 $root.closest('body').__x.$data.showToast('Network error', 'error');
             });
         },

         copyLink() {
             navigator.clipboard.writeText(window.location.href);
             $root.closest('body').__x.$data.showToast('Story link copied! 📋');
             this.shareModalOpen = false;
         }
     }">

    <!-- Back to Feed Navigator -->
    <div class="flex items-center justify-between">
        <a href="{{ url('') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Back to Feed</span>
        </a>

        <!-- Category Beat Badge & Reading Time -->
        <div class="flex items-center gap-2">
            <a href="{{ url('?category=' . $article['category_slug']) }}" 
               class="px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider text-white shadow-md hover:scale-105 transition-transform"
               style="background-color: {{ $article['category_color'] }};">
                {{ $article['category_name'] }}
            </a>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-white/10 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">schedule</span>
                <span>{{ reading_time($article['content'] ?? '') }}</span>
            </span>
        </div>
    </div>

    <!-- POST HEADER CARD (Instagram Profile Header Style) -->
    <div class="p-4 sm:p-6 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3.5">
            <div class="p-0.5 rounded-full story-gradient">
                <img src="{{ $article['author_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                     alt="{{ $article['author_name'] }}" 
                     class="w-12 h-12 rounded-full object-cover ring-2 ring-white dark:ring-background">
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="font-bold text-base text-slate-900 dark:text-white">
                        {{ '@' . strtolower(str_replace(' ', '', $article['author_name'])) }}
                    </span>
                    <span class="w-4 h-4 rounded-full bg-primary text-white flex items-center justify-center text-[10px]">✓</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-primary/10 text-primary uppercase ml-1">Creator</span>
                </div>
                <div class="text-xs text-slate-400">
                    {{ date('F j, Y', strtotime($article['published_at'] ?: $article['created_at'])) }} &bull; {{ reading_time($article['content'] ?? '') }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="shareModalOpen = true" 
                    class="p-2.5 rounded-2xl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 transition-all"
                    title="Share">
                <span class="material-symbols-outlined text-xl">send</span>
            </button>
            <button type="button" @click="toggleSave()" 
                    class="flex items-center gap-1.5 px-3 py-2 rounded-2xl transition-all"
                    :class="saved ? 'bg-primary text-white shadow-md shadow-primary/25 glow-primary' : 'bg-slate-100 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:text-primary'"
                    title="Save Story">
                <span class="material-symbols-outlined text-xl transition-colors"
                      :class="{
                          'font-fill': saved,
                          'animate-bookmark-glow': saveAnimating
                      }">bookmark</span>
                <span class="text-xs font-bold" x-text="saveCount"></span>
            </button>
        </div>
    </div>

    <!-- MAIN MEDIA (Carousel / Video / Cover Picture with Double-Tap Heart Pop) -->
    @php
        $carouselSlides = parse_carousel_images($article['carousel_images'] ?? null);
        $hasCarousel = !empty($carouselSlides) && count($carouselSlides) > 1;
        $isVideo = ($article['post_format'] ?? '') === 'video' || !empty($article['video_url']);
    @endphp

    @if($hasCarousel)
        <!-- Instagram Multi-Image Carousel -->
        <div class="relative w-full aspect-square sm:aspect-[16/10] rounded-3xl bg-black overflow-hidden shadow-2xl border border-slate-200 dark:border-white/10 select-none group"
             x-data="{
                 currentSlide: 0,
                 slides: {{ json_encode($carouselSlides) }},
                 nextSlide() { if (this.currentSlide < this.slides.length - 1) this.currentSlide++; },
                 prevSlide() { if (this.currentSlide > 0) this.currentSlide--; }
             }">
            
            <!-- Slides Strip -->
            <div class="w-full h-full flex transition-transform duration-500 ease-out"
                 :style="'transform: translateX(-' + (currentSlide * 100) + '%);'"
                 @dblclick="doubleTap()">
                <template x-for="(slide, sIdx) in slides" :key="sIdx">
                    <div class="w-full h-full shrink-0 relative bg-black">
                        <img :src="slide" alt="{{ $article['title'] }}" class="w-full h-full object-cover">
                    </div>
                </template>
            </div>

            <!-- Instagram Top-Right Counter Pill (e.g. 1/4) -->
            <div class="absolute top-4 right-4 px-3 py-1.5 rounded-full bg-black/60 backdrop-blur-md text-white text-xs font-bold flex items-center gap-1.5 shadow-md z-10 pointer-events-none">
                <span class="material-symbols-outlined text-sm">view_carousel</span>
                <span x-text="(currentSlide + 1) + '/' + slides.length"></span>
            </div>

            <!-- Instagram Bottom Pagination Dots -->
            <div class="absolute bottom-4 inset-x-0 flex items-center justify-center gap-2 z-10 pointer-events-none">
                <template x-for="(slide, sIdx) in slides" :key="'dot-' + sIdx">
                    <span class="h-2 rounded-full transition-all duration-300"
                          :class="currentSlide === sIdx ? 'bg-primary w-6 shadow-md' : 'bg-white/60 w-2'"></span>
                </template>
            </div>

            <!-- Navigation Arrows (< and >) -->
            <button type="button" @click.stop="prevSlide()" x-show="currentSlide > 0"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white flex items-center justify-center shadow-xl transition-all z-10 opacity-0 group-hover:opacity-100">
                <span class="material-symbols-outlined text-xl">chevron_left</span>
            </button>
            <button type="button" @click.stop="nextSlide()" x-show="currentSlide < slides.length - 1"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white flex items-center justify-center shadow-xl transition-all z-10 opacity-0 group-hover:opacity-100">
                <span class="material-symbols-outlined text-xl">chevron_right</span>
            </button>

            <!-- Animated Floating Heart on Like -->
            <div x-show="heartPopping" class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
                <span class="material-symbols-outlined text-white text-9xl drop-shadow-2xl animate-heart-pop font-fill text-rose-500">
                    favorite
                </span>
            </div>
        </div>
    @elseif($isVideo)
        <!-- Video Player -->
        <div class="relative w-full aspect-square sm:aspect-[16/10] rounded-3xl bg-black overflow-hidden shadow-2xl border border-slate-200 dark:border-white/10 group">
            @if(($article['video_type'] ?? '') === 'youtube' || preg_match('#(youtube\.com|youtu\.be)#i', $article['video_url']))
                <iframe src="{{ youtube_embed_url($article['video_url']) }}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                <span class="absolute top-4 left-4 px-3 py-1.5 rounded-full bg-rose-600/90 backdrop-blur-md text-white text-xs font-bold flex items-center gap-1.5 shadow-md pointer-events-none z-10">
                    <span class="material-symbols-outlined text-sm">smart_display</span>
                    <span>YouTube Video</span>
                </span>
            @else
                <video controls playsinline preload="metadata" class="w-full h-full object-cover">
                    <source src="{{ $article['video_url'] }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <span class="absolute top-4 left-4 px-3 py-1.5 rounded-full bg-rose-600/90 backdrop-blur-md text-white text-xs font-bold flex items-center gap-1.5 shadow-md pointer-events-none z-10">
                    <span class="material-symbols-outlined text-sm">videocam</span>
                    <span>Video</span>
                </span>
            @endif
        </div>
    @else
        <!-- MAIN MEDIA PICTURE (Every story has at least one picture with Double-Tap Heart Pop) -->
        <div class="relative w-full aspect-square sm:aspect-[16/10] rounded-3xl overflow-hidden shadow-2xl bg-black border border-slate-200 dark:border-white/10 select-none cursor-pointer group"
             @dblclick="doubleTap()">
            <img src="{{ $article['featured_image'] }}" alt="{{ $article['title'] }}" 
                 class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-500">
            
            <!-- Animated Floating Heart on Like -->
            <div x-show="heartPopping" class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
                <span class="material-symbols-outlined text-white text-9xl drop-shadow-2xl animate-heart-pop font-fill text-rose-500">
                    favorite
                </span>
            </div>
        </div>
    @endif

    <!-- PLAYFUL INSTAGRAM REACTION BAR -->
    <div class="p-3.5 sm:p-5 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 flex flex-wrap items-center justify-between gap-3 shadow-md">
        <div class="flex items-center gap-3 sm:gap-6 flex-wrap">
            <!-- Love (Like) -->
            <button type="button" @click="toggleLike()" 
                    class="flex items-center gap-1.5 sm:gap-2 transition-all focus:outline-none"
                    :class="liked ? 'text-rose-500 font-bold glow-red' : 'text-slate-700 dark:text-slate-300 hover:text-rose-500'">
                <span class="material-symbols-outlined text-2xl sm:text-3xl transition-colors"
                      :class="{
                          'font-fill text-rose-500 glow-red': liked,
                          'animate-heart-glow': heartAnimating
                      }">favorite</span>
                <span class="text-xs sm:text-sm font-black font-display"><span x-text="likeCount"></span><span class="hidden sm:inline">&nbsp;loves</span></span>
            </button>

            <!-- Comment Icon -->
            <a href="#comments" class="flex items-center gap-1.5 sm:gap-2 text-slate-700 dark:text-slate-300 hover:text-primary transition-transform active:scale-125">
                <span class="material-symbols-outlined text-2xl sm:text-3xl">chat_bubble</span>
                <span class="text-xs sm:text-sm font-black font-display"><span x-text="commentsList.length"></span><span class="hidden sm:inline">&nbsp;comments</span></span>
            </a>

            <!-- Save Story (Bookmark) -->
            <button type="button" @click="toggleSave()" 
                    class="flex items-center gap-1.5 sm:gap-2 transition-all focus:outline-none"
                    :class="saved ? 'text-primary font-bold glow-primary' : 'text-slate-700 dark:text-slate-300 hover:text-primary'"
                    title="Save Story">
                <span class="material-symbols-outlined text-2xl sm:text-3xl transition-colors"
                      :class="{
                          'font-fill text-primary glow-primary': saved,
                          'animate-bookmark-glow': saveAnimating
                      }">bookmark</span>
                <span class="text-xs sm:text-sm font-black font-display"><span x-text="saveCount"></span><span class="hidden sm:inline">&nbsp;saves</span></span>
            </button>

            <!-- Share -->
            <button type="button" @click="shareModalOpen = true" 
                    class="text-slate-700 dark:text-slate-300 hover:text-sky-400 transition-transform active:scale-125 focus:outline-none"
                    title="Share Story">
                <span class="material-symbols-outlined text-2xl sm:text-3xl">send</span>
            </button>
        </div>

        <!-- Views Counter -->
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-2xl bg-slate-100 dark:bg-white/5 text-slate-400 text-xs font-bold">
            <span class="material-symbols-outlined text-base sm:text-lg">visibility</span>
            <span>{{ number_format($article['views_count']) }}<span class="hidden sm:inline">&nbsp;views</span></span>
        </div>
    </div>

    <!-- ARTICLE HEADLINE & BODY -->
    <article class="space-y-6">
        <h1 class="text-3xl sm:text-5xl font-black font-display text-slate-900 dark:text-white tracking-tight leading-[1.15]">
            {{ $article['title'] }}
        </h1>

        <!-- Excerpt Subdeck -->
        <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-300 font-body leading-relaxed font-medium p-4 rounded-2xl bg-primary/5 border-l-4 border-primary">
            {{ $article['excerpt'] }}
        </p>

        <!-- Rich Editorial Content -->
        <div class="prose prose-lg dark:prose-invert max-w-none text-slate-800 dark:text-slate-200 leading-relaxed font-body py-4">
            {!! $article['content'] !!}
        </div>
    </article>


    <!-- INTERACTIVE INSTAGRAM-STYLE POLL / QUIZ (No Login Required) -->
    @if(!empty($article['poll']))
        <div x-data="{
            poll: {{ json_encode($article['poll']) }},
            isVoting: false,
            vote(optionId) {
                if (this.poll.has_voted || this.isVoting) return;
                this.isVoting = true;
                fetch('{{ url('api/polls') }}/' + this.poll.id + '/vote', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ option_id: optionId })
                })
                .then(res => res.json())
                .then(data => {
                    this.isVoting = false;
                    if (data.success && data.poll) {
                        this.poll = data.poll;
                        const body = document.querySelector('body');
                        if (body && body.__x && body.__x.$data.showToast) {
                            body.__x.$data.showToast(data.message || 'Vote registered!');
                        }
                    }
                })
                .catch(() => { this.isVoting = false; });
            }
        }" class="my-8 p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-purple-500/10 via-indigo-500/5 to-slate-100 dark:to-white/5 border border-purple-500/20 shadow-lg space-y-4">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                      :class="poll.poll_type === 'quiz' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/20'">
                    <span class="material-symbols-outlined text-sm" x-text="poll.poll_type === 'quiz' ? 'quiz' : 'how_to_vote'"></span>
                    <span x-text="poll.poll_type === 'quiz' ? 'Interactive Quiz' : 'Community Poll'"></span>
                </span>
                <span class="text-xs font-bold text-slate-400" x-text="poll.total_votes + ' total votes'"></span>
            </div>

            <div>
                <h3 class="text-lg sm:text-2xl font-black font-display text-slate-900 dark:text-white leading-snug" x-text="poll.question"></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="poll.poll_type === 'quiz' ? 'Test your knowledge on this story! Tap an answer below.' : 'Share your voice! Vote on this issue below.'"></p>
            </div>

            <div class="space-y-3 pt-2">
                <template x-for="opt in poll.options" :key="opt.id">
                    <button type="button" @click="vote(opt.id)"
                            :disabled="poll.has_voted || isVoting"
                            class="relative w-full p-3.5 sm:p-4 rounded-2xl border text-sm font-bold transition-all flex items-center justify-between text-left overflow-hidden group focus:outline-none"
                            :class="{
                                'cursor-pointer hover:border-purple-400 dark:hover:border-purple-400/50 hover:shadow-md bg-white dark:bg-surface-card border-slate-200 dark:border-white/10 text-slate-800 dark:text-slate-100': !poll.has_voted,
                                'cursor-default border-slate-200 dark:border-white/10': poll.has_voted,
                                'ring-2 ring-emerald-500 border-emerald-500 font-black': poll.has_voted && poll.poll_type === 'quiz' && opt.is_correct,
                                'ring-2 ring-rose-500 border-rose-500': poll.has_voted && poll.poll_type === 'quiz' && poll.user_voted_option_id === opt.id && !opt.is_correct,
                                'ring-2 ring-purple-500 border-purple-500': poll.has_voted && poll.poll_type === 'poll' && poll.user_voted_option_id === opt.id
                            }">
                        <!-- Animated Fill Bar when voted -->
                        <div x-show="poll.has_voted"
                             class="absolute inset-y-0 left-0 transition-all duration-700 pointer-events-none"
                             :style="'width: ' + opt.percentage + '%;'"
                             :class="{
                                 'bg-emerald-500/25 dark:bg-emerald-500/35': poll.poll_type === 'quiz' && opt.is_correct,
                                 'bg-rose-500/20 dark:bg-rose-500/30': poll.poll_type === 'quiz' && poll.user_voted_option_id === opt.id && !opt.is_correct,
                                 'bg-purple-500/25 dark:bg-purple-500/35': poll.poll_type === 'poll' && poll.user_voted_option_id === opt.id,
                                 'bg-slate-200/70 dark:bg-white/10': (poll.poll_type !== 'quiz' || !opt.is_correct) && poll.user_voted_option_id !== opt.id
                             }"></div>

                        <div class="relative z-10 flex items-center gap-2.5">
                            <span x-show="poll.has_voted && poll.poll_type === 'quiz' && opt.is_correct" class="text-emerald-500 font-bold text-base">✓</span>
                            <span x-show="poll.has_voted && poll.poll_type === 'quiz' && poll.user_voted_option_id === opt.id && !opt.is_correct" class="text-rose-500 font-bold text-base">✗</span>
                            <span x-text="opt.option_text"></span>
                        </div>

                        <div class="relative z-10 font-mono text-sm font-black shrink-0" x-show="poll.has_voted">
                            <span x-text="opt.percentage + '%'"></span>
                        </div>
                    </button>
                </template>
            </div>

            <!-- Poll footer caption -->
            <div class="flex items-center justify-between text-xs text-slate-400 pt-1" x-show="poll.has_voted">
                <span x-text="poll.poll_type === 'quiz' ? (poll.options.find(o => o.id === poll.user_voted_option_id && o.is_correct) ? '🎉 Great job! Correct answer.' : '💡 Good try! See correct answer highlighted in green.') : '✨ Thanks for participating in the community poll!'"></span>
                <span class="font-medium">Instant results</span>
            </div>
        </div>
    @endif

    <!-- COMMENTS SECTION (Changed from discussion to comment) -->
    <section id="comments" class="pt-8 border-t border-slate-200 dark:border-white/10 space-y-6">
        
        <!-- Section Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-primary to-indigo-500 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-primary/25">
                    <span class="material-symbols-outlined text-xl">chat_bubble</span>
                </span>
                <h3 class="text-2xl font-black font-display text-slate-900 dark:text-white">
                    Comments (<span x-text="commentsList.length"></span>)
                </h3>
            </div>
            <span class="text-xs text-slate-400 font-semibold">Keep it respectful & authentic</span>
        </div>

        <!-- Quick Emoji Reaction Bar (Playful Instagram feature) -->
        <div class="p-3 rounded-2xl glass-panel border border-slate-200 dark:border-white/10 flex items-center gap-2 overflow-x-auto scrollbar-none">
            <span class="text-xs font-bold text-slate-400 pl-1 pr-2 whitespace-nowrap">Tap emoji:</span>
            <button type="button" @click="addEmoji('🔥')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">🔥</button>
            <button type="button" @click="addEmoji('❤️')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">❤️</button>
            <button type="button" @click="addEmoji('💀')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">💀</button>
            <button type="button" @click="addEmoji('👏')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">👏</button>
            <button type="button" @click="addEmoji('🚀')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">🚀</button>
            <button type="button" @click="addEmoji('💯')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">💯</button>
            <button type="button" @click="addEmoji('😭')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">😭</button>
            <button type="button" @click="addEmoji('⚡')" class="text-xl p-1.5 hover:scale-130 transition-transform active:scale-95">⚡</button>
        </div>

        <!-- Comment Input Box -->
        <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 shadow-lg">
            @auth
                <div class="flex items-start gap-3.5">
                    <img src="{{ auth()->user()['avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                         alt="{{ auth()->user()['name'] }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-primary/40 mt-1">
                    <div class="flex-1">
                        <textarea id="commentInput" x-model="newComment" rows="2" placeholder="Write a comment as {{ auth()->user()['name'] }}..." 
                                  class="w-full p-3 rounded-2xl bg-slate-100 dark:bg-surface-card border border-transparent focus:border-primary/50 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 text-slate-900 dark:text-white placeholder-slate-400 transition-all mb-3 resize-none"></textarea>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400">Press post or send</span>
                            <button type="button" @click="postComment()" :disabled="isSubmittingComment || !newComment.trim()"
                                    class="px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover disabled:opacity-40 text-white font-bold text-xs shadow-lg shadow-primary/25 transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">send</span>
                                <span x-text="isSubmittingComment ? 'Posting...' : 'Post Comment'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            @endauth

            @guest
                <div class="text-center py-5">
                    <p class="text-sm text-slate-400 mb-3">Sign in to leave comments and interact with the creator.</p>
                    <a href="{{ url('login') }}" 
                       class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-lg shadow-primary/25 transition-all">
                        <span class="material-symbols-outlined text-sm">login</span>
                        <span>Sign in to comment!</span>
                    </a>
                </div>
            @endguest
        </div>

        <!-- Comments Feed (Instagram Style Feed) -->
        <div class="space-y-3 pt-2">
            <template x-for="item in commentsList" :key="item.id">
                <div class="p-4 sm:p-5 rounded-2xl glass-panel border border-slate-200 dark:border-white/5 transition-all flex items-start gap-3">
                    <img :src="item.user_avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'" 
                         :alt="item.user_name" class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-0.5">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-900 dark:text-white" x-text="item.user_name"></span>
                                <span x-show="item.role_name && item.role_name !== 'reader_registered'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-primary/10 text-primary uppercase" x-text="item.role_name"></span>
                            </div>
                            <span class="text-[10px] text-slate-400" x-text="item.time_ago || item.created_at"></span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-body" x-text="item.content"></p>
                    </div>
                </div>
            </template>
        </div>

    </section>

    <!-- RELATED DISPATCHES -->
    @if(!empty($related))
    <section class="border-t border-slate-200 dark:border-white/10 pt-10">
        <h3 class="text-xl font-black font-display text-slate-900 dark:text-white mb-6">More Stories from {{ $article['category_name'] }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($related as $rel)
                <a href="{{ url('articles/' . $rel['slug']) }}" class="group block rounded-3xl overflow-hidden glass-panel border border-slate-200 dark:border-white/5 hover:border-primary/50 transition-all p-3">
                    <div class="aspect-square w-full rounded-2xl overflow-hidden mb-3 bg-black">
                        <img src="{{ $rel['featured_image'] }}" alt="{{ $rel['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2 leading-snug mb-1">
                        {{ $rel['title'] }}
                    </h4>
                    <span class="text-[10px] text-slate-400">{{ date('M d, Y', strtotime($rel['published_at'])) }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- SHARE DRAWER MODAL -->
    <div x-cloak x-show="shareModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-md" @click="shareModalOpen = false"></div>
        <div class="relative w-full max-w-md p-6 rounded-3xl glass-panel shadow-2xl border border-white/10 z-10 animate-in fade-in zoom-in-95">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">send</span>
                    </span>
                    <h4 class="font-black font-display text-lg text-white">Share This Story</h4>
                </div>
                <button type="button" @click="shareModalOpen = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <p class="text-xs text-slate-300 font-bold line-clamp-2 mb-6">{{ $article['title'] }}</p>

            <div class="grid grid-cols-4 gap-3 mb-6">
                <!-- X / Twitter -->
                <a :href="'https://twitter.com/intent/tweet?text=' + encodeURIComponent('{{ addslashes($article['title']) }}') + '&url=' + encodeURIComponent(window.location.href)" 
                   target="_blank" class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-white/5 hover:bg-white/10 text-white transition-all">
                    <span class="text-xl font-bold font-display">𝕏</span>
                    <span class="text-[10px] font-semibold text-slate-400">Post</span>
                </a>

                <!-- WhatsApp -->
                <a :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent('{{ addslashes($article['title']) }} - ' + window.location.href)" 
                   target="_blank" class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 transition-all">
                    <span class="material-symbols-outlined text-2xl">chat</span>
                    <span class="text-[10px] font-semibold text-emerald-300">WhatsApp</span>
                </a>

                <!-- Telegram -->
                <a :href="'https://t.me/share/url?url=' + encodeURIComponent(window.location.href) + '&text=' + encodeURIComponent('{{ addslashes($article['title']) }}')" 
                   target="_blank" class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 transition-all">
                    <span class="material-symbols-outlined text-2xl">send</span>
                    <span class="text-[10px] font-semibold text-sky-300">Telegram</span>
                </a>

                <!-- LinkedIn -->
                <a :href="'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(window.location.href)" 
                   target="_blank" class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 transition-all">
                    <span class="material-symbols-outlined text-2xl">work</span>
                    <span class="text-[10px] font-semibold text-blue-300">LinkedIn</span>
                </a>
            </div>

            <!-- Direct Copy Button -->
            <button type="button" @click="copyLink()" class="w-full py-3.5 px-4 rounded-2xl bg-primary hover:bg-primary-hover text-white font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-primary/25 transition-all">
                <span class="material-symbols-outlined text-base">content_copy</span>
                <span>Copy Story Link</span>
            </button>
        </div>
    </div>

</div>
@endsection
