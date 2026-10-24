<nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-surface/90 backdrop-blur-xl border-t border-slate-200/80 dark:border-surface-border/80 px-4 py-2 transition-colors duration-300">
    <div class="flex items-center justify-around">
        
        <!-- Home -->
        <a href="{{ url('') }}" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-2xl">home</span>
            <span class="text-[10px] font-semibold">Home</span>
        </a>

        <!-- Explore / Articles -->
        <a href="{{ url('articles') }}" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-2xl text-primary">explore</span>
            <span class="text-[10px] font-semibold">Explore</span>
        </a>

        <!-- Write / Workbench (If Creator) or Categories -->
        @auth
            @if(auth()->canCreateArticle())
                <a href="{{ url('articles/create') }}" class="flex flex-col items-center -mt-5">
                    <span class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-primary to-tertiary flex items-center justify-center text-white shadow-lg shadow-primary/30">
                        <span class="material-symbols-outlined text-2xl">add</span>
                    </span>
                    <span class="text-[10px] font-semibold text-primary mt-1">Create</span>
                </a>
            @else
                <a href="{{ url('/#categories') }}" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-2xl">grid_view</span>
                    <span class="text-[10px] font-semibold">Topics</span>
                </a>
            @endif
        @endauth
        @guest
            <a href="{{ url('/#categories') }}" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-2xl">grid_view</span>
                <span class="text-[10px] font-semibold">Topics</span>
            </a>
        @endguest

        <!-- Bookmarks -->
        @auth
            <a href="{{ url('bookmarks') }}" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-2xl">bookmark</span>
                <span class="text-[10px] font-semibold">Saved</span>
            </a>
        @endauth
        @guest
            <button type="button" @click="guestActionText = 'save stories to your reading list'; guestModalOpen = true" 
                    class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-2xl">bookmark</span>
                <span class="text-[10px] font-semibold">Saved</span>
            </button>
        @endguest

        <!-- Menu / Profile Drawer Toggle -->
        @auth
            <button type="button" @click="$dispatch('open-sidebar')" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                <img src="{{ auth()->user()['avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}" 
                     alt="{{ auth()->user()['name'] }}" 
                     class="w-6 h-6 rounded-lg object-cover ring-1 ring-primary/40">
                <span class="text-[10px] font-semibold">Menu</span>
            </button>
        @endauth
        @guest
            <button type="button" @click="$dispatch('open-sidebar')" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-2xl">menu</span>
                <span class="text-[10px] font-semibold">Menu</span>
            </button>
        @endguest

    </div>
</nav>
