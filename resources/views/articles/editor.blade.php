@extends('layouts.app')

@section('content')
<!-- CKEditor 5 CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

<style>
    /* Custom CKEditor 5 Dark/Light Theme Styling */
    .ck-editor__editable_inline {
        min-height: 400px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        line-height: 1.75;
        border-bottom-left-radius: 1.5rem !important;
        border-bottom-right-radius: 1.5rem !important;
        padding: 1.5rem !important;
    }
    .dark .ck.ck-editor__main>.ck-editor__editable {
        background: #171b26 !important;
        color: #e2e8f0 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .dark .ck.ck-toolbar {
        background: #1c1f2a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        border-top-left-radius: 1.5rem !important;
        border-top-right-radius: 1.5rem !important;
    }
    .dark .ck.ck-toolbar .ck-button, .dark .ck.ck-dropdown__button {
        color: #94a3b8 !important;
    }
    .dark .ck.ck-toolbar .ck-button:hover, .dark .ck.ck-dropdown__button:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #fff !important;
    }
    .dark .ck.ck-dropdown__panel {
        background: #1c1f2a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .dark .ck.ck-list__item .ck-button {
        color: #cbd5e1 !important;
    }
    .dark .ck.ck-list__item .ck-button:hover {
        background: #262a35 !important;
    }
</style>

