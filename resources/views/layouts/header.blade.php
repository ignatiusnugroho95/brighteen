<header class="sticky top-0 z-40 w-full border-b border-slate-200/80 dark:border-surface-border/80 bg-white/80 dark:bg-surface/80 backdrop-blur-xl transition-colors duration-300"
        x-data="{ 
            searchOpen: false,
            roleSwitcherOpen: false,
            isDark: document.documentElement.classList.contains('dark'),
            toggleTheme() {
                this.isDark = !this.isDark;
                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.classList.add('light');
                    localStorage.setItem('theme', 'light');
                }
            }
        }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20 gap-4">
            
            <!-- Left: Brand Logo & Tagline -->
            <div class="flex items-center gap-6">
                <a href="{{ url('') }}" class="flex items-center gap-2.5 group">
                    <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary via-indigo-500 to-tertiary text-white font-display font-black text-2xl shadow-lg shadow-primary/30 group-hover:scale-105 transition-transform duration-300">
                        b
                    </span>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black font-display tracking-tight leading-none group-hover:opacity-90 transition-opacity">
                            <span class="text-black dark:text-white">brigh</span><span class="text-[#1e3a8a] dark:text-blue-400">teen</span>
                        </span>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-0.5 hidden sm:block tracking-tight">bright student, active teen</span>
                    </div>
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    <a href="{{ url('') }}" class="px-3.5 py-2 rounded-xl hover:text-primary hover:bg-slate-100 dark:hover:bg-surface-card transition-all">Feed</a>
                    <a href="{{ url('about') }}" class="px-3.5 py-2 rounded-xl hover:text-primary hover:bg-slate-100 dark:hover:bg-surface-card transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">info</span>
                        <span>About Us</span>
                    </a>
                </nav>
            </div>

            <!-- Center: Search Bar -->
            <div class="hidden md:flex flex-1 max-w-md mx-4">
                <form action="{{ url('') }}" method="GET" class="w-full relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
                    <input type="text" name="q" placeholder="Search stories, lore, trends..." 
                           class="w-full pl-11 pr-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card border border-transparent focus:border-primary/50 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 text-slate-900 dark:text-white placeholder-slate-400 transition-all">
                </form>
            </div>

            <!-- Right Actions: Notifications, Theme Toggle & Sidebar/Profile Trigger -->
            <div class="flex items-center gap-1.5 sm:gap-3">

                <!-- Mobile Search Toggle Button -->
                <button type="button" @click="searchOpen = !searchOpen" 
                        class="md:hidden p-2 rounded-2xl bg-slate-100 dark:bg-surface-card hover:bg-slate-200 dark:hover:bg-surface-high text-slate-600 dark:text-slate-300 transition-all"
                        title="Search">
                    <span class="material-symbols-outlined text-xl" x-text="searchOpen ? 'close' : 'search'"></span>
                </button>

                <!-- Notification Bell Component (Auth Only) -->
                @auth
                    <div class="relative" x-data="{
                        notifOpen: false,
                        unreadCount: 0,
                        notifications: [],
                        loading: false,
                        fetchNotifications() {
                            this.loading = true;
                            fetch('{{ url('api/notifications') }}')
                                .then(res => res.json())
                                .then(data => {
                                    this.unreadCount = data.unread_count || 0;
                                    this.notifications = data.notifications || [];
                                    this.loading = false;
                                })
                                .catch(() => { this.loading = false; });
                        },
                        markRead(notif) {
                            fetch('{{ url('api/notifications/') }}/' + notif.id + '/read', { method: 'POST' });
                            notif.is_read = 1;
                            if (this.unreadCount > 0) this.unreadCount--;
                            if (notif.link) window.location.href = '{{ url('') }}/' + notif.link;
                        },
                        markAllRead() {
                            fetch('{{ url('api/notifications/mark-all-read') }}', { method: 'POST' })
                                .then(() => {
                                    this.unreadCount = 0;
                                    this.notifications.forEach(n => n.is_read = 1);
                                });
                        }
                    }" x-init="fetchNotifications()" @click.outside="notifOpen = false">
                        
                        <button type="button" @click="notifOpen = !notifOpen; if(notifOpen) fetchNotifications();"
                                class="relative p-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card hover:bg-slate-200 dark:hover:bg-surface-high text-slate-600 dark:text-slate-300 transition-all focus:outline-none focus:ring-2 focus:ring-primary/40"
                                title="Notifications & Reminders">
                            <span class="material-symbols-outlined text-xl">notifications</span>
                            <!-- Unread Counter Badge -->
                            <span x-show="unreadCount > 0" x-cloak
                                  class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center shadow-lg shadow-rose-500/50 animate-pulse">
                                <span x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                            </span>
                        </button>

                        <!-- Notifications Dropdown Box (Mobile Responsive Width) -->
                        <div x-cloak x-show="notifOpen"
                             x-transition:enter="transition ease-out duration-150 transform"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-[calc(100vw-2rem)] sm:w-96 max-w-sm p-3.5 rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-slate-200 dark:border-white/10 z-50 text-left space-y-3">
                            
                            <div class="flex items-center justify-between pb-2.5 border-b border-slate-200 dark:border-white/10 px-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-primary text-base">notifications_active</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Notifications & Reminders</span>
                                </div>
                                <button type="button" @click="markAllRead()" class="text-[11px] text-primary hover:underline font-semibold">
                                    Mark all read
                                </button>
                            </div>

                            <div class="max-h-80 overflow-y-auto space-y-2 pr-1">
                                <template x-if="notifications.length === 0">
                                    <div class="py-8 text-center text-slate-400">
                                        <span class="material-symbols-outlined text-3xl mb-1 text-slate-500">notifications_paused</span>
                                        <p class="text-xs font-medium">All caught up! No notifications right now.</p>
                                    </div>
                                </template>

                                <template x-for="notif in notifications" :key="notif.id">
                                    <div @click="markRead(notif)"
                                         class="p-3 rounded-2xl cursor-pointer transition-all border flex items-start gap-3"
                                         :class="notif.is_read == 0 ? 'bg-primary/5 dark:bg-primary/10 border-primary/30 text-slate-900 dark:text-white' : 'bg-slate-50 dark:bg-white/[0.02] border-slate-200/60 dark:border-white/5 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5'">
                                        <span class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center text-sm shadow-sm"
                                              :class="notif.type.includes('deadline') ? 'bg-rose-500/10 text-rose-500' : (notif.type.includes('publishing') ? 'bg-amber-500/10 text-amber-500' : (notif.type.includes('application') ? 'bg-purple-500/10 text-purple-500' : 'bg-primary/10 text-primary'))">
                                            <span class="material-symbols-outlined text-base" 
                                                  x-text="notif.type.includes('deadline') ? 'schedule' : (notif.type.includes('publishing') ? 'event_upcoming' : (notif.type.includes('application') ? 'badge' : 'assignment'))"></span>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-1">
                                                <h4 class="text-xs font-bold truncate" :class="notif.is_read == 0 ? 'text-slate-900 dark:text-white font-black' : 'text-slate-700 dark:text-slate-300'" x-text="notif.title"></h4>
                                                <span x-show="notif.is_read == 0" class="w-2 h-2 rounded-full bg-primary shrink-0"></span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5 line-clamp-2" x-text="notif.message"></p>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 block" x-text="notif.created_at"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            @if(auth()->canCreateArticle())
                                <div class="pt-2 border-t border-slate-200 dark:border-white/10 text-center">
                                    <a href="{{ url('dashboard/assignments') }}" class="text-xs text-primary hover:underline font-bold inline-flex items-center gap-1">
                                        <span>Open Assignments & Drafts</span>
                                        <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endauth

                <!-- Universal Light / Dark Mode Toggle Switch -->
                <button type="button" @click="toggleTheme()" 
                        class="p-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card hover:bg-slate-200 dark:hover:bg-surface-high text-slate-600 dark:text-slate-300 transition-all focus:outline-none focus:ring-2 focus:ring-primary/40"
                        title="Toggle Light/Dark Theme">
                    <span x-show="isDark" class="material-symbols-outlined text-xl text-amber-400 animate-spin-slow">light_mode</span>
                    <span x-show="!isDark" class="material-symbols-outlined text-xl text-indigo-500">dark_mode</span>
                </button>

                <!-- Sidebar & Profile Drawer Trigger Button -->
                @auth
                    <button type="button" @click="$dispatch('open-sidebar')" 
                            class="flex items-center gap-2 p-1.5 sm:px-3 sm:py-2 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-surface-card dark:hover:bg-surface-high border border-slate-200/80 dark:border-white/10 text-slate-700 dark:text-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-primary/40 group shadow-sm"
                            title="Open Sidebar Menu & Profile">
                        <img src="{{ auth()->user()['avatar'] ?: url('uploads/avatars/default-avatar.svg') }}" 
                             alt="{{ auth()->user()['name'] }}" 
                             onerror="this.onerror=null; this.src='{{ url('uploads/avatars/default-avatar.svg') }}';"
                             class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl object-cover ring-2 ring-primary/40 group-hover:scale-105 transition-transform bg-slate-800">
                        <div class="hidden sm:flex flex-col text-left leading-tight">
                            <span class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[90px]">{{ auth()->user()['name'] }}</span>
                            <span class="text-[10px] text-primary font-bold capitalize">{{ auth()->user()['role_name'] }}</span>
                        </div>
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors text-lg sm:ml-1">menu</span>
                    </button>
                @endauth

                @guest
                    <div class="flex items-center gap-2">
                        <a href="{{ url('login') }}" class="px-4 py-2 rounded-2xl bg-primary hover:bg-primary-hover text-white font-semibold text-xs sm:text-sm shadow-md shadow-primary/20 transition-all">
                            Sign In
                        </a>
                        <button type="button" @click="$dispatch('open-sidebar')" 
                                class="p-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-surface-card dark:hover:bg-surface-high text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-white/10 transition-all"
                                title="Open Sidebar Menu">
                            <span class="material-symbols-outlined text-xl">menu</span>
                        </button>
                    </div>
                @endguest

            </div>
        </div>

        <!-- Mobile Search Input Drawer -->
        <div x-show="searchOpen" x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden pb-3 pt-1 border-t border-slate-100 dark:border-white/5">
            <form action="{{ url('') }}" method="GET" class="w-full relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">search</span>
                <input type="text" name="q" placeholder="Search stories, lore, topics..." 
                       class="w-full pl-10 pr-10 py-2.5 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
                <button type="button" @click="searchOpen = false" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </form>
        </div>
    </div>
</header>

