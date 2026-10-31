@extends('layouts.app')

@section('content')
<script>
function announcementManager() {
    return {
        emojiOptions: ['📢', '🎉', '🔥', '⚡', '📌', '🏆', '✨', '🚀', '📅', '💡', '🎓', '📰'],
        
        // Create Form State
        createEmoji: '📢',
        createText: '',
        createHasLink: false,
        createLinkUrl: '',
        createLinkLabel: '',
        createIsActive: {{ $activeCount < $maxActive ? '1' : '0' }},

        // Edit Modal State
        editModalOpen: false,
        editId: null,
        editEmoji: '📢',
        editText: '',
        editHasLink: false,
        editLinkUrl: '',
        editLinkLabel: '',
        editIsActive: 1,

        // Delete Modal State
        deleteModalOpen: false,
        deleteId: null,
        deletePreviewText: '',

        insertEmojiToCreate(em) {
            if ((this.createText + em).length <= {{ $maxTextLength }}) {
                this.createText += em;
            }
        },

        insertEmojiToEdit(em) {
            if ((this.editText + em).length <= {{ $maxTextLength }}) {
                this.editText += em;
            }
        },

        openEdit(item) {
            this.editId = item.id;
            this.editEmoji = item.emoji || '📢';
            this.editText = item.text || '';
            this.editLinkUrl = item.link_url || '';
            this.editLinkLabel = item.link_label || '';
            this.editHasLink = !!(item.link_url && item.link_url.trim() !== '');
            this.editIsActive = Number(item.is_active) === 1 ? 1 : 0;
            this.editModalOpen = true;
        },

        openDelete(id, text) {
            this.deleteId = id;
            this.deletePreviewText = text;
            this.deleteModalOpen = true;
        }
    };
}
</script>

