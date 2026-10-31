<!-- Unified Off-Canvas Sidebar & Profile Drawer -->
<div x-data="{ open: false }" 
     @open-sidebar.window="open = true" 
     @close-sidebar.window="open = false" 
     @keydown.escape.window="open = false" 
     x-cloak>
    <!-- Dark Backdrop Overlay -->
    <div x-show="open"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[99]"></div>

    <!-- Slide-over Drawer Panel -->
    <aside x-show="open"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-[100] w-full max-w-[320px] sm:max-w-[360px] h-full bg-white dark:bg-[#121620] text-slate-800 dark:text-slate-100 shadow-2xl border-l border-slate-200/80 dark:border-white/10 flex flex-col justify-between overflow-x-hidden overflow-y-auto">
        
        <!-- Top Scrollable Content -->
        <div class="p-4 sm:p-5 space-y-5 min-w-0">
            
            <!-- Drawer Header -->
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-200/80 dark:border-white/10">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-primary/10 text-primary shrink-0">
                        <span class="material-symbols-outlined text-lg">dashboard_customize</span>
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-display font-black text-sm text-slate-900 dark:text-white uppercase tracking-wider truncate">
                            Control Center
                        </h2>
                    </div>
                </div>
                <button type="button" @click="open = false" 
                        class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/10 transition-all shrink-0"
                        title="Close Sidebar">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Profile Summary Card (Auth) / Guest Invitation (Guest) -->
            @auth
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10 shadow-sm min-w-0 space-y-2.5">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative shrink-0">
                            <img src="{{ auth()->user()['avatar'] ?: url('uploads/avatars/default-avatar.svg') }}" 
                                 alt="{{ auth()->user()['name'] }}" 
                                 onerror="this.onerror=null; this.src='{{ url('uploads/avatars/default-avatar.svg') }}';"
                                 class="w-11 h-11 rounded-xl object-cover ring-2 ring-primary/40 shadow-sm bg-slate-800">
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#121620]"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-display font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate">{{ auth()->user()['name'] }}</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ auth()->user()['email'] }}</p>
                            
                            <div class="mt-1 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider
                                @if(auth()->role() === 'management') bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25
                                @elseif(auth()->role() === 'editor') bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/25
                                @elseif(auth()->role() === 'journalist') bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/25
                                @elseif(auth()->role() === 'contributor') bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/25
                                @else bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/25
                                @endif">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                <span>{{ auth()->user()['role_name'] }}</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ url('profile') }}" 
                       class="w-full flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary text-xs font-bold transition-all border border-primary/20">
                        <span class="material-symbols-outlined text-sm">manage_accounts</span>
                        <span>Edit Profile & Account</span>
                    </a>
                </div>
            @endauth

            @guest
                <div class="p-4 rounded-2xl bg-gradient-to-br from-primary/10 via-indigo-500/5 to-transparent border border-primary/20 text-center space-y-2.5 min-w-0">
                    <div class="w-10 h-10 mx-auto rounded-xl bg-gradient-to-tr from-primary to-indigo-600 flex items-center justify-center text-white shadow-md shadow-primary/25">
                        <span class="material-symbols-outlined text-xl">account_circle</span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Welcome, Guest Explorer!</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">Sign in to write stories, bookmark lore, and join the conversation.</p>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <a href="{{ url('login') }}" class="flex-1 py-2 px-3 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-sm transition-all text-center">
                            Sign In
                        </a>
                        <a href="{{ url('register') }}" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-800 dark:text-white text-xs font-bold transition-all text-center">
                            Register
                        </a>
                    </div>
                </div>
            @endguest

            {{-- Primary Action: Write Story (Content Creators) --}}
            @auth
                @if(auth()->canCreateArticle())
                    <a href="{{ url('articles/create') }}" 
                       class="group flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white font-semibold shadow-lg shadow-primary/20 transition-all transform hover:-translate-y-0.5 min-w-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0">
                                <span class="material-symbols-outlined text-lg">edit_square</span>
                            </span>
                            <div class="text-left min-w-0">
                                <div class="text-xs font-black uppercase tracking-wider truncate">Write New Story</div>
                                <div class="text-[10px] text-white/80 font-normal truncate">Workbench & Publisher</div>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-white/80 group-hover:translate-x-1 transition-transform text-lg shrink-0">arrow_forward</span>
                    </a>
                @endif
            @endauth

            {{-- Role-Based Action List --}}
            @auth
                <div class="space-y-1.5 min-w-0">
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 px-1 pb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-xs">tune</span>
                        <span>Role Workspaces</span>
                    </div>

                    {{-- Central Dashboard Hub (All Creators) --}}
                    @if(auth()->canCreateArticle())
                        <a href="{{ url('dashboard') }}" 
                           class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] hover:bg-sky-500/10 border border-slate-200/80 dark:border-white/10 hover:border-sky-500/30 text-slate-800 dark:text-slate-200 transition-all group min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                                <span class="material-symbols-outlined text-lg">dashboard</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Central Dashboard</span>
                                    <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded-full bg-sky-500/15 text-sky-600 dark:text-sky-400 shrink-0">Hub</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Statistics, analytics & shortcuts</p>
                            </div>
                        </a>
                    @endif

                    {{-- Management Panel (Management Only) --}}
                    @if(auth()->isManagement())
                        <a href="{{ url('dashboard/management') }}" 
                           class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] hover:bg-emerald-500/10 border border-slate-200/80 dark:border-white/10 hover:border-emerald-500/30 text-slate-800 dark:text-slate-200 transition-all group min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                                <span class="material-symbols-outlined text-lg">monitoring</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Management Panel</span>
                                    <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 shrink-0">Admin</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Staff, readers, settings & metrics</p>
                            </div>
                        </a>
                    @endif

                    {{-- Editorial Desk (Editor & Management) --}}
                    @if(auth()->isEditor())
                        <a href="{{ url('dashboard/editorial') }}" 
                           class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] hover:bg-purple-500/10 border border-slate-200/80 dark:border-white/10 hover:border-purple-500/30 text-slate-800 dark:text-slate-200 transition-all group min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                                <span class="material-symbols-outlined text-lg">rule</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Editorial Desk</span>
                                    <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded-full bg-purple-500/15 text-purple-600 dark:text-purple-400 shrink-0">Review</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Review queue, beats & curation</p>
                            </div>
                        </a>

                        {{-- Announcement Manager (Editor & Management) --}}
                        <a href="{{ url('dashboard/announcement') }}" 
                           class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] hover:bg-amber-500/10 border border-slate-200/80 dark:border-white/10 hover:border-amber-500/30 text-slate-800 dark:text-slate-200 transition-all group min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                                <span class="material-symbols-outlined text-lg">campaign</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Announcements</span>
                                    <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 shrink-0">Ticker</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Manage up to 5 active announcements</p>
                            </div>
                        </a>
                    @endif

                    {{-- Assignment Center & Drafts (Contributor, Journalist, Editor & Management) --}}
                    @if(auth()->canCreateArticle())
                        <a href="{{ url('dashboard/assignments') }}" 
                           class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] hover:bg-indigo-500/10 border border-slate-200/80 dark:border-white/10 hover:border-indigo-500/30 text-slate-800 dark:text-slate-200 transition-all group min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                                <span class="material-symbols-outlined text-lg">assignment</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Assignments & Drafts</span>
                                    <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 shrink-0">Tasks</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Pitches, drafts & deadlines</p>
                            </div>
                        </a>
                    @endif

                    {{-- Edit About Us (Editor & Management) --}}
                    @if(auth()->isEditor())
                        <a href="{{ url('about/edit') }}" 
                           class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] hover:bg-amber-500/10 border border-slate-200/80 dark:border-white/10 hover:border-amber-500/30 text-slate-800 dark:text-slate-200 transition-all group min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                                <span class="material-symbols-outlined text-lg">edit_document</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Edit About Us</span>
                                    <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 shrink-0">Page</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Update mission, story & stats</p>
                            </div>
                        </a>
                    @endif

                    {{-- Saved Stories (All Authenticated) --}}
                    <a href="{{ url('bookmarks') }}" 
                       class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] hover:bg-primary/10 border border-slate-200/80 dark:border-white/10 hover:border-primary/30 text-slate-800 dark:text-slate-200 transition-all group min-w-0">
                        <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                            <span class="material-symbols-outlined text-lg">bookmark</span>
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate">Saved Stories</div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Your bookmarked reading list</p>
                        </div>
                    </a>
                </div>
            @endauth

            <!-- General Navigation Section -->
            <div class="space-y-1 pt-2 border-t border-slate-200/80 dark:border-white/10 min-w-0">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 px-1 mb-1.5">
                    Navigation
                </div>

                <a href="{{ url('') }}" 
                   class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 text-xs font-bold text-slate-700 dark:text-slate-300 transition-all min-w-0">
                    <span class="material-symbols-outlined text-lg text-primary shrink-0">dynamic_feed</span>
                    <span class="truncate">Home Feed</span>
                </a>

                <a href="{{ url('about') }}" 
                   class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 text-xs font-bold text-slate-700 dark:text-slate-300 transition-all min-w-0">
                    <span class="material-symbols-outlined text-lg text-indigo-400 shrink-0">info</span>
                    <span class="truncate">About Us</span>
                </a>

                @auth
                    <a href="{{ url('profile') }}" 
                       class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 text-xs font-bold text-slate-700 dark:text-slate-300 transition-all min-w-0">
                        <span class="material-symbols-outlined text-lg text-emerald-500 shrink-0">account_circle</span>
                        <span class="truncate">My Profile & Account</span>
                    </a>
                @endauth
            </div>

            {{-- TEMPORARY FEATURE: Per-Account Demo Role Switcher (Management & Editor Only) --}}
            @if(auth()->canUseRoleSwitcher())
                @php
                    $allowedSwitchRoles = auth()->availableSwitchRoles();
                    $origRole = auth()->originalRole();
                    $origUser = auth()->originalUser();
                    $currentActiveRole = auth()->check() ? auth()->role() : 'guest';
                @endphp
                <div class="pt-2 border-t border-slate-200/80 dark:border-white/10 space-y-1.5 min-w-0">
                    <div class="flex items-center justify-between px-1">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-purple-400">swap_horiz</span>
                            <span>Demo Role Switcher</span>
                        </span>
                        @if(auth()->isRoleOverridden())
                            <a href="{{ url('switch-user/reset') }}" class="text-[9px] font-bold text-amber-500 hover:underline truncate">
                                Reset ({{ $origRole }})
                            </a>
                        @else
                            <span class="text-[9px] font-bold text-purple-500 dark:text-purple-400 truncate">
                                {{ $currentActiveRole }}
                            </span>
                        @endif
                    </div>

                    <div class="px-2 py-1 rounded-lg bg-purple-500/5 border border-purple-500/15 text-[10px] text-slate-500 dark:text-slate-400 flex items-center justify-between gap-1">
                        <span class="truncate">Account: <strong class="text-slate-800 dark:text-slate-200">{{ $origUser['name'] ?? 'User' }}</strong></span>
                        <span class="text-[9px] uppercase font-bold text-purple-500 shrink-0">{{ $origRole }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-1 p-1.5 rounded-xl bg-slate-50 dark:bg-white/[0.02] border border-slate-200/80 dark:border-white/5 min-w-0">
                        @if(in_array('management', $allowedSwitchRoles, true))
                            <a href="{{ url('switch-user/management') }}" 
                               class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-200/70 dark:hover:bg-white/10 text-xs transition-all min-w-0 {{ $currentActiveRole === 'management' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/30' : 'text-slate-700 dark:text-slate-300' }}">
                                <div class="flex items-center gap-1.5 min-w-0 truncate">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="truncate">Management</span>
                                    @if($origRole === 'management')
                                        <span class="text-[10px] text-slate-400 font-normal shrink-0">(Default)</span>
                                    @endif
                                </div>
                                @if($currentActiveRole === 'management')
                                    <span class="material-symbols-outlined text-xs text-emerald-500 shrink-0">check</span>
                                @endif
                            </a>
                        @endif

                        @if(in_array('editor', $allowedSwitchRoles, true))
                            <a href="{{ url('switch-user/editor') }}" 
                               class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-200/70 dark:hover:bg-white/10 text-xs transition-all min-w-0 {{ $currentActiveRole === 'editor' ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 font-bold border border-purple-500/30' : 'text-slate-700 dark:text-slate-300' }}">
                                <div class="flex items-center gap-1.5 min-w-0 truncate">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 shrink-0"></span>
                                    <span class="truncate">Editor</span>
                                    @if($origRole === 'editor')
                                        <span class="text-[10px] text-slate-400 font-normal shrink-0">(Default)</span>
                                    @endif
                                </div>
                                @if($currentActiveRole === 'editor')
                                    <span class="material-symbols-outlined text-xs text-purple-500 shrink-0">check</span>
                                @endif
                            </a>
                        @endif

                        @if(in_array('journalist', $allowedSwitchRoles, true))
                            <a href="{{ url('switch-user/journalist') }}" 
                               class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-200/70 dark:hover:bg-white/10 text-xs transition-all min-w-0 {{ $currentActiveRole === 'journalist' ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-bold border border-indigo-500/30' : 'text-slate-700 dark:text-slate-300' }}">
                                <div class="flex items-center gap-1.5 min-w-0 truncate">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                                    <span class="truncate">Journalist</span>
                                </div>
                                @if($currentActiveRole === 'journalist')
                                    <span class="material-symbols-outlined text-xs text-indigo-500 shrink-0">check</span>
                                @endif
                            </a>
                        @endif

                        @if(in_array('contributor', $allowedSwitchRoles, true))
                            <a href="{{ url('switch-user/contributor') }}" 
                               class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-200/70 dark:hover:bg-white/10 text-xs transition-all min-w-0 {{ $currentActiveRole === 'contributor' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/30' : 'text-slate-700 dark:text-slate-300' }}">
                                <div class="flex items-center gap-1.5 min-w-0 truncate">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 shrink-0"></span>
                                    <span class="truncate">Contributor</span>
                                </div>
                                @if($currentActiveRole === 'contributor')
                                    <span class="material-symbols-outlined text-xs text-sky-500 shrink-0">check</span>
                                @endif
                            </a>
                        @endif

                        @if(in_array('reader_registered', $allowedSwitchRoles, true))
                            <a href="{{ url('switch-user/reader_registered') }}" 
                               class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-200/70 dark:hover:bg-white/10 text-xs transition-all min-w-0 {{ $currentActiveRole === 'reader_registered' ? 'bg-slate-500/10 text-slate-800 dark:text-white font-bold border border-slate-500/30' : 'text-slate-700 dark:text-slate-300' }}">
                                <div class="flex items-center gap-1.5 min-w-0 truncate">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                    <span class="truncate">Registered Reader</span>
                                </div>
                                @if($currentActiveRole === 'reader_registered')
                                    <span class="material-symbols-outlined text-xs text-slate-400 shrink-0">check</span>
                                @endif
                            </a>
                        @endif

                        @if(in_array('guest', $allowedSwitchRoles, true))
                            <a href="{{ url('switch-user/guest') }}" 
                               class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-rose-500/10 text-xs transition-all min-w-0 {{ $currentActiveRole === 'guest' ? 'bg-rose-500/10 text-rose-500 font-bold border border-rose-500/30' : 'text-slate-600 dark:text-slate-400' }}">
                                <div class="flex items-center gap-1.5 min-w-0 truncate">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span>
                                    <span class="truncate">Guest</span>
                                    <span class="text-[10px] text-slate-400 font-normal shrink-0">(Preview)</span>
                                </div>
                                @if($currentActiveRole === 'guest')
                                    <span class="material-symbols-outlined text-xs text-rose-500 shrink-0">check</span>
                                @endif
                            </a>
                        @endif
                    </div>
                </div>
            @endif

        </div>

        <!-- Sidebar Footer: Theme Toggle & Sign Out -->
        <div class="p-4 border-t border-slate-200/80 dark:border-white/10 bg-slate-50/50 dark:bg-black/20 space-y-2.5 shrink-0 min-w-0">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                <span class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">palette</span>
                    <span>Appearance</span>
                </span>
                <button type="button" @click="toggleTheme()" 
                        class="px-2.5 py-1 rounded-xl bg-slate-200 dark:bg-white/10 hover:bg-slate-300 dark:hover:bg-white/20 text-slate-800 dark:text-white font-semibold transition-all flex items-center gap-1.5 text-xs">
                    <span class="material-symbols-outlined text-xs" x-text="isDark ? 'light_mode' : 'dark_mode'"></span>
                    <span x-text="isDark ? 'Light' : 'Dark'"></span>
                </button>
            </div>

            @auth
                <a href="{{ url('logout') }}" 
                   class="flex items-center justify-center gap-1.5 w-full py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold transition-all">
                    <span class="material-symbols-outlined text-base">logout</span>
                    <span>Sign Out</span>
                </a>
            @endauth
        </div>

    </aside>
</div>
