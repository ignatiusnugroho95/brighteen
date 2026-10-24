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
         aiTab: 'draft',
         aiLoading: false,
         aiPrompt: '',
         aiNotes: '',
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

         openAiModal(tab = 'draft') {
             this.aiTab = tab;
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
                 text: this.aiSelectedText || plainContent || excerptVal || titleVal,
                 niche: 'Gen-Z Tech, Pop Culture & Creativity'
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
                <button type="button" @click="openAiModal('draft')" 
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
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-black uppercase tracking-wider text-slate-400">Article Content</label>
                        <!-- Quick AI Actions toolbar above editor -->
                        <div class="flex items-center gap-2 text-xs">
                            <button type="button" @click="openAiModal('rephrase')" class="text-purple-400 hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">magic_button</span> Rephrase
                            </button>
                            <span class="text-slate-500">&bull;</span>
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

                    <!-- Featured Image URL -->
                    <div x-data="{ imgUrl: '{{ $article['featured_image'] ?? '' }}' }">
                        <label class="block text-xs font-bold text-slate-400 mb-2">Cover Image URL</label>
                        <input type="url" name="featured_image" x-model="imgUrl"
                               placeholder="https://images.unsplash.com/..." 
                               class="w-full px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 mb-3">
                        
                        <div class="aspect-video w-full rounded-2xl overflow-hidden bg-slate-200 dark:bg-surface-card border border-white/5 relative">
                            <template x-if="imgUrl">
                                <img :src="imgUrl" alt="Cover preview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!imgUrl">
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 text-xs">
                                    <span class="material-symbols-outlined text-2xl mb-1">image</span>
                                    <span>Preview appears here</span>
                                </div>
                            </template>
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

    <!-- GEMINI AI ASSISTANT MODAL (Mobile-Friendly & Dark/Light Mode Responsive) -->
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
                                        : 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-500/30'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="aiHasKey ? 'bg-emerald-500 animate-pulse' : 'bg-purple-500'"></span>
                                <span x-text="aiHasKey ? 'Google Gemini Live' : 'Contextual AI Engine'"></span>
                                <span class="material-symbols-outlined text-[13px] text-slate-400">tune</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                            Asisten penulisan cerdas, akurat dengan prompt, gaya bahasa muda & bebas klise
                        </p>
                    </div>
                </div>

                <button type="button" @click="aiModalOpen = false" 
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 transition-colors shrink-0"
                        title="Tutup Modal">
                    <span class="material-symbols-outlined text-lg sm:text-xl">close</span>
                </button>
            </div>

            <!-- Collapsible AI Engine & Credentials Setting Panel -->
            <div x-show="aiSettingsOpen" x-transition class="p-4 sm:p-5 bg-slate-50 dark:bg-surface-card border-b border-slate-200 dark:border-white/10 space-y-3 shrink-0">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-primary">key</span>
                        <span>Konfigurasi Google Gemini Cloud API</span>
                    </span>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-[11px] font-bold text-primary hover:underline flex items-center gap-0.5">
                        <span>Dapatkan Key Gratis</span>
                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div class="sm:col-span-2">
                        <input type="password" 
                               x-model="aiApiKeyInput" 
                               placeholder="Tempel API Key (AIzaSy...)" 
                               class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <div>
                        <select x-model="aiModelInput" 
                                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#151923] border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="gemini-3.5-flash">gemini-3.5-flash (Rekomendasi - Cepat & Stabil)</option>
                            <option value="gemini-3.5-flash-lite">gemini-3.5-flash-lite (Ringan & Cepat)</option>
                            <option value="gemini-3.8-flash">gemini-3.8-flash (Versi 3.8 Terbaru)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 flex-wrap pt-1">
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                        *Tanpa API key, sistem otomatis memakai <strong>Intelligent Contextual Generator Brighteen</strong> yang 100% selaras dengan topik Anda.
                    </p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="saveAiKey()" :disabled="aiKeySaving"
                                class="px-3.5 py-1.5 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-xs transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs" :class="aiKeySaving ? 'animate-spin' : ''">save</span>
                            <span>Simpan Key</span>
                        </button>
                    </div>
                </div>

                <template x-if="aiKeyMessage">
                    <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 pt-1" x-text="aiKeyMessage"></div>
                </template>
            </div>

            <!-- AI Tool Tabs Toolbar (Touch Scrollable on Mobile) -->
            <div class="flex items-center gap-1.5 px-3 sm:px-6 pt-3 pb-2 border-b border-slate-200 dark:border-white/10 overflow-x-auto scrollbar-none text-xs font-bold shrink-0">
                <button type="button" @click="aiTab = 'draft'" 
                        class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap shadow-xs"
                        :class="aiTab === 'draft' 
                            ? 'bg-primary text-white shadow-primary/25' 
                            : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-base">edit_document</span>
                    <span>1. Auto-Draft</span>
                </button>

                <button type="button" @click="aiTab = 'title'" 
                        class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap shadow-xs"
                        :class="aiTab === 'title' 
                            ? 'bg-primary text-white shadow-primary/25' 
                            : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-base">format_h1</span>
                    <span>2. Titles & SEO</span>
                </button>

                <button type="button" @click="aiTab = 'rephrase'" 
                        class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap shadow-xs"
                        :class="aiTab === 'rephrase' 
                            ? 'bg-primary text-white shadow-primary/25' 
                            : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-base">autorenew</span>
                    <span>3. Rephrase/Ringkas</span>
                </button>

                <button type="button" @click="aiTab = 'grammar'" 
                        class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap shadow-xs"
                        :class="aiTab === 'grammar' 
                            ? 'bg-primary text-white shadow-primary/25' 
                            : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-base">spellcheck</span>
                    <span>4. Tone Checker</span>
                </button>

                <button type="button" @click="aiTab = 'ideas'" 
                        class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap shadow-xs"
                        :class="aiTab === 'ideas' 
                            ? 'bg-primary text-white shadow-primary/25' 
                            : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-base">lightbulb</span>
                    <span>5. Ide Cerita</span>
                </button>
            </div>

            <!-- Modal Body Content (Scrollable) -->
            <div class="p-4 sm:p-6 overflow-y-auto space-y-5 flex-1">
                
                <!-- Tab 1: Auto-Draft Generator -->
                <div x-show="aiTab === 'draft'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Topik atau Tema Cerita yang Ingin Dibuat:
                        </label>
                        <input type="text" x-model="aiPrompt" 
                               placeholder="Contoh: Mengapa pelajar SMA banyak beralih ke kamera digital jadul Y2K" 
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Sudut Pandang / Catatan Khusus (Opsional):
                        </label>
                        <input type="text" x-model="aiNotes" 
                               placeholder="Contoh: Sebutkan kejenuhan feed Instagram, estetika sensor CMOS jadul, dan nostalgia taktil" 
                               class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all">
                    </div>
                    <button type="button" @click="runAiAction('draft')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-primary/25 transition-all flex items-center justify-center gap-2 transform active:scale-98">
                        <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">auto_awesome</span>
                        <span x-text="aiLoading ? 'Menyusun Draf Artikel Lengkap...' : 'Generate Draf Artikel Sesuai Topik'"></span>
                    </button>
                </div>

                <!-- Tab 2: Title & SEO Optimizer -->
                <div x-show="aiTab === 'title'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Topik atau Penggalan Draf Naskah:
                        </label>
                        <input type="text" x-model="aiPrompt" 
                               placeholder="Masukkan judul sementara atau pokok bahasan..." 
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all">
                    </div>
                    <button type="button" @click="runAiAction('title')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-purple-600/25 transition-all flex items-center justify-center gap-2 transform active:scale-98">
                        <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">format_h1</span>
                        <span x-text="aiLoading ? 'Meracik Rekomendasi Judul & SEO...' : 'Buat 5 Pilihan Judul Menarik & Ringkasan SEO'"></span>
                    </button>
                </div>

                <!-- Tab 3: Rephrase & Summarize -->
                <div x-show="aiTab === 'rephrase'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Teks yang Ingin Diolah / Dipoles:
                        </label>
                        <textarea x-model="aiSelectedText" rows="4" 
                                  placeholder="Sorot teks di editor atau ketik/tempel paragraf di sini..." 
                                  class="w-full p-4 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 resize-none transition-all"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" @click="runAiAction('rephrase')" :disabled="aiLoading" 
                                class="py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs transition-all flex items-center justify-center gap-1.5 shadow-sm">
                            <span class="material-symbols-outlined text-base">autorenew</span>
                            <span>Poles Gaya Bahasa (Rephrase)</span>
                        </button>
                        <button type="button" @click="runAiAction('summarize')" :disabled="aiLoading" 
                                class="py-3 px-4 rounded-2xl bg-slate-100 dark:bg-surface-high hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-50 text-slate-800 dark:text-white font-bold text-xs border border-slate-200 dark:border-white/10 transition-all flex items-center justify-center gap-1.5 shadow-xs">
                            <span class="material-symbols-outlined text-base">short_text</span>
                            <span>Ringkas Jadi Cuplikan (Excerpt)</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 4: Grammar & Tone Checker -->
                <div x-show="aiTab === 'grammar'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Teks yang Ingin Diaudit:
                        </label>
                        <textarea x-model="aiSelectedText" rows="4" 
                                  placeholder="Tempel draf tulisan untuk memeriksa ejaan, ritme kalimat, dan kesesuaian nada baca..." 
                                  class="w-full p-4 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 resize-none transition-all"></textarea>
                    </div>
                    <button type="button" @click="runAiAction('grammar')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">fact_check</span>
                        <span x-text="aiLoading ? 'Memeriksa Ritme Kalimat & Tata Bahasa...' : 'Jalankan Audit Nada & Tata Bahasa'"></span>
                    </button>
                </div>

                <!-- Tab 5: Content Idea Generator -->
                <div x-show="aiTab === 'ideas'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Tema / Kategori Pembahasan:
                        </label>
                        <input type="text" x-model="aiPrompt" 
                               placeholder="Contoh: Teknologi, Kreativitas, Lingkungan Sekolah, Esports" 
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all">
                    </div>
                    <button type="button" @click="runAiAction('ideas')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md shadow-amber-600/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">radar</span>
                        <span x-text="aiLoading ? 'Meriset Tren Terkini...' : 'Temukan 6 Ide Cerita Berpotensi Viral'"></span>
                    </button>
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
                            <span class="text-xs font-black uppercase tracking-wider text-purple-600 dark:text-purple-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">sparkles</span>
                                <span>Hasil Output AI</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/10" 
                                  x-text="'Engine: ' + aiSource"></span>
                        </div>

                        <!-- View Mode Switch: Formatted vs Raw Code -->
                        <div class="flex items-center p-0.5 rounded-xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-[11px] font-bold">
                            <button type="button" @click="aiViewMode = 'preview'" 
                                    class="px-2.5 py-1 rounded-lg transition-all"
                                    :class="aiViewMode === 'preview' ? 'bg-white dark:bg-surface-card text-primary shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                                Pratinjau
                            </button>
                            <button type="button" @click="aiViewMode = 'code'" 
                                    class="px-2.5 py-1 rounded-lg transition-all"
                                    :class="aiViewMode === 'code' ? 'bg-white dark:bg-surface-card text-primary shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                                Kode HTML
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

                    <!-- Sticky / Touch-Friendly Action Buttons -->
                    <div class="flex flex-wrap items-center justify-between gap-2.5 pt-2">
                        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
                            <button type="button" @click="insertIntoEditor()" 
                                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white font-bold text-xs shadow-md shadow-primary/25 transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-base">input</span>
                                <span>Masukkan ke Editor</span>
                            </button>
                            <button type="button" @click="copyResult()" 
                                    class="w-full sm:w-auto px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-white/10 hover:bg-slate-200 dark:hover:bg-white/15 text-slate-700 dark:text-white font-bold text-xs border border-slate-200 dark:border-white/10 transition-all flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-base">content_copy</span>
                                <span>Salin Teks</span>
                            </button>
                        </div>

                        <button type="button" @click="aiResult = ''; aiError = ''" 
                                class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 px-2 py-1">
                            Bersihkan
                        </button>
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
                    'blockQuote', 'insertTable', 'uploadImage', 'code', '|',
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
