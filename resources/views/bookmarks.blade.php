@extends('layouts.app')

@section('content')
<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    <div class="flex items-center justify-between pb-6 border-b border-slate-200 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-base">bookmarks</span>
                <span>Your Reading Queue</span>
            </div>
            <h1 class="text-3xl font-black font-display text-slate-900 dark:text-white">Saved Stories</h1>
        </div>

        <div class="text-xs font-bold text-slate-400">
            {{ count($articles) }} saved items
        </div>
    </div>

    @if(empty($articles))
        <div class="text-center py-16 px-4 rounded-3xl glass-panel">
            <span class="material-symbols-outlined text-5xl text-slate-400 mb-3">bookmark_border</span>
            <h3 class="text-xl font-bold font-display text-white mb-2">No Bookmarked Stories Yet</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mb-6">Whenever you see a story that resonates, tap the bookmark icon on any card or article page to save it here.</p>
            <a href="{{ url('') }}" class="px-5 py-2.5 rounded-2xl bg-primary text-white text-xs font-bold font-display shadow-lg shadow-primary/25">
                Explore Feed
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($articles as $art)
                <article class="flex flex-col rounded-3xl overflow-hidden glass-panel border border-slate-200/80 dark:border-white/5 hover:border-primary/40 transition-all duration-300 group hover:-translate-y-1.5 shadow-xl shadow-black/5 dark:shadow-black/20"
                         x-data="{
                             saved: true,
                             toggleSave() {
                                 fetch('{{ url('api/articles/' . $art['id'] . '/save') }}', { method: 'POST' })
                                     .then(res => res.json())
                                     .then(data => {
                                         this.saved = data.saved;
                                         if (!this.saved) {
                                             $root.closest('body').__x.$data.showToast('Removed from saved reading list.');
                                         }
                                     });
                             }
                         }"
                         x-show="saved"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-90">
                    
                    <a href="{{ url('articles/' . $art['slug']) }}" class="relative aspect-video w-full overflow-hidden block">
                        <img src="{{ $art['featured_image'] }}" alt="{{ $art['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <span class="absolute top-4 left-4 px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider text-white shadow-md backdrop-blur-md"
                              style="background-color: {{ $art['category_color'] }};">
                            {{ $art['category_name'] }}
                        </span>
                    </a>

                    <div class="flex-1 p-6 flex flex-col justify-between">
                        <div>
                            <div class="text-[11px] font-semibold text-slate-400 mb-2">
                                Saved {{ date('M d, Y', strtotime($art['saved_at'])) }}
                            </div>
                            <a href="{{ url('articles/' . $art['slug']) }}">
                                <h4 class="text-xl font-black font-display text-slate-900 dark:text-white leading-snug group-hover:text-primary transition-colors mb-3">
                                    {{ $art['title'] }}
                                </h4>
                            </a>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-3 leading-relaxed mb-6 font-body">
                                {{ $art['excerpt'] }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-white/5">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $art['author_name'] }}</span>
                            <button type="button" @click="toggleSave()" class="text-xs text-rose-400 hover:text-rose-300 font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">bookmark_remove</span>
                                <span>Remove</span>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

</div>
@endsection
