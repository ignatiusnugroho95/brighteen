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

         openAiModal(tab = 'draft') {
             this.aiTab = tab;
             this.aiModalOpen = true;
             this.aiError = '';
             
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
                 $root.closest('body').__x.$data.showToast('AI draft inserted into editor!');
                 this.aiModalOpen = false;
             }
         },

         applyTitle(suggestedTitle) {
             const titleInput = document.getElementById('articleTitle');
             if (titleInput) {
                 titleInput.value = suggestedTitle;
                 $root.closest('body').__x.$data.showToast('Title updated!');
             }
         },

         applyExcerpt(suggestedExcerpt) {
             const excerptInput = document.getElementById('articleExcerpt');
             if (excerptInput) {
                 excerptInput.value = suggestedExcerpt;
                 $root.closest('body').__x.$data.showToast('Excerpt updated!');
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

    <!-- GEMINI AI ASSISTANT MODAL (All 5 Tools) -->
    <div x-cloak x-show="aiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/75 backdrop-blur-md" @click="aiModalOpen = false"></div>

        <!-- Modal Window -->
        <div class="relative w-full max-w-2xl rounded-3xl glass-panel shadow-2xl border border-purple-500/30 z-10 overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="p-6 bg-gradient-to-r from-purple-900/40 via-indigo-900/40 to-surface-card border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-purple-500 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-purple-500/30">
                        <span class="material-symbols-outlined text-2xl">auto_awesome</span>
                    </div>
                    <div>
                        <h3 class="font-display font-black text-lg text-white flex items-center gap-2">
                            <span>Gemini AI Writer Assistant</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">Gen-Z Mode</span>
                        </h3>
                        <p class="text-xs text-slate-400">Trained for vibrant youth cadence, internet lore & zero corporate fluff</p>
                    </div>
                </div>
                <button type="button" @click="aiModalOpen = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- AI Tool Tabs -->
            <div class="flex items-center gap-1 px-6 pt-4 border-b border-white/10 overflow-x-auto scrollbar-none text-xs font-bold">
                <button type="button" @click="aiTab = 'draft'" 
                        class="px-3.5 py-2.5 rounded-t-xl transition-all border-b-2 flex items-center gap-1.5 whitespace-nowrap"
                        :class="aiTab === 'draft' ? 'text-primary border-primary bg-primary/10' : 'text-slate-400 border-transparent hover:text-white'">
                    <span class="material-symbols-outlined text-base">edit_document</span>
                    <span>1. Auto-Draft</span>
                </button>

                <button type="button" @click="aiTab = 'title'" 
                        class="px-3.5 py-2.5 rounded-t-xl transition-all border-b-2 flex items-center gap-1.5 whitespace-nowrap"
                        :class="aiTab === 'title' ? 'text-primary border-primary bg-primary/10' : 'text-slate-400 border-transparent hover:text-white'">
                    <span class="material-symbols-outlined text-base">format_h1</span>
                    <span>2. Titles & SEO</span>
                </button>

                <button type="button" @click="aiTab = 'rephrase'" 
                        class="px-3.5 py-2.5 rounded-t-xl transition-all border-b-2 flex items-center gap-1.5 whitespace-nowrap"
                        :class="aiTab === 'rephrase' ? 'text-primary border-primary bg-primary/10' : 'text-slate-400 border-transparent hover:text-white'">
                    <span class="material-symbols-outlined text-base">autorenew</span>
                    <span>3. Rephrase/Summary</span>
                </button>

                <button type="button" @click="aiTab = 'grammar'" 
                        class="px-3.5 py-2.5 rounded-t-xl transition-all border-b-2 flex items-center gap-1.5 whitespace-nowrap"
                        :class="aiTab === 'grammar' ? 'text-primary border-primary bg-primary/10' : 'text-slate-400 border-transparent hover:text-white'">
                    <span class="material-symbols-outlined text-base">spellcheck</span>
                    <span>4. Tone Checker</span>
                </button>

                <button type="button" @click="aiTab = 'ideas'" 
                        class="px-3.5 py-2.5 rounded-t-xl transition-all border-b-2 flex items-center gap-1.5 whitespace-nowrap"
                        :class="aiTab === 'ideas' ? 'text-primary border-primary bg-primary/10' : 'text-slate-400 border-transparent hover:text-white'">
                    <span class="material-symbols-outlined text-base">lightbulb</span>
                    <span>5. Content Ideas</span>
                </button>
            </div>

            <!-- Modal Body Content -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                
                <!-- Tab 1: Auto-Draft Generator -->
                <div x-show="aiTab === 'draft'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">What story do you want to create?</label>
                        <input type="text" x-model="aiPrompt" placeholder="e.g. Why young creators are switching to nostalgic Y2K digicams" 
                               class="w-full px-4 py-3 rounded-2xl bg-surface-card border border-white/10 text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Specific angles or notes (optional)</label>
                        <input type="text" x-model="aiNotes" placeholder="e.g. Mention camera models like Canon PowerShot, Instagram fatigue, tactile nostalgia" 
                               class="w-full px-4 py-2.5 rounded-2xl bg-surface-card border border-white/10 text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <button type="button" @click="runAiAction('draft')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-primary hover:bg-primary-hover disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">sync</span>
                        <span x-text="aiLoading ? 'Generating Full Draft with Gemini...' : 'Generate Complete Youth Draft'"></span>
                    </button>
                </div>

                <!-- Tab 2: Title & SEO Optimizer -->
                <div x-show="aiTab === 'title'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Working Topic or Draft snippet</label>
                        <input type="text" x-model="aiPrompt" placeholder="Enter rough title or topic..." 
                               class="w-full px-4 py-3 rounded-2xl bg-surface-card border border-white/10 text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <button type="button" @click="runAiAction('title')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-purple-600/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base" :class="aiLoading ? 'animate-spin' : ''">auto_awesome</span>
                        <span x-text="aiLoading ? 'Optimizing Viral Hooks...' : 'Generate 5 Catchy Gen-Z Titles & SEO'"></span>
                    </button>
                </div>

                <!-- Tab 3: Rephrase & Summarize -->
                <div x-show="aiTab === 'rephrase'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Selected Text to Polish</label>
                        <textarea x-model="aiSelectedText" rows="4" placeholder="Highlight text in editor or paste text here..." 
                                  class="w-full p-4 rounded-2xl bg-surface-card border border-white/10 text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="runAiAction('rephrase')" :disabled="aiLoading" 
                                class="py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-base">autorenew</span>
                            <span>Make Punchy</span>
                        </button>
                        <button type="button" @click="runAiAction('summarize')" :disabled="aiLoading" 
                                class="py-3 px-4 rounded-2xl bg-surface-high hover:bg-white/10 disabled:opacity-50 text-white font-bold text-xs border border-white/10 transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-base">short_text</span>
                            <span>Generate Excerpt</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 4: Grammar & Tone Checker -->
                <div x-show="aiTab === 'grammar'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Text to Audit</label>
                        <textarea x-model="aiSelectedText" rows="4" placeholder="Paste draft paragraphs to check tone and spelling..." 
                                  class="w-full p-4 rounded-2xl bg-surface-card border border-white/10 text-white text-xs focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"></textarea>
                    </div>
                    <button type="button" @click="runAiAction('grammar')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">fact_check</span>
                        <span x-text="aiLoading ? 'Analyzing Rhythm & Tone...' : 'Run Youth Tone & Grammar Audit'"></span>
                    </button>
                </div>

                <!-- Tab 5: Content Idea Generator -->
                <div x-show="aiTab === 'ideas'" class="space-y-4">
                    <p class="text-xs text-slate-400">Discover what youth communities are discussing across Reddit, Discord, and TikTok.</p>
                    <button type="button" @click="runAiAction('ideas')" :disabled="aiLoading" 
                            class="w-full py-3 px-6 rounded-2xl bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-amber-600/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">radar</span>
                        <span x-text="aiLoading ? 'Scouting Internet Lore...' : 'Scout 6 Trending Story Angles'"></span>
                    </button>
                </div>

                <!-- Error Display -->
                <div x-show="aiError" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold">
                    <span x-text="aiError"></span>
                </div>

                <!-- AI Output Display Box -->
                <div x-show="aiResult" class="space-y-4 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-purple-400 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">sparkles</span>
                            <span>Generated Output</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono" x-text="'Engine: ' + aiSource"></span>
                    </div>

                    <div class="p-5 rounded-2xl bg-surface-card border border-white/10 text-xs sm:text-sm text-slate-200 leading-relaxed font-body whitespace-pre-line max-h-72 overflow-y-auto"
                         x-text="aiResult"></div>

                    <!-- Direct Injection Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" @click="insertIntoEditor()" 
                                class="px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-md shadow-primary/25 transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">input</span>
                            <span>Insert into Editor</span>
                        </button>
                        <button type="button" @click="navigator.clipboard.writeText(aiResult); $root.closest('body').__x.$data.showToast('Copied to clipboard!')" 
                                class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">content_copy</span>
                            <span>Copy Text</span>
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