<div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8"
     x-data="{
         aiModalOpen: false,
         aiActiveSection: 'ideas', // 'ideas' | 'draft' | 'title' | 'rephrase' | 'grammar'
         aiLanguage: 'en', // 'en' (default) | 'id' | 'other'
         aiLoading: false,
         aiPrompt: '',
         aiNotes: '',
         aiWhat: '',
         aiWho: '',
         aiWhere: '',
         aiWhen: '',
         aiWhy: '',
         aiHow: '',
         aiInterviewQuotes: '',
         aiSelectedText: '',
         aiResult: '',
         aiSource: '',
         aiError: '',
         aiViewMode: 'preview', // 'preview' | 'code'
         aiSettingsOpen: false,
         aiApiKeyInput: '',
         aiModelInput: 'gemini-3.5-flash',
         aiHasKey: false,
         aiEngineName: 'Detecting AI Engine...',
         aiKeySaving: false,
         aiKeyMessage: '',

         toggleAiSection(key) {
             this.aiActiveSection = (this.aiActiveSection === key) ? null : key;
         },

         fetchAiStatus() {
             fetch('{{ url('api/ai/status') }}')
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         this.aiHasKey = data.has_key;
                         this.aiModelInput = data.model || 'gemini-3.5-flash';
                         this.aiEngineName = data.engine;
                     }
                 })
                 .catch(() => {});
         },

         saveAiKey() {
             this.aiKeySaving = true;
             this.aiKeyMessage = '';
             fetch('{{ url('api/ai/save-key') }}', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify({
                     api_key: this.aiApiKeyInput,
                     model: this.aiModelInput
                 })
             })
             .then(res => res.json())
             .then(data => {
                 this.aiKeySaving = false;
                 if (data.success) {
                     this.aiHasKey = data.has_key;
                     this.aiEngineName = data.engine;
                     this.aiKeyMessage = data.message;
                     this.aiApiKeyInput = '';
                     setTimeout(() => { this.aiSettingsOpen = false; this.aiKeyMessage = ''; }, 2000);
                     const body = document.querySelector('body');
                     if (body && body.__x && body.__x.$data.showToast) {
                         body.__x.$data.showToast(data.message);
                     }
                 } else {
                     this.aiKeyMessage = data.error || 'Failed to save credentials.';
                 }
             })
             .catch(() => {
                 this.aiKeySaving = false;
                 this.aiKeyMessage = 'Server connection error.';
             });
         },

         openAiModal(section = 'ideas') {
             this.aiActiveSection = section;
             this.aiModalOpen = true;
             this.aiError = '';
             this.fetchAiStatus();

             // Auto fill topic from article title if prompt is empty
             const titleInput = document.getElementById('articleTitle');
             if (!this.aiPrompt && titleInput && titleInput.value.trim() !== '') {
                 this.aiPrompt = titleInput.value.trim();
             }

             // Get selection from CKEditor if available
             if (window.brighteenEditor) {
                 const selection = window.brighteenEditor.model.document.selection;
                 const range = selection.getFirstRange();
                 if (range) {
                     let text = '';
                     for (const item of range.getItems()) {
                         if (item.data) text += item.data;
                     }
                     if (text) this.aiSelectedText = text;
                 }
             }
         },

         runAiAction(actionName) {
             this.aiLoading = true;
             this.aiError = '';
             this.aiResult = '';

             const titleVal = document.getElementById('articleTitle') ? document.getElementById('articleTitle').value : '';
             const excerptVal = document.getElementById('articleExcerpt') ? document.getElementById('articleExcerpt').value : '';
             let editorContent = window.brighteenEditor ? window.brighteenEditor.getData() : '';

             // Strip HTML tags for text analysis
             let plainContent = editorContent.replace(/<[^>]*>?/gm, '');

             const payload = {
                 action: actionName,
                 prompt: this.aiPrompt,
                 topic: this.aiPrompt || titleVal,
                 notes: this.aiNotes,
                 what: this.aiWhat,
                 who: this.aiWho,
                 where: this.aiWhere,
                 when: this.aiWhen,
                 why: this.aiWhy,
                 how: this.aiHow,
                 interview_quotes: this.aiInterviewQuotes,
                 text: this.aiSelectedText || plainContent || excerptVal || titleVal,
                 niche: this.aiPrompt || 'School Life & Hobbies',
                 language: this.aiLanguage || 'en'
             };

             fetch('{{ url('api/ai/generate') }}', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify(payload)
             })
             .then(res => res.json())
             .then(data => {
                 this.aiLoading = false;
                 if (data.success) {
                     this.aiResult = data.result;
                     this.aiSource = data.source;
                 } else {
                     this.aiError = data.error || 'AI generation failed.';
                 }
             })
             .catch(err => {
                 this.aiLoading = false;
                 this.aiError = 'Network communication failure with AI service.';
             });
         },

         insertIntoEditor() {
             if (window.brighteenEditor && this.aiResult) {
                 // Insert or replace in CKEditor
                 const viewFragment = window.brighteenEditor.data.processor.toView(this.aiResult);
                 const modelFragment = window.brighteenEditor.data.toModel(viewFragment);
                 window.brighteenEditor.model.insertContent(modelFragment);
                 const body = document.querySelector('body');
                 if (body && body.__x && body.__x.$data.showToast) {
                     body.__x.$data.showToast('AI content inserted into editor!');
                 }
                 this.aiModalOpen = false;
             }
         },

         copyResult() {
             if (this.aiResult) {
                 navigator.clipboard.writeText(this.aiResult).then(() => {
                     const body = document.querySelector('body');
                     if (body && body.__x && body.__x.$data.showToast) {
                         body.__x.$data.showToast('Copied to clipboard!');
                     }
                 });
             }
         },

         applyTitle(suggestedTitle) {
             const titleInput = document.getElementById('articleTitle');
             if (titleInput) {
                 titleInput.value = suggestedTitle;
                 const body = document.querySelector('body');
                 if (body && body.__x && body.__x.$data.showToast) {
                     body.__x.$data.showToast('Title updated!');
                 }
             }
         },

         applyExcerpt(suggestedExcerpt) {
             const excerptInput = document.getElementById('articleExcerpt');
             if (excerptInput) {
                 excerptInput.value = suggestedExcerpt;
                 const body = document.querySelector('body');
                 if (body && body.__x && body.__x.$data.showToast) {
                     body.__x.$data.showToast('Excerpt updated!');
                 }
             }
         },

         // --- Media, Carousel, Video & Poll Extensions ---
         postFormat: '{{ $article['post_format'] ?? 'article' }}',
         coverImageUrl: '{{ $article['featured_image'] ?? '' }}',
         videoUrl: '{{ $article['video_url'] ?? '' }}',
         videoType: '{{ $article['video_type'] ?? 'youtube' }}',
         carouselImages: {!! json_encode(!empty($article['carousel_images']) ? parse_carousel_images($article['carousel_images']) : []) !!},
         carouselInputUrl: '',
         isUploadingCarousel: false,

         // Video Embed Modal State
         videoModalOpen: false,
         videoTab: 'youtube', // 'youtube' | 'upload'
         youtubeInputUrl: '',
         isUploadingVideo: false,
         videoUploadError: '',

         // Poll / Quiz State
         pollEnabled: {{ (!empty($poll) || ($article['post_format'] ?? '') === 'poll') ? 'true' : 'false' }},
         pollType: '{{ $poll['poll_type'] ?? 'poll' }}', // 'poll' | 'quiz'
         pollQuestion: {!! json_encode($poll['question'] ?? '') !!},
         pollOptions: {!! json_encode(!empty($poll['options']) ? array_column($poll['options'], 'option_text') : ['', '']) !!},
         pollCorrectIndex: {{ !empty($poll['options']) ? (function() use ($poll) { foreach($poll['options'] as $i => $opt) { if (!empty($opt['is_correct'])) return $i; } return 0; })() : 0 }},

         getYoutubeId(url) {
             if (!url) return null;
             const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?.*?v=|shorts\/)([^#&?]*).*/;
             const match = url.match(regExp);
             return (match && match[2].length === 11) ? match[2] : null;
         },

         getYoutubeEmbedUrl(url) {
             const id = this.getYoutubeId(url);
             return id ? 'https://www.youtube-nocookie.com/embed/' + id : '';
         },

         openVideoModal() {
             this.videoModalOpen = true;
             this.videoUploadError = '';
         },

         insertVideoIntoEditor(htmlSnippet) {
             if (window.brighteenEditor) {
                 const viewFragment = window.brighteenEditor.data.processor.toView(htmlSnippet);
                 const modelFragment = window.brighteenEditor.data.toModel(viewFragment);
                 window.brighteenEditor.model.insertContent(modelFragment);
                 const body = document.querySelector('body');
                 if (body && body.__x && body.__x.$data.showToast) {
                     body.__x.$data.showToast('Video embedded into story content!');
                 }
             }
         },

         embedYoutubeVideo(setAsFeatured = false) {
             const ytId = this.getYoutubeId(this.youtubeInputUrl);
             if (!ytId) {
                 this.videoUploadError = 'Please enter a valid YouTube or YouTube Shorts URL.';
                 return;
             }
             const embedUrl = 'https://www.youtube-nocookie.com/embed/' + ytId;
             const snippet = '<div class="video-embed-box my-6 aspect-video w-full rounded-2xl overflow-hidden shadow-xl border border-slate-200 dark:border-white/10"><iframe src="' + embedUrl + '" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
             
             this.insertVideoIntoEditor(snippet);

             if (setAsFeatured || this.postFormat === 'video') {
                 this.videoUrl = this.youtubeInputUrl;
                 this.videoType = 'youtube';
                 if (!this.coverImageUrl) {
                     this.coverImageUrl = 'https://img.youtube.com/vi/' + ytId + '/hqdefault.jpg';
                 }
             }
             this.youtubeInputUrl = '';
             this.videoModalOpen = false;
         },

         uploadVideoFile(event, setAsFeatured = false) {
             const file = event.target.files[0];
             if (!file) return;

             this.isUploadingVideo = true;
             this.videoUploadError = '';

             const formData = new FormData();
             formData.append('video', file);

             fetch('{{ url('api/upload-video') }}', {
                 method: 'POST',
                 body: formData
             })
             .then(res => res.json())
             .then(data => {
                 this.isUploadingVideo = false;
                 if (data.url) {
                     const snippet = '<div class="video-player-box my-6 rounded-2xl overflow-hidden shadow-xl bg-black border border-slate-200 dark:border-white/10"><video controls class="w-full max-h-[500px]" playsinline preload="metadata"><source src="' + data.url + '" type="video/mp4">Your browser does not support video.</video></div>';
                     this.insertVideoIntoEditor(snippet);

                     if (setAsFeatured || this.postFormat === 'video') {
                         this.videoUrl = data.url;
                         this.videoType = 'upload';
                     }
                     this.videoModalOpen = false;
                     event.target.value = '';
                 } else {
                     this.videoUploadError = data.error?.message || 'Video upload failed.';
                 }
             })
             .catch(() => {
                 this.isUploadingVideo = false;
                 this.videoUploadError = 'Network error while uploading video.';
             });
         },

         uploadCarouselImages(event) {
             const files = event.target.files;
             if (!files || files.length === 0) return;

             this.isUploadingCarousel = true;
             const formData = new FormData();
             for (let i = 0; i < files.length; i++) {
                 formData.append('images[]', files[i]);
             }

             fetch('{{ url('api/upload-carousel') }}', {
                 method: 'POST',
                 body: formData
             })
             .then(res => res.json())
             .then(data => {
                 this.isUploadingCarousel = false;
                 if (data.urls && data.urls.length) {
                     data.urls.forEach(url => this.carouselImages.push(url));
                     if (!this.coverImageUrl && this.carouselImages.length) {
                         this.coverImageUrl = this.carouselImages[0];
                     }
                     const body = document.querySelector('body');
                     if (body && body.__x && body.__x.$data.showToast) {
                         body.__x.$data.showToast(data.urls.length + ' slides added to carousel!');
                     }
                 }
                 event.target.value = '';
             })
             .catch(() => {
                 this.isUploadingCarousel = false;
                 alert('Error uploading carousel images.');
             });
         },

         addCarouselUrl() {
             if (!this.carouselInputUrl.trim()) return;
             this.carouselImages.push(this.carouselInputUrl.trim());
             if (!this.coverImageUrl) {
                 this.coverImageUrl = this.carouselInputUrl.trim();
             }
             this.carouselInputUrl = '';
         },

         removeCarouselImage(idx) {
             this.carouselImages.splice(idx, 1);
         },

         moveCarouselImage(idx, dir) {
             const target = idx + dir;
             if (target < 0 || target >= this.carouselImages.length) return;
             const item = this.carouselImages.splice(idx, 1)[0];
             this.carouselImages.splice(target, 0, item);
         },

         addPollOption() {
             if (this.pollOptions.length < 4) {
                 this.pollOptions.push('');
             }
         },

         removePollOption(idx) {
             if (this.pollOptions.length > 2) {
                 this.pollOptions.splice(idx, 1);
                 if (this.pollCorrectIndex >= this.pollOptions.length) {
                     this.pollCorrectIndex = this.pollOptions.length - 1;
                 }
             }
         }
     }">

    <!-- Page Header & Action Bar -->
    <form id="articleForm" method="POST" action="{{ $isEdit ? url('articles/' . $article['id'] . '/update') : url('articles') }}" class="space-y-8">
        
        <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-white/10">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider mb-1">
                    <span class="material-symbols-outlined text-base">edit_note</span>
                    <span>Writer Workbench</span>
                </div>
                <h1 class="text-3xl font-black font-display text-slate-900 dark:text-white">
                    {{ $isEdit ? 'Edit Dispatch' : 'Draft New Story' }}
                </h1>
            </div>

            <!-- Action Controls -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
                <!-- AI Assistant Trigger Button -->
                <button type="button" @click="openAiModal('ideas')" 
                        class="px-3.5 sm:px-4 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-primary hover:from-purple-700 hover:to-primary-hover text-white font-bold text-xs shadow-lg shadow-purple-500/25 transition-all flex items-center gap-1.5 sm:gap-2 transform hover:-translate-y-0.5">
                    <span class="material-symbols-outlined text-base sm:text-lg animate-pulse">auto_awesome</span>
                    <span>Gemini AI Assistant</span>
                </button>

                <!-- Save Draft -->
                <button type="submit" name="action" value="save_draft" 
                        class="px-3.5 sm:px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card hover:bg-slate-200 dark:hover:bg-surface-high text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-white/10 transition-all">
                    Save Draft
                </button>

                <!-- Review / Publish according to RBAC -->
                @if(auth()->canPublishDirectly())
                    <button type="submit" name="action" value="publish" 
                            class="px-4 sm:px-5 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs shadow-lg shadow-emerald-500/25 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">publish</span>
                        <span>Publish Live</span>
                    </button>
                @else
                    <button type="submit" name="action" value="submit_review" 
                            class="px-4 sm:px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-lg shadow-primary/25 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>Submit for Editorial Review</span>
                    </button>
                @endif
            </div>
        </div>

        @if(!empty($error))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <span>{{ $error }}</span>
            </div>
        @endif

        @if(!empty($selectedAssignment))
            <!-- Assignment Briefing Banner -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-primary/10 via-indigo-500/10 to-purple-500/10 border border-primary/30 flex items-start sm:items-center justify-between gap-4 flex-wrap">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-primary/20 text-primary flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                        <span class="material-symbols-outlined text-2xl">assignment</span>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-primary/20 text-primary">Turn-in Assignment #{{ $selectedAssignment['id'] }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Assigned by <strong class="text-slate-900 dark:text-white">{{ $selectedAssignment['editor_name'] ?? 'Editor' }}</strong></span>
                        </div>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ $selectedAssignment['topic'] }}</h4>
                        @if(!empty($selectedAssignment['description']))
                            <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 max-w-2xl">{{ $selectedAssignment['description'] }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-left sm:text-right text-xs">
                    <div class="text-slate-500 dark:text-slate-400">Submission Deadline:</div>
                    <div class="font-bold text-amber-600 dark:text-amber-400">{{ date('M j, Y - H:i', strtotime($selectedAssignment['submission_deadline'])) }}</div>
                </div>
            </div>
        @endif

        @if($isEdit && !empty($article['editorial_feedback']))
            <div class="p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm">
                <div class="font-bold flex items-center gap-1.5 mb-1 text-amber-400">
                    <span class="material-symbols-outlined text-base">feedback</span>
                    <span>Editorial Reviewer Notes:</span>
                </div>
                <p>{{ $article['editorial_feedback'] }}</p>
            </div>
        @endif

        <!-- Main Form Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Title & CKEditor (2 Cols) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Hidden inputs for media & formats -->
                <input type="hidden" name="post_format" :value="postFormat">
                <input type="hidden" name="video_url" :value="videoUrl">
                <input type="hidden" name="video_type" :value="videoType">
                <input type="hidden" name="carousel_images" :value="JSON.stringify(carouselImages)">

                <!-- Story Format Selector -->
                <div class="p-4 sm:p-5 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 shadow-xs">
                    <div class="flex items-center justify-between mb-2.5">
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400">Story Format</label>
                        <span class="text-[10px] text-slate-400 font-medium">Select primary media layout</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <button type="button" @click="postFormat = 'article'"
                                class="p-3 rounded-2xl border text-left transition-all flex flex-col gap-1.5 focus:outline-none"
                                :class="postFormat === 'article' ? 'bg-primary/10 border-primary text-primary font-bold shadow-xs ring-1 ring-primary' : 'bg-slate-50 dark:bg-surface-card border-slate-200 dark:border-white/5 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20'">
                            <span class="material-symbols-outlined text-2xl">article</span>
                            <div>
                                <div class="text-xs font-bold">Standard</div>
                                <div class="text-[10px] opacity-75">Article story</div>
                            </div>
                        </button>
                        <button type="button" @click="postFormat = 'carousel'"
                                class="p-3 rounded-2xl border text-left transition-all flex flex-col gap-1.5 focus:outline-none"
                                :class="postFormat === 'carousel' ? 'bg-indigo-500/10 border-indigo-500 text-indigo-500 font-bold shadow-xs ring-1 ring-indigo-500' : 'bg-slate-50 dark:bg-surface-card border-slate-200 dark:border-white/5 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20'">
                            <span class="material-symbols-outlined text-2xl">view_carousel</span>
                            <div>
                                <div class="text-xs font-bold">Carousel</div>
                                <div class="text-[10px] opacity-75">Multi-image slides</div>
                            </div>
                        </button>
                        <button type="button" @click="postFormat = 'video'; if(!videoUrl) openVideoModal()"
                                class="p-3 rounded-2xl border text-left transition-all flex flex-col gap-1.5 focus:outline-none"
                                :class="postFormat === 'video' ? 'bg-rose-500/10 border-rose-500 text-rose-500 font-bold shadow-xs ring-1 ring-rose-500' : 'bg-slate-50 dark:bg-surface-card border-slate-200 dark:border-white/5 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20'">
                            <span class="material-symbols-outlined text-2xl">play_circle</span>
                            <div>
                                <div class="text-xs font-bold">Video Story</div>
                                <div class="text-[10px] opacity-75">YouTube / MP4</div>
                            </div>
                        </button>
                        <button type="button" @click="postFormat = 'poll'; pollEnabled = true"
                                class="p-3 rounded-2xl border text-left transition-all flex flex-col gap-1.5 focus:outline-none"
                                :class="postFormat === 'poll' ? 'bg-purple-500/10 border-purple-500 text-purple-500 font-bold shadow-xs ring-1 ring-purple-500' : 'bg-slate-50 dark:bg-surface-card border-slate-200 dark:border-white/5 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20'">
                            <span class="material-symbols-outlined text-2xl">how_to_vote</span>
                            <div>
                                <div class="text-xs font-bold">Poll / Quiz</div>
                                <div class="text-[10px] opacity-75">Interactive sticker</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Title Field -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Headline</label>
                    <div class="relative">
                        <input type="text" id="articleTitle" name="title" required
                               value="{{ $article['title'] ?? '' }}"
                               placeholder="Write a punchy, scroll-stopping title..." 
                               class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white font-display font-black text-xl focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                        <button type="button" @click="openAiModal('title')" title="Optimize Title with AI"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-2 rounded-xl text-purple-400 hover:text-purple-300 hover:bg-purple-500/10 transition-all">
                            <span class="material-symbols-outlined text-lg">auto_awesome</span>
                        </button>
                    </div>
                </div>

                <!-- Rich Text CKEditor Container -->
                <div class="relative">
                    <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                        <label class="text-xs font-black uppercase tracking-wider text-slate-400">Article Content</label>
                        <!-- Quick Editor Actions toolbar above editor -->
                        <div class="flex items-center gap-2.5 text-xs">
                            <button type="button" @click="openVideoModal()" class="text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 font-bold flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-base">play_circle</span> Embed Video
                            </button>
                            <span class="text-slate-400">&bull;</span>
                            <button type="button" @click="openAiModal('rephrase')" class="text-purple-400 hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">magic_button</span> Rephrase
                            </button>
                            <span class="text-slate-400">&bull;</span>
                            <button type="button" @click="openAiModal('grammar')" class="text-purple-400 hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">spellcheck</span> Tone Audit
                            </button>
                        </div>
                    </div>

                    <textarea id="editor" name="content">{{ $article['content'] ?? '' }}</textarea>

                    <!-- Floating AI Shortcut Widget at bottom right of editor -->
                    <div class="absolute bottom-4 right-4 z-20">
                        <button type="button" @click="openAiModal('draft')"
                                class="flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-surface-card/90 hover:bg-surface-high border border-purple-500/40 text-purple-300 text-xs font-bold shadow-xl backdrop-blur-md transition-all hover:scale-105">
                            <span class="material-symbols-outlined text-base text-purple-400">auto_awesome</span>
                            <span>AI Tools</span>
                        </button>
                    </div>
                </div>

                <!-- Interactive Instagram Poll / Quiz Creator -->
                <div class="p-6 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-2xl bg-purple-500/10 text-purple-500 flex items-center justify-center">
                                <span class="material-symbols-outlined text-xl">how_to_vote</span>
                            </span>
                            <div>
                                <h3 class="text-sm font-black font-display text-slate-900 dark:text-white">Instagram-Style Poll / Quiz</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Anyone can vote directly on the feed without logging in</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="pollEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-surface-high peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>

                    <!-- Hidden inputs for Poll submission when enabled -->
                    <template x-if="pollEnabled && pollQuestion.trim() !== ''">
                        <div>
                            <input type="hidden" name="poll_question" :value="pollQuestion">
                            <input type="hidden" name="poll_type" :value="pollType">
                            <input type="hidden" name="poll_correct_index" :value="pollCorrectIndex">
                            <template x-for="(opt, oIdx) in pollOptions" :key="oIdx">
                                <input type="hidden" name="poll_options[]" :value="opt">
                            </template>
                        </div>
                    </template>

                    <div x-show="pollEnabled" x-transition class="space-y-4 pt-2 border-t border-slate-100 dark:border-white/5">
                        <!-- Mode Selector (Poll vs Quiz) -->
                        <div class="flex items-center gap-2">
                            <button type="button" @click="pollType = 'poll'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                                    :class="pollType === 'poll' ? 'bg-purple-500 text-white shadow-xs' : 'bg-slate-100 dark:bg-surface-card text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                                <span class="material-symbols-outlined text-base">bar_chart</span>
                                <span>Opinion Poll</span>
                            </button>
                            <button type="button" @click="pollType = 'quiz'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                                    :class="pollType === 'quiz' ? 'bg-emerald-500 text-white shadow-xs' : 'bg-slate-100 dark:bg-surface-card text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                                <span class="material-symbols-outlined text-base">quiz</span>
                                <span>Trivia Quiz (With Answer Key)</span>
                            </button>
                        </div>

                        <!-- Question Input -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Question / Prompt</label>
                            <input type="text" x-model="pollQuestion" placeholder="e.g. Which study technique actually gets you through exam weeks?"
                                   class="w-full px-4 py-2.5 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-purple-500/40 font-medium">
                        </div>

                        <!-- Options List -->
                        <div class="space-y-2.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span x-text="pollType === 'quiz' ? 'Options (Select the correct answer)' : 'Poll Options (2 to 4)'"></span>
                            </label>

                            <template x-for="(opt, idx) in pollOptions" :key="idx">
                                <div class="flex items-center gap-2">
                                    <!-- Quiz Correct Radio -->
                                    <template x-if="pollType === 'quiz'">
                                        <label class="cursor-pointer flex items-center shrink-0" title="Mark as correct answer">
                                            <input type="radio" name="_quiz_correct_radio" :value="idx" :checked="pollCorrectIndex === idx" @change="pollCorrectIndex = idx" class="sr-only peer">
                                            <span class="w-6 h-6 rounded-full border-2 border-slate-300 dark:border-white/20 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 text-white flex items-center justify-center transition-all text-xs font-bold">✓</span>
                                        </label>
                                    </template>

                                    <!-- Option Text Input -->
                                    <div class="relative flex-1">
                                        <input type="text" x-model="pollOptions[idx]" :placeholder="'Option ' + (idx + 1) + '...'"
                                               class="w-full px-4 py-2 rounded-xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                                    </div>

                                    <!-- Delete Button (minimum 2 options) -->
                                    <template x-if="pollOptions.length > 2">
                                        <button type="button" @click="removePollOption(idx)" class="p-1.5 text-slate-400 hover:text-rose-500 transition-colors">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <!-- Add Option Button (up to 4 options) -->
                            <template x-if="pollOptions.length < 4">
                                <button type="button" @click="addPollOption()" class="mt-2 text-xs text-purple-500 dark:text-purple-400 hover:underline font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">add_circle</span>
                                    <span>Add another option</span>
                                </button>
                            </template>
                        </div>

                        <!-- Live Feed Sticker Preview -->
                        <div class="mt-4 p-4 rounded-2xl bg-gradient-to-br from-purple-500/10 via-indigo-500/5 to-slate-100 dark:to-surface-card border border-purple-500/20">
                            <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-purple-500 mb-2">
                                <span class="material-symbols-outlined text-sm" x-text="pollType === 'quiz' ? 'quiz' : 'bar_chart'"></span>
                                <span x-text="pollType === 'quiz' ? 'Feed Quiz Sticker Preview' : 'Feed Poll Sticker Preview'"></span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white mb-3" x-text="pollQuestion || 'Your question appears here...'"></h4>
                            <div class="space-y-2">
                                <template x-for="(opt, idx) in pollOptions" :key="idx">
                                    <div class="relative w-full p-2.5 rounded-xl border border-slate-200 dark:border-white/10 text-xs font-bold flex items-center justify-between overflow-hidden"
                                         :class="pollType === 'quiz' && pollCorrectIndex === idx ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-white dark:bg-surface-card text-slate-700 dark:text-slate-300'">
                                        <span x-text="opt || ('Option ' + (idx + 1))"></span>
                                        <span class="text-[10px] font-mono text-slate-400" x-text="pollType === 'quiz' && pollCorrectIndex === idx ? '✓ Correct Answer' : '0%'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Metadata & Settings (1 Col) -->
            <div class="space-y-6">
                
                <!-- Publishing Meta -->
                <div class="p-6 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-4">
                    <h3 class="text-sm font-black font-display text-slate-900 dark:text-white uppercase tracking-wider">Publishing Meta</h3>
                    
                    <!-- For Assignment Select -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">For Assignment:</label>
                            <a href="{{ url('dashboard/assignments') }}" class="text-[11px] text-primary hover:underline font-semibold flex items-center gap-1">
                                <span>Assignment Center</span>
                                <span class="material-symbols-outlined text-xs">arrow_forward</span>
                            </a>
                        </div>
                        <select name="assignment_id" 
                                class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 font-medium">
                            <option value="0">-- None (Independent Piece) --</option>
                            @php
                                $hasSelectedInList = false;
                                if (!empty($userAssignments) && !empty($selectedAssignmentId)) {
                                    foreach($userAssignments as $uAsg) {
                                        if ((int)$uAsg['id'] === (int)$selectedAssignmentId) {
                                            $hasSelectedInList = true;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            @if(!empty($selectedAssignment) && !$hasSelectedInList)
                                <option value="{{ $selectedAssignment['id'] }}" selected>
                                    #{{ $selectedAssignment['id'] }} {{ $selectedAssignment['topic'] }} (Due: {{ date('M j', strtotime($selectedAssignment['submission_deadline'])) }})
                                </option>
                            @endif
                            @if(!empty($userAssignments))
                                @foreach($userAssignments as $uAsg)
                                    <option value="{{ $uAsg['id'] }}" {{ ((int)$selectedAssignmentId === (int)$uAsg['id']) ? 'selected' : '' }}>
                                        #{{ $uAsg['id'] }} {{ $uAsg['topic'] }} (Due: {{ date('M j', strtotime($uAsg['submission_deadline'])) }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Submitting this article will automatically mark the selected assignment as "Turned In" for editorial review.</p>
                    </div>

                    <!-- Category Beat -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Category Beat</label>
                        <select name="category_id" class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-surface-card border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            @foreach($categories as $cat)
                                <option value="{{ $cat['id'] }}" {{ ($article && $article['category_id'] == $cat['id']) ? 'selected' : '' }}>
                                    {{ $cat['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Excerpt Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-400">Excerpt / Subdeck</label>
                            <button type="button" @click="openAiModal('summarize')" class="text-[10px] text-purple-400 hover:underline">
                                Summarize with AI
                            </button>
                        </div>
                        <textarea id="articleExcerpt" name="excerpt" rows="3" 
                                  placeholder="Short 2-line teaser summary..." 
                                  class="w-full p-3 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none">{{ $article['excerpt'] ?? '' }}</textarea>
                    </div>

                    <!-- Dynamic Media Manager (Standard Cover / Instagram Carousel / Video) -->
                    <div>
                        <!-- Hidden cover image input synced with coverImageUrl -->
                        <input type="hidden" name="featured_image" :value="coverImageUrl">

                        <!-- A. CAROUSEL MULTI-IMAGE MANAGER -->
                        <div x-show="postFormat === 'carousel'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-indigo-500">view_carousel</span>
                                    <span>Carousel Slides</span>
                                </label>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-500" x-text="carouselImages.length + ' slides'"></span>
                            </div>

                            <!-- Upload Multiple Files Button / Dropzone -->
                            <div class="p-4 rounded-2xl border-2 border-dashed border-indigo-500/30 dark:border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 transition-colors text-center relative cursor-pointer">
                                <input type="file" multiple accept="image/*" @change="uploadCarouselImages($event)" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                <div class="flex flex-col items-center gap-1 text-slate-600 dark:text-slate-300">
                                    <span class="material-symbols-outlined text-2xl text-indigo-500 animate-bounce">add_photo_alternate</span>
                                    <span class="text-xs font-bold">Select Multiple Images</span>
                                    <span class="text-[10px] text-slate-400">Upload multiple JPG, PNG, WEBP slides sequentially</span>
                                </div>
                            </div>

                            <div x-show="isUploadingCarousel" class="text-center py-2 text-xs text-indigo-500 font-bold flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base animate-spin">sync</span>
                                <span>Uploading carousel slides...</span>
                            </div>

                            <!-- Add via Image URL -->
                            <div class="flex items-center gap-1.5">
                                <input type="url" x-model="carouselInputUrl" placeholder="Or paste image URL..."
                                       @keydown.enter.prevent="addCarouselUrl()"
                                       class="flex-1 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="addCarouselUrl()" class="px-3 py-1.5 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-xs shadow-xs transition-all">
                                    Add
                                </button>
                            </div>

                            <!-- Carousel Slides Thumbnail List -->
                            <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                <template x-for="(img, idx) in carouselImages" :key="idx">
                                    <div class="p-2 rounded-xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 flex items-center justify-between gap-2 group">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="w-5 h-5 rounded-md bg-indigo-500/15 text-indigo-500 text-[10px] font-black flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                                            <img :src="img" class="w-10 h-10 rounded-lg object-cover bg-black shrink-0">
                                            <span class="text-[11px] text-slate-600 dark:text-slate-300 truncate" x-text="img"></span>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button type="button" @click="moveCarouselImage(idx, -1)" :disabled="idx === 0" class="p-1 rounded text-slate-400 hover:text-slate-900 dark:hover:text-white disabled:opacity-30">
                                                <span class="material-symbols-outlined text-base">arrow_upward</span>
                                            </button>
                                            <button type="button" @click="moveCarouselImage(idx, 1)" :disabled="idx === carouselImages.length - 1" class="p-1 rounded text-slate-400 hover:text-slate-900 dark:hover:text-white disabled:opacity-30">
                                                <span class="material-symbols-outlined text-base">arrow_downward</span>
                                            </button>
                                            <button type="button" @click="removeCarouselImage(idx)" class="p-1 rounded text-slate-400 hover:text-rose-500">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- B. VIDEO MEDIA MANAGER -->
                        <div x-show="postFormat === 'video'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-rose-500">play_circle</span>
                                    <span>Featured Video Media</span>
                                </label>
                                <button type="button" @click="openVideoModal()" class="text-[10px] text-rose-500 hover:underline font-bold">
                                    Embedder Tool
                                </button>
                            </div>

                            <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10">
                                <button type="button" @click="videoType = 'youtube'" 
                                        class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all"
                                        :class="videoType === 'youtube' ? 'bg-rose-500 text-white shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                                    YouTube Link
                                </button>
                                <button type="button" @click="videoType = 'upload'" 
                                        class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all"
                                        :class="videoType === 'upload' ? 'bg-rose-500 text-white shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                                    Upload MP4
                                </button>
                            </div>

                            <!-- YouTube Link Input -->
                            <div x-show="videoType === 'youtube'" class="space-y-2">
                                <input type="url" x-model="videoUrl" placeholder="https://www.youtube.com/watch?v=..."
                                       @input="if(!coverImageUrl && getYoutubeId(videoUrl)) coverImageUrl = 'https://img.youtube.com/vi/' + getYoutubeId(videoUrl) + '/hqdefault.jpg'"
                                       class="w-full px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/40">
                                <template x-if="getYoutubeEmbedUrl(videoUrl)">
                                    <div class="aspect-video w-full rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-white/10">
                                        <iframe :src="getYoutubeEmbedUrl(videoUrl)" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                </template>
                            </div>

                            <!-- Video Upload Input -->
                            <div x-show="videoType === 'upload'" class="space-y-2">
                                <div class="p-4 rounded-2xl border-2 border-dashed border-rose-500/30 dark:border-rose-500/20 bg-rose-500/5 hover:bg-rose-500/10 transition-colors text-center relative cursor-pointer">
                                    <input type="file" accept="video/mp4,video/webm,video/ogg" @change="uploadVideoFile($event, true)" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                    <div class="flex flex-col items-center gap-1 text-slate-600 dark:text-slate-300">
                                        <span class="material-symbols-outlined text-2xl text-rose-500 animate-pulse">cloud_upload</span>
                                        <span class="text-xs font-bold">Select Video File</span>
                                        <span class="text-[10px] text-slate-400">MP4, WebM or Ogg format</span>
                                    </div>
                                </div>
                                <div x-show="isUploadingVideo" class="text-center py-2 text-xs text-rose-500 font-bold flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-base animate-spin">sync</span>
                                    <span>Uploading video...</span>
                                </div>
                                <template x-if="videoUrl && videoType === 'upload'">
                                    <div class="aspect-video w-full rounded-2xl overflow-hidden shadow-lg bg-black border border-slate-200 dark:border-white/10">
                                        <video controls class="w-full h-full" :src="videoUrl"></video>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- C. STANDARD COVER IMAGE MANAGER -->
                        <div x-show="postFormat === 'article' || postFormat === 'poll'" class="space-y-2.5">
                            <label class="block text-xs font-bold text-slate-400">Cover Image URL</label>
                            <input type="url" x-model="coverImageUrl"
                                   placeholder="https://images.unsplash.com/..." 
                                   class="w-full px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                            
                            <div class="aspect-video w-full rounded-2xl overflow-hidden bg-slate-200 dark:bg-surface-card border border-white/5 relative">
                                <template x-if="coverImageUrl">
                                    <img :src="coverImageUrl" alt="Cover preview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!coverImageUrl">
                                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 text-xs">
                                        <span class="material-symbols-outlined text-2xl mb-1">image</span>
                                        <span>Preview appears here</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Creator Workflow Notes -->
                    <div class="pt-3 border-t border-white/5 text-[11px] text-slate-400 space-y-1.5">
                        <div class="flex items-center gap-1.5 font-bold text-slate-300">
                            <span class="material-symbols-outlined text-sm text-primary">policy</span>
                            <span>Editorial Pipeline:</span>
                        </div>
                        <p>Contributors & Journalists submit drafts to the Editorial review queue. Managing Editors verify fact checks and approve for live feed.</p>
                    </div>
                </div>

                <!-- AI Quick Ideas Generator Card -->
                <div class="p-6 rounded-3xl bg-gradient-to-br from-purple-900/20 via-surface-card to-surface-card border border-purple-500/30">
                    <div class="flex items-center gap-2 text-purple-400 font-display font-bold text-sm mb-2">
                        <span class="material-symbols-outlined text-lg">lightbulb</span>
                        <span>Need Fresh Angles?</span>
                    </div>
                    <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                        Brainstorm trending internet lore, Y2K aesthetic hooks, and teen creator stories with Gemini AI.
                    </p>
                    <button type="button" @click="openAiModal('ideas'); runAiAction('ideas')" 
                            class="w-full py-2.5 px-4 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/30 font-bold text-xs transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">explore</span>
                        <span>Brainstorm Viral Hooks</span>
                    </button>
                </div>

            </div>

        </div>

    </form>

    <!-- GEMINI AI ASSISTANT MODAL (Vertical Collapsible Accordion & Dark/Light Mode Responsive) -->
    <div x-cloak x-show="aiModalOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/70 dark:bg-black/80 backdrop-blur-sm transition-opacity" @click="aiModalOpen = false"></div>

        <!-- Modal Window / Responsive Bottom Sheet on Mobile -->
        <div class="relative w-full sm:max-w-2xl max-h-[92vh] sm:max-h-[88vh] rounded-t-3xl sm:rounded-3xl bg-white dark:bg-[#151923] text-slate-900 dark:text-white shadow-2xl border border-slate-200 dark:border-white/10 z-50 overflow-hidden flex flex-col transition-all">
            
            <!-- Mobile Pull Handle Indicator -->
            <div class="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-white/20 mx-auto mt-2.5 mb-1 sm:hidden shrink-0"></div>

            <!-- Modal Header -->
            <div class="p-4 sm:p-5 bg-gradient-to-r from-purple-50 via-indigo-50/60 to-white dark:from-purple-950/40 dark:via-indigo-950/40 dark:to-[#151923] border-b border-slate-200 dark:border-white/10 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-purple-500/25 shrink-0">
                        <span class="material-symbols-outlined text-xl sm:text-2xl">auto_awesome</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-display font-black text-base sm:text-lg text-slate-900 dark:text-white truncate">
                                Gemini AI Assistant
                            </h3>
                            <!-- Engine Status Pill & Settings Toggle -->
                            <button type="button" 
                                    @click="aiSettingsOpen = !aiSettingsOpen" 
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold flex items-center gap-1 transition-all border shadow-xs"
                                    :class="aiHasKey 
                                        ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-300 dark:border-emerald-500/30' 
                                        : 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-500/30'"
                                    title="Configure Google Gemini credentials">
                                <span class="w-1.5 h-1.5 rounded-full" :class="aiHasKey ? 'bg-emerald-500 animate-pulse' : 'bg-purple-500'"></span>
                                <span x-text="aiHasKey ? 'Google Gemini Live' : 'Contextual AI Engine'"></span>
                                <span class="material-symbols-outlined text-[13px] text-slate-400">tune</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                            Smart AI co-pilot for junior high & middle school student journalists
                        </p>
                    </div>
                </div>

                <button type="button" @click="aiModalOpen = false" 
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 transition-colors shrink-0"
                        title="Close Assistant">
                    <span class="material-symbols-outlined text-lg sm:text-xl">close</span>
                </button>
            </div>

            <!-- Collapsible AI Engine & Credentials Setting Panel -->
            <div x-show="aiSettingsOpen" x-transition class="p-4 sm:p-5 bg-slate-50 dark:bg-surface-card border-b border-slate-200 dark:border-white/10 space-y-3 shrink-0">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-primary">key</span>
                        <span>Google Gemini Cloud API Configuration</span>
                    </span>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-[11px] font-bold text-primary hover:underline flex items-center gap-0.5">
                        <span>Get Free API Key</span>
                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div class="sm:col-span-2">
                        <input type="password" 
                               x-model="aiApiKeyInput" 
                               placeholder="Paste API Key (AIzaSy...)" 
                               class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <div>
                        <select x-model="aiModelInput" 
                                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="gemini-3.5-flash">gemini-3.5-flash (Fast & Stable - Recommended)</option>
                            <option value="gemini-3.5-flash-lite">gemini-3.5-flash-lite (Rapid & Lightweight)</option>
                            <option value="gemini-3.8-flash">gemini-3.8-flash (Latest Model)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 flex-wrap pt-1">
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                        *Without an API key, the system automatically uses Brighteen's built-in Contextual Generator tailored for student journalism.
                    </p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="saveAiKey()" :disabled="aiKeySaving"
                                class="px-3.5 py-1.5 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-xs transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs" :class="aiKeySaving ? 'animate-spin' : ''">save</span>
                            <span x-text="aiKeySaving ? 'Saving...' : 'Save Key'"></span>
                        </button>
                    </div>
                </div>

                <template x-if="aiKeyMessage">
                    <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 pt-1" x-text="aiKeyMessage"></div>
                </template>
            </div>

            <!-- Modal Body Content: Stacked Vertical Collapsible Sections (Accordion Pattern) -->
            <div class="p-4 sm:p-6 overflow-y-auto space-y-3.5 flex-1">

                <div class="flex items-center justify-between text-xs pb-1 flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px]">
                            Editorial AI Tools
                        </span>
                        <span class="text-[11px] text-slate-400 font-semibold hidden sm:inline">• Middle School Magazine</span>
                    </div>

                    <!-- Output Language Selector (Restricted to English unless Indonesian requested) -->
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-white/5 p-1 rounded-xl border border-slate-200 dark:border-white/10 text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400 px-1 font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">translate</span>
                            <span>Output:</span>
                        </span>
                        <button type="button" @click="aiLanguage = 'en'" 
                                class="px-2 py-0.5 rounded-lg font-bold transition-all text-[11px]"
                                :class="aiLanguage === 'en' ? 'bg-white dark:bg-surface-card text-primary shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                            English (Default)
                        </button>
                        <button type="button" @click="aiLanguage = 'id'" 
                                class="px-2 py-0.5 rounded-lg font-bold transition-all text-[11px]"
                                :class="aiLanguage === 'id' ? 'bg-white dark:bg-surface-card text-primary shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                            Bahasa Indonesia
                        </button>
                    </div>
                </div>

                <!-- 1. SECTION: Story Ideas (First Tab / Top Accordion) -->
                <div class="rounded-2xl border border-slate-200 dark:border-white/10 overflow-hidden bg-slate-50/70 dark:bg-surface-card transition-all">
                    <button type="button" @click="toggleAiSection('ideas')" 
                            class="w-full p-4 flex items-center justify-between gap-3 text-left transition-colors"
                            :class="aiActiveSection === 'ideas' ? 'bg-amber-500/10 dark:bg-amber-500/15 border-b border-slate-200 dark:border-white/10' : 'hover:bg-slate-100 dark:hover:bg-white/5'">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                 :class="aiActiveSection === 'ideas' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                <span class="material-symbols-outlined text-lg">lightbulb</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Story Ideas</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Discover 6 fresh, engaging story angles for middle school students</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-200 shrink-0"
                              :class="aiActiveSection === 'ideas' ? 'rotate-180 text-amber-600 dark:text-amber-400' : ''">
                            keyboard_arrow_down
                        </span>
                    </button>

                    <div x-show="aiActiveSection === 'ideas'" x-transition class="p-4 sm:p-5 space-y-4 bg-white dark:bg-[#151923]/60">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Beat, Club, or Topic Category:
                            </label>
                            <input type="text" x-model="aiPrompt" 
                                   placeholder="e.g., School Science Fair, Robotics Club, Eco Project, Esports, Study Tips" 
                                   class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500/50 transition-all">
                        </div>
                        <button type="button" @click="runAiAction('ideas')" :disabled="aiLoading" 
                                class="w-full py-3 px-6 rounded-2xl bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-amber-600/25 transition-all flex items-center justify-center gap-2 transform active:scale-98">
                            <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">radar</span>
                            <span x-text="aiLoading ? 'Researching Student Trends...' : 'Brainstorm 6 Engaging Story Ideas'"></span>
                        </button>
                    </div>
                </div>

                <!-- 2. SECTION: Auto-Draft Generator with 5W+1H & Interview Quotes -->
                <div class="rounded-2xl border border-slate-200 dark:border-white/10 overflow-hidden bg-slate-50/70 dark:bg-surface-card transition-all">
                    <button type="button" @click="toggleAiSection('draft')" 
                            class="w-full p-4 flex items-center justify-between gap-3 text-left transition-colors"
                            :class="aiActiveSection === 'draft' ? 'bg-primary/10 dark:bg-primary/15 border-b border-slate-200 dark:border-white/10' : 'hover:bg-slate-100 dark:hover:bg-white/5'">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                 :class="aiActiveSection === 'draft' ? 'bg-primary text-white shadow-sm' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                <span class="material-symbols-outlined text-lg">edit_document</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Auto-Draft</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Generate a structured, complete article with 5W+1H journalism</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-200 shrink-0"
                              :class="aiActiveSection === 'draft' ? 'rotate-180 text-primary' : ''">
                            keyboard_arrow_down
                        </span>
                    </button>

                    <div x-show="aiActiveSection === 'draft'" x-transition class="p-4 sm:p-5 space-y-4 bg-white dark:bg-[#151923]/60">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Story Topic or Working Headline:
                            </label>
                            <input type="text" x-model="aiPrompt" 
                                   placeholder="e.g., SMP Science Club creates solar-powered phone charger from recycled parts" 
                                   class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Story Angle & Key Notes (Optional):
                            </label>
                            <input type="text" x-model="aiNotes" 
                                   placeholder="e.g., Highlight hands-on teamwork, budget-friendly materials, and teacher encouragement" 
                                   class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all">
                        </div>

                        <!-- 5W + 1H Structured Journalism Fields -->
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-surface-card/80 border border-slate-200 dark:border-white/10 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-primary">feed</span>
                                    <span>5W + 1H Journalism Foundation (Optional)</span>
                                </span>
                                <span class="text-[10px] text-slate-400 uppercase font-mono tracking-wider">Fact Checker</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                                Provide real details to help Gemini ground the story in accurate, factual reporting.
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <!-- What -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                                        What (Core Event / Subject):
                                    </label>
                                    <input type="text" x-model="aiWhat" 
                                           placeholder="e.g., Annual student green-tech expo and robot showcase" 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                </div>

                                <!-- Who -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                                        Who (Students, Teachers, Clubs Involved):
                                    </label>
                                    <input type="text" x-model="aiWho" 
                                           placeholder="e.g., 8th-grade robotics team & science teacher Mr. Aris" 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                </div>

                                <!-- Where -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                                        Where (Location / Venue):
                                    </label>
                                    <input type="text" x-model="aiWhere" 
                                           placeholder="e.g., School Auditorium and central courtyard" 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                </div>

                                <!-- When -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                                        When (Date / Time / Timeline):
                                    </label>
                                    <input type="text" x-model="aiWhen" 
                                           placeholder="e.g., Friday morning, October 24, during homeroom" 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                </div>

                                <!-- Why -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                                        Why (Significance / Motivation):
                                    </label>
                                    <input type="text" x-model="aiWhy" 
                                           placeholder="e.g., To reduce single-use plastic and promote green habits" 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                </div>

                                <!-- How -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                                        How (Process / How It Unfolded):
                                    </label>
                                    <input type="text" x-model="aiHow" 
                                           placeholder="e.g., Students built working prototypes and demonstrated them live" 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                                </div>
                            </div>
                        </div>

                        <!-- Interview Quotes & Attribution Field -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-primary">format_quote</span>
                                    <span>Interview Quotes & Speaker Attribution (Optional):</span>
                                </span>
                                <span class="text-[10px] text-slate-400">Quotes</span>
                            </label>
                            <textarea x-model="aiInterviewQuotes" rows="2" 
                                      placeholder="e.g., &quot;We wanted to prove that science isn't just theory in textbooks, but something fun we can build together,&quot; shared Maya, 8th grade science club leader." 
                                      class="w-full p-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 resize-none transition-all"></textarea>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                                *Gemini will format quotes into stylized blockquote elements with proper speaker credits.
                            </p>
                        </div>

                        <button type="button" @click="runAiAction('draft')" :disabled="aiLoading" 
                                class="w-full py-3 px-6 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-primary/25 transition-all flex items-center justify-center gap-2 transform active:scale-98">
                            <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">auto_awesome</span>
                            <span x-text="aiLoading ? 'Writing Middle School Feature Story...' : 'Generate Complete Article Draft'"></span>
                        </button>
                    </div>
                </div>

                <!-- 3. SECTION: Titles & SEO -->
                <div class="rounded-2xl border border-slate-200 dark:border-white/10 overflow-hidden bg-slate-50/70 dark:bg-surface-card transition-all">
                    <button type="button" @click="toggleAiSection('title')" 
                            class="w-full p-4 flex items-center justify-between gap-3 text-left transition-colors"
                            :class="aiActiveSection === 'title' ? 'bg-indigo-500/10 dark:bg-indigo-500/15 border-b border-slate-200 dark:border-white/10' : 'hover:bg-slate-100 dark:hover:bg-white/5'">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                 :class="aiActiveSection === 'title' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                <span class="material-symbols-outlined text-lg">format_h1</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Titles & SEO</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Generate 5 catchy, student-friendly headlines and an SEO excerpt</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-200 shrink-0"
                              :class="aiActiveSection === 'title' ? 'rotate-180 text-indigo-600 dark:text-indigo-400' : ''">
                            keyboard_arrow_down
                        </span>
                    </button>

                    <div x-show="aiActiveSection === 'title'" x-transition class="p-4 sm:p-5 space-y-4 bg-white dark:bg-[#151923]/60">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Topic or Working Draft Snippet:
                            </label>
                            <input type="text" x-model="aiPrompt" 
                                   placeholder="Enter temporary title or key takeaway..." 
                                   class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50 transition-all">
                        </div>
                        <button type="button" @click="runAiAction('title')" :disabled="aiLoading" 
                                class="w-full py-3 px-6 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-purple-600/25 transition-all flex items-center justify-center gap-2 transform active:scale-98">
                            <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">format_h1</span>
                            <span x-text="aiLoading ? 'Crafting Headlines...' : 'Generate 5 Catchy Headlines & SEO Excerpt'"></span>
                        </button>
                    </div>
                </div>

                <!-- 4. SECTION: Rephrase & Summarize -->
                <div class="rounded-2xl border border-slate-200 dark:border-white/10 overflow-hidden bg-slate-50/70 dark:bg-surface-card transition-all">
                    <button type="button" @click="toggleAiSection('rephrase')" 
                            class="w-full p-4 flex items-center justify-between gap-3 text-left transition-colors"
                            :class="aiActiveSection === 'rephrase' ? 'bg-cyan-500/10 dark:bg-cyan-500/15 border-b border-slate-200 dark:border-white/10' : 'hover:bg-slate-100 dark:hover:bg-white/5'">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                 :class="aiActiveSection === 'rephrase' ? 'bg-cyan-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                <span class="material-symbols-outlined text-lg">autorenew</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Rephrase & Summarize</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Polish sentence flow or condense paragraphs into punchy excerpts</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-200 shrink-0"
                              :class="aiActiveSection === 'rephrase' ? 'rotate-180 text-cyan-600 dark:text-cyan-400' : ''">
                            keyboard_arrow_down
                        </span>
                    </button>

                    <div x-show="aiActiveSection === 'rephrase'" x-transition class="p-4 sm:p-5 space-y-4 bg-white dark:bg-[#151923]/60">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Text to Polish or Summarize:
                            </label>
                            <textarea x-model="aiSelectedText" rows="4" 
                                      placeholder="Highlight text in the editor or paste paragraphs here..." 
                                      class="w-full p-4 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-cyan-500/40 focus:border-cyan-500/50 resize-none transition-all"></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button type="button" @click="runAiAction('rephrase')" :disabled="aiLoading" 
                                    class="py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs transition-all flex items-center justify-center gap-1.5 shadow-sm">
                                <span class="material-symbols-outlined text-base">autorenew</span>
                                <span>Polish & Rephrase</span>
                            </button>
                            <button type="button" @click="runAiAction('summarize')" :disabled="aiLoading" 
                                    class="py-3 px-4 rounded-2xl bg-slate-100 dark:bg-surface-high hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-50 text-slate-800 dark:text-white font-bold text-xs border border-slate-200 dark:border-white/10 transition-all flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-base">short_text</span>
                                <span>Summarize into Excerpt</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 5. SECTION: Tone & Grammar Checker -->
                <div class="rounded-2xl border border-slate-200 dark:border-white/10 overflow-hidden bg-slate-50/70 dark:bg-surface-card transition-all">
                    <button type="button" @click="toggleAiSection('grammar')" 
                            class="w-full p-4 flex items-center justify-between gap-3 text-left transition-colors"
                            :class="aiActiveSection === 'grammar' ? 'bg-emerald-500/10 dark:bg-emerald-500/15 border-b border-slate-200 dark:border-white/10' : 'hover:bg-slate-100 dark:hover:bg-white/5'">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                 :class="aiActiveSection === 'grammar' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'">
                                <span class="material-symbols-outlined text-lg">spellcheck</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Tone & Grammar Checker</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Audit clarity, rhythm, and age-appropriate middle school voice</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-200 shrink-0"
                              :class="aiActiveSection === 'grammar' ? 'rotate-180 text-emerald-600 dark:text-emerald-400' : ''">
                            keyboard_arrow_down
                        </span>
                    </button>

                    <div x-show="aiActiveSection === 'grammar'" x-transition class="p-4 sm:p-5 space-y-4 bg-white dark:bg-[#151923]/60">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Draft Text to Audit:
                            </label>
                            <textarea x-model="aiSelectedText" rows="4" 
                                      placeholder="Paste your draft to check spelling, sentence flow, and friendly tone..." 
                                      class="w-full p-4 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500/50 resize-none transition-all"></textarea>
                        </div>
                        <button type="button" @click="runAiAction('grammar')" :disabled="aiLoading" 
                                class="w-full py-3 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">fact_check</span>
                            <span x-text="aiLoading ? 'Auditing Rhythm & Grammar...' : 'Run Tone & Grammar Audit'"></span>
                        </button>
                    </div>
                </div>

                <!-- Error Display -->
                <div x-show="aiError" class="p-3.5 sm:p-4 rounded-2xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 text-rose-700 dark:text-rose-400 text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-base shrink-0">error</span>
                    <span x-text="aiError"></span>
                </div>

                <!-- AI Output Display Box -->
                <div x-show="aiResult" class="space-y-3 pt-3 border-t border-slate-200 dark:border-white/10">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-wider text-purple-600 dark:text-purple-400">
                                AI Generated Output
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/10" 
                                  x-text="'Engine: ' + aiSource"></span>
                        </div>

                        <!-- View Mode Switch: Formatted Preview vs Raw HTML Code -->
                        <div class="flex items-center p-0.5 rounded-xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-[11px] font-bold">
                            <button type="button" @click="aiViewMode = 'preview'" 
                                    class="px-2.5 py-1 rounded-lg transition-all"
                                    :class="aiViewMode === 'preview' ? 'bg-white dark:bg-surface-card text-primary shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                                Preview
                            </button>
                            <button type="button" @click="aiViewMode = 'code'" 
                                    class="px-2.5 py-1 rounded-lg transition-all"
                                    :class="aiViewMode === 'code' ? 'bg-white dark:bg-surface-card text-primary shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                                HTML Code
                            </button>
                        </div>
                    </div>

                    <!-- Output Content Card -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 shadow-xs">
                        <!-- Formatted Preview -->
                        <div x-show="aiViewMode === 'preview'" 
                             class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed font-body max-h-72 sm:max-h-80 overflow-y-auto space-y-3 prose dark:prose-invert max-w-none"
                             x-html="aiResult"></div>

                        <!-- Raw HTML / Text View -->
                        <pre x-show="aiViewMode === 'code'" 
                             class="text-xs font-mono text-slate-700 dark:text-slate-300 max-h-72 sm:max-h-80 overflow-y-auto whitespace-pre-wrap leading-relaxed select-all"
                             x-text="aiResult"></pre>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center justify-between gap-2.5 pt-2">
                        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
                            <button type="button" @click="insertIntoEditor()" 
                                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white font-bold text-xs shadow-md shadow-primary/25 transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-base">input</span>
                                <span>Insert into Editor</span>
                            </button>
                            <button type="button" @click="copyResult()" 
                                    class="w-full sm:w-auto px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-white/10 hover:bg-slate-200 dark:hover:bg-white/15 text-slate-700 dark:text-white font-bold text-xs border border-slate-200 dark:border-white/10 transition-all flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-base">content_copy</span>
                                <span>Copy Text</span>
                            </button>
                        </div>

                        <button type="button" @click="aiResult = ''; aiError = ''" 
                                class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 px-2 py-1">
                            Clear
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- VIDEO EMBED MODAL (YouTube Link & Direct MP4 Upload) -->
    <div x-cloak x-show="videoModalOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/75 dark:bg-black/80 backdrop-blur-sm transition-opacity" @click="videoModalOpen = false"></div>

        <!-- Modal Content -->
        <div class="relative w-full sm:max-w-xl rounded-t-3xl sm:rounded-3xl bg-white dark:bg-[#151923] text-slate-900 dark:text-white shadow-2xl border border-slate-200 dark:border-white/10 z-50 overflow-hidden flex flex-col transition-all">
            <!-- Modal Header -->
            <div class="p-5 sm:p-6 pb-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">smart_display</span>
                    </span>
                    <div>
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Video Embed & Upload</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Embed YouTube streams or upload MP4 video directly</p>
                    </div>
                </div>
                <button type="button" @click="videoModalOpen = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="px-6 pt-4 flex items-center gap-2 border-b border-slate-100 dark:border-white/5">
                <button type="button" @click="videoTab = 'youtube'"
                        class="pb-3 px-3 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5"
                        :class="videoTab === 'youtube' ? 'border-rose-500 text-rose-500' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'">
                    <span class="material-symbols-outlined text-base">link</span>
                    <span>YouTube Embed</span>
                </button>
                <button type="button" @click="videoTab = 'upload'"
                        class="pb-3 px-3 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5"
                        :class="videoTab === 'upload' ? 'border-rose-500 text-rose-500' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'">
                    <span class="material-symbols-outlined text-base">upload_file</span>
                    <span>Upload Video (MP4)</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="p-6 space-y-4">
                <div x-show="videoUploadError" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">error</span>
                    <span x-text="videoUploadError"></span>
                </div>

                <!-- 1. YouTube Link Tab -->
                <div x-show="videoTab === 'youtube'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">YouTube Video URL</label>
                        <input type="url" x-model="youtubeInputUrl" placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..."
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/40">
                        <p class="text-[10px] text-slate-400 mt-1">Supports standard YouTube videos, YouTube Shorts, and youtu.be links.</p>
                    </div>

                    <!-- Live YouTube Preview -->
                    <template x-if="getYoutubeEmbedUrl(youtubeInputUrl)">
                        <div class="aspect-video w-full rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-white/10">
                            <iframe :src="getYoutubeEmbedUrl(youtubeInputUrl)" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </template>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" @click="embedYoutubeVideo(false)"
                                :disabled="!getYoutubeId(youtubeInputUrl)"
                                class="flex-1 py-3 px-4 rounded-xl bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-rose-500/25 transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-base">input</span>
                            <span>Embed into Story</span>
                        </button>
                        <button type="button" @click="embedYoutubeVideo(true)"
                                :disabled="!getYoutubeId(youtubeInputUrl)"
                                class="py-3 px-4 rounded-xl bg-slate-100 dark:bg-white/10 hover:bg-slate-200 dark:hover:bg-white/15 disabled:opacity-50 text-slate-700 dark:text-white font-bold text-xs border border-slate-200 dark:border-white/10 transition-all flex items-center justify-center gap-1.5">
                            <span>Set as Featured</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Direct Video Upload Tab -->
                <div x-show="videoTab === 'upload'" class="space-y-4">
                    <div class="p-6 rounded-2xl border-2 border-dashed border-rose-500/30 bg-rose-500/5 hover:bg-rose-500/10 transition-colors text-center relative cursor-pointer">
                        <input type="file" accept="video/mp4,video/webm,video/ogg" @change="uploadVideoFile($event, false)" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <div class="flex flex-col items-center gap-1.5 text-slate-600 dark:text-slate-300">
                            <span class="material-symbols-outlined text-3xl text-rose-500 animate-pulse">cloud_upload</span>
                            <span class="text-xs font-bold">Choose a Video File</span>
                            <span class="text-[10px] text-slate-400">MP4, WebM, Ogg (up to 100MB)</span>
                        </div>
                    </div>

                    <div x-show="isUploadingVideo" class="text-center py-3 text-xs text-rose-500 font-bold flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg animate-spin">sync</span>
                        <span>Uploading video to server... please wait</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Initialize CKEditor 5 with Custom Image Upload Adapter -->
<script>
    class BrighteenUploadAdapter {
        constructor(loader) {
            this.loader = loader;
        }

        upload() {
            return this.loader.file
                .then(file => new Promise((resolve, reject) => {
                    const data = new FormData();
                    data.append('upload', file);

                    fetch('{{ url('api/upload-image') }}', {
                        method: 'POST',
                        body: data
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.error) {
                            reject(result.error.message || 'Image upload failed');
                        } else {
                            resolve({ default: result.url });
                        }
                    })
                    .catch(err => reject('Network upload error'));
                }));
        }

        abort() {}
    }

    function BrighteenCustomUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
            return new BrighteenUploadAdapter(loader);
        };
    }

    ClassicEditor
        .create(document.querySelector('#editor'), {
            extraPlugins: [BrighteenCustomUploadAdapterPlugin],
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                    'blockQuote', 'insertTable', 'uploadImage', 'mediaEmbed', 'code', '|',
                    'undo', 'redo'
                ]
            },
            placeholder: 'Craft your story here with rich formatting, quotes, and visuals...'
        })
        .then(editor => {
            window.brighteenEditor = editor;
        })
        .catch(error => {
            console.error('CKEditor initialization error:', error);
        });
</script>
@endsection