<div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="announcementManager()">

    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-base">campaign</span>
                <span>Single-Channel Announcement Broadcast</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">
                    Announcement Manager
                </h1>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black border shadow-xs {{ $activeCount >= $maxActive ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' }}">
                    <span class="w-2 h-2 rounded-full {{ $activeCount >= $maxActive ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse' }}"></span>
                    <span>{{ $activeCount }} / {{ $maxActive }} Active Announcements</span>
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Manage up to {{ $maxActive }} active announcements (concise text, emojis &amp; optional link) displayed on the homepage <strong>Announcement</strong> bar.
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ url('dashboard/editorial') }}" 
               class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-surface-card dark:hover:bg-white/10 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-white/10 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base text-purple-500">rule</span>
                <span>Editorial Desk</span>
            </a>
            @if(auth()->isManagement())
                <a href="{{ url('dashboard/management') }}" 
                   class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-surface-card dark:hover:bg-white/10 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-white/10 transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-emerald-500">monitoring</span>
                    <span>Management Panel</span>
                </a>
            @endif
            <a href="{{ url('') }}" 
               class="px-4 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">visibility</span>
                <span>View Homepage</span>
            </a>
        </div>
    </div>

    <!-- Notifications & Alerts -->
    @if(!empty($notice))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-semibold flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <span>
                    @if($notice === 'created') New announcement successfully created!
                    @elseif($notice === 'updated') Announcement changes saved successfully!
                    @elseif($notice === 'activated') Announcement activated on the homepage ticker!
                    @elseif($notice === 'deactivated') Announcement deactivated from the homepage ticker.
                    @elseif($notice === 'deleted') Announcement permanently deleted.
                    @else Action completed successfully.
                    @endif
                </span>
            </div>
            <a href="{{ url('dashboard/announcement') }}" class="text-xs font-bold underline shrink-0">Dismiss</a>
        </div>
    @endif

    @if(!empty($error))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs sm:text-sm font-semibold flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <span>
                    @if($error === 'max_active_reached') Maximum of {{ $maxActive }} active announcements reached. Please deactivate or delete an active announcement first.
                    @elseif($error === 'empty_text') Announcement text cannot be empty.
                    @elseif($error === 'not_found') The requested announcement could not be found.
                    @else An error occurred while processing your request.
                    @endif
                </span>
            </div>
            <a href="{{ url('dashboard/announcement') }}" class="text-xs font-bold underline shrink-0">Dismiss</a>
        </div>
    @endif

    <!-- Live Homepage Announcement Ticker Preview -->
    <div class="space-y-2">
        <div class="flex items-center justify-between px-1">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-primary">preview</span>
                <span>Live Preview on Homepage (/index)</span>
            </span>
            <span class="text-[11px] text-slate-400">{{ $activeCount }} active item(s)</span>
        </div>

        <div class="rounded-2xl bg-slate-100 dark:bg-white/[0.04] border border-slate-200 dark:border-white/10 p-2 sm:px-3 sm:py-2 text-xs shadow-sm overflow-hidden flex items-center gap-3">
            <!-- Ticker Static Label -->
            <div class="flex items-center gap-1.5 shrink-0 bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-light px-2.5 py-1 rounded-xl font-black text-[11px] uppercase tracking-wider z-10 shadow-xs">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="material-symbols-outlined text-[16px]">campaign</span>
                <span>Announcement</span>
            </div>

            <!-- Marquee Scrolling Track -->
            <div class="relative overflow-hidden flex-1 group">
                @if(empty($activeAnnouncements))
                    <div class="py-0.5 text-slate-400 italic text-xs">
                        No active announcements right now. Create or activate an announcement below.
                    </div>
                @else
                    <div class="animate-marquee-smooth items-center gap-8 py-0.5">
                        @for($repeat = 0; $repeat < 2; $repeat++)
                            <div class="inline-flex items-center gap-6 sm:gap-8">
                                @foreach($activeAnnouncements as $idx => $ann)
                                    <div class="inline-flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white font-black text-[10px] uppercase tracking-wider flex items-center gap-1 shadow-xs">
                                            <span>{{ $ann['emoji'] ?: '📢' }}</span>
                                            <span>#{{ $idx + 1 }}</span>
                                        </span>
                                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $ann['text'] }}</span>
                                        @if(!empty($ann['link_url']))
                                            <a href="{{ \App\Models\Announcement::formatHref($ann['link_url']) }}" 
                                               target="{{ preg_match('#^https?://#i', $ann['link_url']) ? '_blank' : '_self' }}"
                                               class="px-2 py-0.5 rounded-lg bg-primary/15 hover:bg-primary text-primary hover:text-white font-bold text-[10px] transition-colors inline-flex items-center gap-0.5">
                                                <span>{{ $ann['link_label'] ?: 'Open Link' }}</span>
                                                <span class="material-symbols-outlined text-[12px]">arrow_outward</span>
                                            </a>
                                        @endif
                                    </div>
                                    <span class="text-slate-300 dark:text-slate-700 select-none">•</span>
                                @endforeach
                            </div>
                        @endfor
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Grid: Left Composer (Create) + Right Active/Saved List (CRUD) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- LEFT COLUMN: CREATE NEW ANNOUNCEMENT (5 Cols) -->
        <div class="lg:col-span-5 p-5 sm:p-6 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 shadow-sm space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">add_comment</span>
                    </span>
                    <div>
                        <h2 class="text-base font-black font-display text-slate-900 dark:text-white">Create Announcement</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Concise text with character limit, emojis &amp; link</p>
                    </div>
                </div>
            </div>

            <form action="{{ url('dashboard/announcement') }}" method="POST" class="space-y-4">
                
                <!-- 1. Emoji Badge Selector -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Lead Emoji Badge
                    </label>
                    <input type="hidden" name="emoji" :value="createEmoji">
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="em in emojiOptions" :key="em">
                            <button type="button" 
                                    @click="createEmoji = em"
                                    class="w-9 h-9 rounded-xl text-base flex items-center justify-center border transition-all"
                                    :class="createEmoji === em 
                                        ? 'bg-primary/15 border-primary ring-2 ring-primary/25 scale-105' 
                                        : 'bg-slate-50 dark:bg-white/5 border-slate-200 dark:border-white/10 hover:bg-slate-100 dark:hover:bg-white/10'">
                                <span x-text="em"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 2. Limited Text Input + Quick Emoji Insert -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Announcement Text <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] font-mono font-bold"
                              :class="createText.length >= {{ $maxTextLength }} ? 'text-rose-500' : (createText.length > {{ $maxTextLength - 25 }} ? 'text-amber-500' : 'text-slate-400')">
                            <span x-text="createText.length"></span> / {{ $maxTextLength }}
                        </span>
                    </div>
                    <textarea name="text" rows="3" required maxlength="{{ $maxTextLength }}"
                              x-model="createText"
                              placeholder="Write your short announcement here (max {{ $maxTextLength }} characters)..."
                              class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40"></textarea>
                    
                    <!-- Quick Insert Emoji into Text -->
                    <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase mr-1">Insert Emoji:</span>
                        <template x-for="em in ['🎉', '🔥', '✨', '👏', '🚀', '📌', '💡', '❤️']" :key="'ins-' + em">
                            <button type="button" @click="insertEmojiToCreate(em)"
                                    class="px-1.5 py-0.5 rounded-lg bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-xs transition-colors"
                                    title="Insert emoji into text">
                                <span x-text="em"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 3. Optional Limited Link -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="createHasLink" class="rounded text-primary focus:ring-primary">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-primary">link</span>
                                <span>Include Action Link (Optional)</span>
                            </span>
                        </label>
                    </div>

                    <div x-show="createHasLink" x-cloak class="space-y-2.5 pt-1">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Link URL</label>
                                <span class="text-[10px] font-mono text-slate-400" x-text="createLinkUrl.length + ' / {{ $maxLinkUrlLength }}'"></span>
                            </div>
                            <input type="text" name="link_url" x-model="createLinkUrl" maxlength="{{ $maxLinkUrlLength }}"
                                   :disabled="!createHasLink"
                                   placeholder="e.g. https://... or articles/story-slug"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-background border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Link Button Label (Max {{ $maxLinkLabelLength }} chars)</label>
                                <span class="text-[10px] font-mono text-slate-400" x-text="createLinkLabel.length + ' / {{ $maxLinkLabelLength }}'"></span>
                            </div>
                            <input type="text" name="link_label" x-model="createLinkLabel" maxlength="{{ $maxLinkLabelLength }}"
                                   :disabled="!createHasLink"
                                   placeholder="e.g. Read More"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-background border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                        </div>
                    </div>
                </div>

                <!-- 4. Status Selection -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10">
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Display Status</div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">
                            @if($activeCount >= $maxActive)
                                <span class="text-amber-500 font-semibold">Active slots full ({{ $activeCount }}/{{ $maxActive }}). Save as Draft or deactivate another item.</span>
                            @else
                                {{ $maxActive - $activeCount }} active slot(s) remaining.
                            @endif
                        </div>
                    </div>
                    <select name="is_active" x-model="createIsActive"
                            class="px-3 py-1.5 rounded-xl bg-white dark:bg-surface-card border border-slate-200 dark:border-white/10 text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="1" {{ $activeCount >= $maxActive ? 'disabled' : '' }}>Active (Live)</option>
                        <option value="0" {{ $activeCount >= $maxActive ? 'selected' : '' }}>Draft / Inactive</option>
                    </select>
                </div>

                <button type="submit" 
                        class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    <span>Save Announcement</span>
                </button>
            </form>
        </div>

        <!-- RIGHT COLUMN: ANNOUNCEMENTS LIST & CRUD (7 Cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="flex items-center justify-between px-1">
                <div>
                    <h2 class="text-base font-black font-display text-slate-900 dark:text-white">All Announcements ({{ count($announcements) }})</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Up to {{ $maxActive }} <strong>Active</strong> announcements rotate automatically on the homepage ticker.</p>
                </div>
            </div>

            @if(empty($announcements))
                <div class="p-10 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 text-center space-y-3">
                    <span class="w-12 h-12 mx-auto rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">campaign</span>
                    </span>
                    <div class="text-sm font-bold text-slate-800 dark:text-white">No Announcements Yet</div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                        Use the composer form on the left to publish your first announcement.
                    </p>
                </div>
            @else
                <div class="space-y-3">
                    @php $activeSlotIndex = 0; @endphp
                    @foreach($announcements as $item)
                        @php
                            $isActive = (int)$item['is_active'] === 1;
                            if ($isActive) {
                                $activeSlotIndex++;
                            }
                            $itemJson = htmlspecialchars(json_encode([
                                'id' => (int)$item['id'],
                                'emoji' => $item['emoji'] ?: '📢',
                                'text' => $item['text'],
                                'link_url' => $item['link_url'] ?? '',
                                'link_label' => $item['link_label'] ?? '',
                                'is_active' => (int)$item['is_active'],
                            ]), ENT_QUOTES, 'UTF-8');
                        @endphp
                        <div class="p-4 sm:p-5 rounded-3xl glass-panel border transition-all {{ $isActive ? 'border-primary/40 bg-primary/[0.02] dark:bg-primary/[0.04] shadow-sm' : 'border-slate-200 dark:border-white/10 opacity-80 hover:opacity-100' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    <!-- Emoji Avatar -->
                                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl shrink-0 border {{ $isActive ? 'bg-amber-500/15 border-amber-500/30' : 'bg-slate-100 dark:bg-white/5 border-slate-200 dark:border-white/10' }}">
                                        {{ $item['emoji'] ?: '📢' }}
                                    </div>

                                    <div class="min-w-0 flex-1 space-y-1.5">
                                        <!-- Status Pill & Meta -->
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if($isActive)
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span>Active #{{ $activeSlotIndex }}</span>
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-200 dark:bg-white/10 text-slate-600 dark:text-slate-400">
                                                    Inactive / Draft
                                                </span>
                                            @endif

                                            <span class="text-[11px] text-slate-400">
                                                {{ mb_strlen($item['text']) }}/{{ $maxTextLength }} chars
                                            </span>

                                            @if(!empty($item['creator_name']))
                                                <span class="text-[11px] text-slate-400">&bull; by {{ $item['creator_name'] }}</span>
                                            @endif
                                        </div>

                                        <!-- Announcement Text -->
                                        <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white break-words leading-relaxed">
                                            {{ $item['text'] }}
                                        </p>

                                        <!-- Optional Link Preview -->
                                        @if(!empty($item['link_url']))
                                            <div class="pt-0.5 flex items-center gap-2 flex-wrap">
                                                <a href="{{ \App\Models\Announcement::formatHref($item['link_url']) }}" 
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary text-[11px] font-bold border border-primary/20 transition-colors">
                                                    <span class="material-symbols-outlined text-xs">link</span>
                                                    <span>{{ $item['link_label'] ?: 'Open Link' }}</span>
                                                    <span class="text-[10px] opacity-75 truncate max-w-[180px]">({{ $item['link_url'] }})</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- CRUD Action Buttons -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <!-- Toggle Active / Inactive -->
                                    <form action="{{ url('dashboard/announcement/' . $item['id'] . '/toggle') }}" method="POST">
                                        <button type="submit"
                                                class="p-2 rounded-xl border text-xs font-bold transition-all flex items-center justify-center {{ $isActive ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-500/25' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/25' }}"
                                                title="{{ $isActive ? 'Deactivate from Ticker' : 'Activate on Ticker' }}">
                                            <span class="material-symbols-outlined text-base">{{ $isActive ? 'visibility_off' : 'play_arrow' }}</span>
                                        </button>
                                    </form>

                                    <!-- Edit Button -->
                                    <button type="button"
                                            @click="openEdit({{ $itemJson }})"
                                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-white/10 transition-all flex items-center justify-center"
                                            title="Edit Announcement">
                                        <span class="material-symbols-outlined text-base">edit</span>
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button"
                                            @click="openDelete({{ (int)$item['id'] }}, '{{ addslashes($item['text']) }}')"
                                            class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/20 transition-all flex items-center justify-center"
                                            title="Delete Announcement">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <!-- EDIT ANNOUNCEMENT MODAL -->
    <div x-cloak x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-sm" @click="editModalOpen = false"></div>
        <div class="relative w-full max-w-lg p-6 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-slate-200 dark:border-white/10 z-10 space-y-4 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">edit_note</span>
                    </span>
                    <div>
                        <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Edit Announcement</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Update announcement text, emoji, link, or active status</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form :action="'{{ url('dashboard/announcement/') }}/' + editId + '/update'" method="POST" class="space-y-4">
                <!-- Emoji Selector -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Lead Emoji Badge</label>
                    <input type="hidden" name="emoji" :value="editEmoji">
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="em in emojiOptions" :key="'edit-' + em">
                            <button type="button" 
                                    @click="editEmoji = em"
                                    class="w-9 h-9 rounded-xl text-base flex items-center justify-center border transition-all"
                                    :class="editEmoji === em 
                                        ? 'bg-primary/15 border-primary ring-2 ring-primary/25 scale-105' 
                                        : 'bg-slate-50 dark:bg-white/5 border-slate-200 dark:border-white/10'">
                                <span x-text="em"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Text Input -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Announcement Text</label>
                        <span class="text-[11px] font-mono font-bold"
                              :class="editText.length >= {{ $maxTextLength }} ? 'text-rose-500' : 'text-slate-400'">
                            <span x-text="editText.length"></span> / {{ $maxTextLength }}
                        </span>
                    </div>
                    <textarea name="text" rows="3" required maxlength="{{ $maxTextLength }}"
                              x-model="editText"
                              class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40"></textarea>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[10px] font-bold text-slate-400 uppercase mr-1">Insert Emoji:</span>
                        <template x-for="em in ['🎉', '🔥', '✨', '👏', '🚀', '📌', '💡', '❤️']" :key="'edit-ins-' + em">
                            <button type="button" @click="insertEmojiToEdit(em)"
                                    class="px-1.5 py-0.5 rounded-lg bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-xs">
                                <span x-text="em"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Link Fields -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10 space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="editHasLink" @change="if(!editHasLink) { editLinkUrl = ''; editLinkLabel = ''; }" class="rounded text-primary focus:ring-primary">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Include Action Link</span>
                    </label>

                    <div x-show="editHasLink" x-cloak class="space-y-2.5 pt-1">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Link URL</label>
                            <input type="text" name="link_url" x-model="editLinkUrl" maxlength="{{ $maxLinkUrlLength }}"
                                   placeholder="https://... or articles/..."
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-background border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Link Button Label (Max {{ $maxLinkLabelLength }} chars)</label>
                            <input type="text" name="link_label" x-model="editLinkLabel" maxlength="{{ $maxLinkLabelLength }}"
                                   placeholder="Read More"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-background border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Active Status -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Display Status</span>
                    <select name="is_active" x-model="editIsActive"
                            class="px-3 py-1.5 rounded-xl bg-white dark:bg-surface-card border border-slate-200 dark:border-white/10 text-xs font-bold text-slate-900 dark:text-white">
                        <option value="1">Active (Live)</option>
                        <option value="0">Draft / Inactive</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 text-xs font-bold text-slate-700 dark:text-slate-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div x-cloak x-show="deleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-sm" @click="deleteModalOpen = false"></div>
        <div class="relative w-full max-w-md p-6 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-rose-500/30 z-10 space-y-4">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">delete_forever</span>
                </span>
                <div>
                    <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Delete Announcement?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">This action cannot be undone.</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-700 dark:text-slate-300 italic" x-text="deletePreviewText"></div>

            <form :action="'{{ url('dashboard/announcement/') }}/' + deleteId + '/delete'" method="POST" class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" @click="deleteModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 text-xs font-bold text-slate-700 dark:text-slate-300">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold shadow-md shadow-rose-500/20">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
