@extends('layouts.app')

@section('content')
@php
    $userNameParts = [];
    if (auth()->check()) {
        $parts = explode(' ', auth()->user()['name'] ?? '', 2);
        $userNameParts = [$parts[0] ?? '', $parts[1] ?? ''];
    }
@endphp
<div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16"
     x-data="{
         contributorModalOpen: false,
         guestPromptOpen: false,
         firstName: '{{ addslashes($myApplication['first_name'] ?? ($userNameParts[0] ?? '')) }}',
         lastName: '{{ addslashes($myApplication['last_name'] ?? ($userNameParts[1] ?? '')) }}',
         fullName: '{{ addslashes($myApplication['full_name'] ?? (auth()->check() ? (auth()->user()['name'] ?? '') : '')) }}',
         updateFullName() {
             this.fullName = (this.firstName + ' ' + this.lastName).trim();
         }
     }">

    <!-- Notification Alert -->
    @if(!empty($notice) && $notice === 'about_updated')
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm font-semibold flex items-center justify-between gap-3 shadow-sm animate-in fade-in">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span>The About Us page content, mission, and platform metrics have been updated successfully!</span>
            </div>
            <a href="{{ url('about') }}" class="text-xs font-bold underline hover:text-emerald-300">Dismiss</a>
        </div>
    @endif

    @if(!empty($notice) && $notice === 'contributor_applied')
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm font-semibold flex items-center justify-between gap-3 shadow-sm animate-in fade-in">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span>Your application to become a Community Contributor has been submitted to Management! Once approved, your account will be promoted to Contributor.</span>
            </div>
            <a href="{{ url('about#get-involved') }}" class="text-xs font-bold underline hover:text-emerald-300">Dismiss</a>
        </div>
    @endif

    @if(!empty($error) && $error === 'missing_application_fields')
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-500 dark:text-rose-400 text-sm font-semibold flex items-center justify-between gap-3 shadow-sm animate-in fade-in">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">error</span>
                <span>Please fill in all required fields (First name, Last name, Birth date, Institution, and Grade/Position).</span>
            </div>
            <a href="{{ url('about#get-involved') }}" class="text-xs font-bold underline hover:text-rose-300">Dismiss</a>
        </div>
    @endif

    <!-- Editor & Management Control Bar -->
    @if(!empty($canEdit))
        <div class="p-4 rounded-3xl bg-gradient-to-r from-primary/15 via-indigo-500/10 to-pink-500/15 border border-primary/30 flex flex-wrap items-center justify-between gap-4 shadow-lg shadow-primary/5">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-primary text-white flex items-center justify-center shadow-md shadow-primary/30">
                    <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Editorial & Management Controls</h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-primary/20 text-primary">Live Mode</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        You have permission to edit this page's headlines, narrative, mission, and live metrics.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                @if(auth()->isEditor())
                    <a href="{{ url('dashboard/editorial') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
                        Editorial Desk
                    </a>
                @endif
                @if(auth()->isManagement())
                    <a href="{{ url('dashboard/management') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
                        Management
                    </a>
                @endif
                <a href="{{ url('about/edit') }}" 
                   class="px-5 py-2 rounded-xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-primary/25 transition-all flex items-center gap-2 transform hover:-translate-y-0.5">
                    <span class="material-symbols-outlined text-base">edit_note</span>
                    <span>Edit About Us Page</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Hero Section -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest bg-primary/10 text-primary border border-primary/20">
            <span class="material-symbols-outlined text-sm">auto_awesome</span>
            <span>bright student, active teen</span>
        </div>
        <h1 class="text-4xl sm:text-6xl font-black font-display tracking-tight text-slate-900 dark:text-white leading-tight">
            {{ $page['title'] }}
        </h1>
        <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
            {{ $page['subtitle'] }}
        </p>
    </div>

    <!-- Hero Image Banner -->
    @if(!empty($page['hero_image']))
        <div class="relative rounded-3xl overflow-hidden aspect-21/9 max-h-[460px] shadow-2xl border border-slate-200 dark:border-white/10 group">
            <img src="{{ $page['hero_image'] }}" alt="{{ $page['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>
            <div class="absolute bottom-6 left-6 right-6 sm:bottom-8 sm:left-8 flex flex-wrap items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/20 backdrop-blur-md text-white border border-white/20">
                        Brighteen Magazine &bull; Brighton JHS
                    </span>
                    <h3 class="text-xl sm:text-2xl font-black font-display text-white">Publication & Creation Media for Brightonians</h3>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-300 bg-slate-900/60 backdrop-blur-md px-3.5 py-2 rounded-2xl border border-white/10">
                    <span class="material-symbols-outlined text-emerald-400 text-sm">verified</span>
                    <span>Brighton JHS Creative Community</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Platform Impact Metrics Highlights Grid -->
    @if(!empty($page['stats']) && is_array($page['stats']))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
            @foreach($page['stats'] as $st)
                <div class="p-6 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 relative overflow-hidden group hover:border-primary/40 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-xl">{{ $st['icon'] ?? 'insights' }}</span>
                        </span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black font-display text-slate-900 dark:text-white tracking-tight">
                        {{ $st['value'] }}
                    </div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1">
                        {{ $st['label'] }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Mission & Manifesto Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Card 1: Mission -->
        <div class="p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-4 relative overflow-hidden">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-primary to-indigo-500 text-white flex items-center justify-center shadow-lg shadow-primary/25">
                <span class="material-symbols-outlined text-2xl">rocket_launch</span>
            </div>
            <h3 class="text-2xl font-black font-display text-slate-900 dark:text-white">Our Mission</h3>
            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 font-medium">
                {{ $page['mission'] }}
            </p>
        </div>

        <!-- Card 2: Manifesto & Values -->
        <div class="p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-4 relative overflow-hidden">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-500 text-white flex items-center justify-center shadow-lg shadow-pink-500/25">
                <span class="material-symbols-outlined text-2xl">verified_user</span>
            </div>
            <h3 class="text-2xl font-black font-display text-slate-900 dark:text-white">Our Manifesto</h3>
            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 font-medium">
                {{ $page['manifesto'] }}
            </p>
        </div>
    </div>

    <!-- Main Narrative & Story Content -->
    <div class="p-8 sm:p-12 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 space-y-6">
        <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider">
            <span class="material-symbols-outlined text-base">history_edu</span>
            <span>The Origin Story</span>
        </div>
        <div class="prose prose-slate dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 text-base leading-relaxed">
            {!! $page['content'] !!}
        </div>
    </div>

    <!-- The Newsroom & Voices -->
    @if(!empty($team))
        <div class="space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-xs font-bold text-primary uppercase tracking-widest">Voices & Architects</span>
                <h2 class="text-3xl font-black font-display text-slate-900 dark:text-white">The Brighteen Newsroom</h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Meet the youth editors, staff journalists, and creators orchestrating our daily dispatches.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($team as $m)
                    <div class="p-6 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 text-center space-y-3 group hover:border-primary/40 transition-all">
                        <div class="relative w-20 h-20 mx-auto">
                            <img src="{{ $m['avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80' }}"
                                 alt="{{ $m['name'] }}" 
                                 class="w-20 h-20 rounded-2xl object-cover ring-2 ring-primary/30 group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <div>
                            <h4 class="font-bold text-base text-slate-900 dark:text-white">{{ $m['name'] }}</h4>
                            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider
                                @if($m['role_name'] === 'management') bg-secondary/15 text-secondary-light border border-secondary/20
                                @elseif($m['role_name'] === 'editor') bg-tertiary/15 text-tertiary-light border border-tertiary/20
                                @elseif($m['role_name'] === 'journalist') bg-indigo-500/15 text-indigo-500 border border-indigo-500/20
                                @else bg-slate-200 dark:bg-white/10 text-slate-600 dark:text-slate-300
                                @endif">
                                {{ $m['role_display_name'] ?? ucfirst($m['role_name']) }}
                            </span>
                        </div>
                        @if(!empty($m['bio']))
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-3">
                                {{ $m['bio'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Call To Action Section -->
    <div id="get-involved" class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-primary via-indigo-600 to-purple-600 text-white text-center space-y-6 relative overflow-hidden shadow-2xl shadow-primary/20">
        <div class="relative z-10 max-w-2xl mx-auto space-y-4">
            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-white/20 backdrop-blur-md">
                Get Involved
            </span>
            <h2 class="text-3xl sm:text-4xl font-black font-display tracking-tight">
                Got a Story That Needs to Be Told?
            </h2>
            <p class="text-sm sm:text-base text-white/90 leading-relaxed">
                Whether you're breaking news from your school lab, documenting an indie game mod, or critiquing emerging internet lore—we want your voice.
            </p>

            @if(auth()->check() && auth()->isReaderRegistered())
                {{-- Role: Registered Reader applying for Contributor --}}
                @if(!empty($myApplication) && $myApplication['status'] === 'pending')
                    <div class="p-4 rounded-2xl bg-amber-500/25 border border-amber-300/40 text-white text-xs max-w-lg mx-auto flex items-center justify-between gap-3 text-left backdrop-blur-md shadow-lg">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-xl bg-amber-400 text-amber-950 flex items-center justify-center shrink-0 font-bold">
                                <span class="material-symbols-outlined text-lg">hourglass_top</span>
                            </span>
                            <div>
                                <h5 class="font-bold text-sm text-white">Application Under Review</h5>
                                <p class="text-[11px] text-amber-100">Submitted on {{ date('M d, Y', strtotime($myApplication['created_at'])) }}. Management is reviewing your contributor credentials.</p>
                            </div>
                        </div>
                        <button type="button" @click="contributorModalOpen = true" 
                                class="px-3.5 py-2 rounded-xl bg-white text-slate-900 font-bold text-xs shrink-0 hover:bg-slate-100 shadow transition-all">
                            Review Form
                        </button>
                    </div>
                @elseif(!empty($myApplication) && $myApplication['status'] === 'rejected')
                    <div class="p-4 rounded-2xl bg-rose-500/25 border border-rose-300/40 text-white text-xs max-w-lg mx-auto flex items-center justify-between gap-3 text-left backdrop-blur-md shadow-lg">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-xl bg-rose-400 text-rose-950 flex items-center justify-center shrink-0 font-bold">
                                <span class="material-symbols-outlined text-lg">info</span>
                            </span>
                            <div>
                                <h5 class="font-bold text-sm text-white">Application Needs Revision</h5>
                                <p class="text-[11px] text-rose-100">{{ $myApplication['rejection_reason'] ?: 'Please update your details and submit again.' }}</p>
                            </div>
                        </div>
                        <button type="button" @click="contributorModalOpen = true" 
                                class="px-3.5 py-2 rounded-xl bg-white text-slate-900 font-bold text-xs shrink-0 hover:bg-slate-100 shadow transition-all">
                            Re-Apply
                        </button>
                    </div>
                @else
                    <div class="space-y-3 pt-2">
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <button type="button" @click="contributorModalOpen = true"
                                    class="px-6 py-3.5 rounded-2xl bg-white text-slate-900 hover:bg-slate-100 font-black text-xs uppercase tracking-wider shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-primary">edit_square</span>
                                <span>Start Writing (Register as Contributor)</span>
                            </button>
                            <a href="{{ url('') }}" 
                               class="px-6 py-3.5 rounded-2xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs uppercase tracking-wider backdrop-blur-md transition-all">
                                Explore Stories
                            </a>
                        </div>
                        <p class="text-[11px] text-white/80">
                            Registered readers submit a quick profile form to be approved as a community contributor.
                        </p>
                    </div>
                @endif
            @elseif(auth()->check() && auth()->canCreateArticle())
                {{-- Role: Already Contributor, Journalist, Editor, or Management --}}
                <div class="space-y-3 pt-2">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ url('articles/create') }}" 
                           class="px-6 py-3.5 rounded-2xl bg-white text-slate-900 hover:bg-slate-100 font-black text-xs uppercase tracking-wider shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                            <span class="material-symbols-outlined text-base text-primary">edit_square</span>
                            <span>Start Writing (Writer Workbench)</span>
                        </a>
                        <a href="{{ url('') }}" 
                           class="px-6 py-3.5 rounded-2xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs uppercase tracking-wider backdrop-blur-md transition-all">
                            Explore Stories
                        </a>
                    </div>
                    <p class="text-[11px] text-white/80">
                        Logged in as <strong class="capitalize">{{ auth()->user()['role_name'] }}</strong> &bull; Direct drafting enabled.
                    </p>
                </div>
            @else
                {{-- Guest Mode --}}
                <div class="space-y-3 pt-2">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button type="button" @click="guestPromptOpen = true"
                                class="px-6 py-3.5 rounded-2xl bg-white text-slate-900 hover:bg-slate-100 font-black text-xs uppercase tracking-wider shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                            <span class="material-symbols-outlined text-base text-primary">edit_square</span>
                            <span>Start Writing</span>
                        </button>
                        <a href="{{ url('') }}" 
                           class="px-6 py-3.5 rounded-2xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs uppercase tracking-wider backdrop-blur-md transition-all">
                            Explore Stories
                        </a>
                    </div>
                    <p class="text-[11px] text-white/80">
                        Sign in as a registered reader to join the community contributor team.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- CONTRIBUTOR REGISTRATION MODAL FOR REGISTERED READERS -->
    <div x-cloak x-show="contributorModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="contributorModalOpen = false"></div>
        
        <div class="relative w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-primary/30 z-10 p-6 sm:p-8 text-slate-900 dark:text-white animate-in fade-in zoom-in-95">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">app_registration</span>
                    </span>
                    <div>
                        <h3 class="text-lg font-black font-display text-slate-900 dark:text-white">Become a Community Contributor</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Submit your author credentials to Management for contributor promotion</p>
                    </div>
                </div>
                <button type="button" @click="contributorModalOpen = false" 
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-500 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>

            <!-- Form -->
            <form action="{{ url('about/contributor-apply') }}" method="POST" class="space-y-4 pt-4">
                
                <!-- Notice / Description -->
                <div class="p-3.5 rounded-2xl bg-primary/5 dark:bg-primary/10 border border-primary/20 text-xs text-slate-600 dark:text-slate-300 space-y-1">
                    <p class="font-bold text-primary flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        <span>Promoted Contributor Privileges</span>
                    </p>
                    <p class="text-[11px] leading-relaxed">
                        Registered readers promoted to Contributor can draft new stories, access the Writer Workbench with AI assistance, and submit pieces to the Editorial Desk.
                    </p>
                </div>

                <!-- 1. First Name & Last Name (2 Cols) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            First Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="first_name" x-model="firstName" @input="updateFullName()" required
                               placeholder="e.g. Jordan"
                               value="{{ $myApplication['first_name'] ?? ($userNameParts[0] ?? '') }}"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Last Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="last_name" x-model="lastName" @input="updateFullName()" required
                               placeholder="e.g. Taylor"
                               value="{{ $myApplication['last_name'] ?? ($userNameParts[1] ?? '') }}"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                </div>

                <!-- 2. Full Name (Auto-calculated) -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Full Name <span class="text-slate-400 text-[10px] font-normal">(Auto-calculated)</span>
                        </label>
                        <span class="text-[10px] font-mono text-primary font-bold">Auto</span>
                    </div>
                    <input type="text" name="full_name" x-model="fullName" readonly
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10 text-xs text-slate-600 dark:text-slate-400 font-semibold cursor-not-allowed">
                </div>

                <!-- 3. Author Name (Optional) -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Author Name / Byline <span class="text-slate-400 text-[10px] font-normal">(Optional)</span>
                    </label>
                    <input type="text" name="author_name" 
                           placeholder="Public byline on articles (leave blank to use Full Name)"
                           value="{{ $myApplication['author_name'] ?? '' }}"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <!-- 4. Email & WhatsApp (2 Cols) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" required
                               value="{{ $myApplication['email'] ?? (auth()->check() ? (auth()->user()['email'] ?? '') : '') }}"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            WhatsApp Number <span class="text-slate-400 text-[10px] font-normal">(Optional)</span>
                        </label>
                        <input type="tel" name="whatsapp" 
                               placeholder="e.g. +6281234567890"
                               value="{{ $myApplication['whatsapp'] ?? '' }}"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                </div>

                <!-- 5. Date of Birth -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Date of Birth <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="birth_date" required
                           value="{{ $myApplication['birth_date'] ?? '' }}"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <!-- 6. School / Institution & Grade / Position (2 Cols) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            School / Institution <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="institution" required
                               placeholder="e.g. Brighton Academy, High School"
                               value="{{ $myApplication['institution'] ?? '' }}"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Grade / Role / Position <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="grade_or_position" required
                               placeholder="e.g. Grade 11 / Student Council"
                               value="{{ $myApplication['grade_or_position'] ?? '' }}"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-white/10">
                    <button type="button" @click="contributorModalOpen = false" 
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-xs font-bold text-slate-700 dark:text-slate-300 transition-all">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-primary/25 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>Submit Application</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- GUEST SIGN IN PROMPT MODAL -->
    <div x-cloak x-show="guestPromptOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="guestPromptOpen = false"></div>
        <div class="relative w-full max-w-md rounded-3xl bg-white dark:bg-[#151922] shadow-2xl border border-slate-200 dark:border-white/10 z-10 p-6 sm:p-8 text-center space-y-4 animate-in fade-in zoom-in-95 text-slate-900 dark:text-white">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">rate_review</span>
            </div>
            <div>
                <h3 class="text-xl font-black font-display">Sign In to Apply as Contributor</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                    To apply as a community contributor, please sign in or register as a member first.
                </p>
            </div>
            <div class="flex items-center gap-2.5 pt-2">
                <a href="{{ url('login?redirect=about#get-involved') }}" class="flex-1 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all text-center">
                    Sign In
                </a>
                <a href="{{ url('register?redirect=about#get-involved') }}" class="flex-1 py-2.5 rounded-xl bg-slate-100 dark:bg-white/10 hover:bg-slate-200 text-slate-800 dark:text-white text-xs font-bold transition-all text-center">
                    Join Brighteen
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
