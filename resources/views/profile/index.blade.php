@extends('layouts.app')

@section('content')
@php
    $currentAvatar = $user['avatar'] ?: $defaultAvatar;
    $hasCustomAvatar = !empty($user['avatar']) && $user['avatar'] !== $defaultAvatar;
    
    // Preset detection for School / Institution
    $userInst = $user['institution'] ?: 'Brighton JHS Depok';
    $isInstPreset = ($userInst === 'Brighton JHS Depok');
    $initialInstChoice = $isInstPreset ? 'Brighton JHS Depok' : 'Others';
    $initialInstCustom = $isInstPreset ? '' : $userInst;

    // Preset detection for Grade / Role / Position
    $userGrade = $user['grade_or_position'] ?: '7A';
    $gradePresets = ['7A', '7B', '8A', '8B', '9A', '9B', 'Teacher'];
    $isGradePreset = in_array($userGrade, $gradePresets);
    $initialGradeChoice = $isGradePreset ? $userGrade : 'Others';
    $initialGradeCustom = $isGradePreset ? '' : $userGrade;
@endphp

<div class="max-w-5xl mx-auto space-y-8 pb-16 min-w-0"
     x-data="{
        firstName: '{{ addslashes($user['first_name'] ?? '') }}',
        lastName: '{{ addslashes($user['last_name'] ?? '') }}',
        fullName: '{{ addslashes($user['name'] ?? '') }}',
        authorName: '{{ addslashes($user['author_name'] ?? '') }}',
        instChoice: '{{ $initialInstChoice }}',
        instCustom: '{{ addslashes($initialInstCustom) }}',
        gradeChoice: '{{ $initialGradeChoice }}',
        gradeCustom: '{{ addslashes($initialGradeCustom) }}',
        avatarPreview: '{{ $currentAvatar }}',
        hasCustomAvatar: {{ $hasCustomAvatar ? 'true' : 'false' }},
        removeAvatar: false,
        activeTab: 'profile', // 'profile' | 'security'

        updateFullName() {
            this.fullName = (this.firstName.trim() + ' ' + this.lastName.trim()).trim();
        },

        handleAvatarChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.removeAvatar = false;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.avatarPreview = e.target.result;
                    this.hasCustomAvatar = true;
                };
                reader.readAsDataURL(file);
            }
        },

        resetToDefaultAvatar() {
            this.avatarPreview = '{{ $defaultAvatar }}';
            this.hasCustomAvatar = false;
            this.removeAvatar = true;
            const input = document.getElementById('avatarFileInput');
            if (input) input.value = '';
        }
     }">

    <!-- Top Alert Messages -->
    @if(!empty($notice) && $notice === 'saved')
        <div class="p-4 sm:p-5 rounded-3xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm font-semibold flex items-center justify-between gap-3 shadow-lg shadow-emerald-500/5 animate-in fade-in slide-in-from-top-2">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/25">
                    <span class="material-symbols-outlined text-xl">check_circle</span>
                </span>
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Profile & Account Saved Successfully!</h4>
                    <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">Your personal credentials, byline, and school information are updated across the platform.</p>
                </div>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if(!empty($error))
        <div class="p-4 sm:p-5 rounded-3xl bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs sm:text-sm font-semibold flex items-center justify-between gap-3 shadow-lg shadow-rose-500/5 animate-in fade-in slide-in-from-top-2">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-2xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/25">
                    <span class="material-symbols-outlined text-xl">error</span>
                </span>
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Failed to Save Profile</h4>
                    <p class="text-xs text-rose-700 dark:text-rose-400 mt-0.5">
                        @if($error === 'name_required') First Name and Last Name are required.
                        @elseif($error === 'invalid_email') Please provide a valid email address.
                        @elseif($error === 'email_taken') This email address is already in use by another account.
                        @elseif($error === 'birth_date_required') Please pick or enter your Date of Birth.
                        @elseif($error === 'invalid_image_type') Invalid image format. Please upload JPEG, PNG, WEBP, or GIF.
                        @elseif($error === 'image_too_large') Image size exceeds 5MB limit. Please choose a smaller photo.
                        @elseif($error === 'upload_failed') Could not save photo to server. Please try again.
                        @elseif($error === 'incorrect_current_password') Current password does not match our records.
                        @elseif($error === 'password_too_short') New password must be at least 6 characters long.
                        @else Please verify your form inputs and try again.
                        @endif
                    </p>
                </div>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    <!-- Profile Header & Identity Card -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950 border border-indigo-500/30 p-6 sm:p-8 text-white shadow-xl">
        <!-- Ambient Glow -->
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <!-- Left: Avatar & Identity Summary -->
            <div class="flex items-center gap-4 sm:gap-5 min-w-0">
                <div class="relative shrink-0 group">
                    <img :src="avatarPreview" 
                         alt="{{ $user['name'] }}" 
                         class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl object-cover ring-4 ring-white/20 shadow-2xl transition-transform group-hover:scale-105 bg-slate-800">
                    <button type="button" 
                            @click="document.getElementById('avatarFileInput').click()"
                            class="absolute -bottom-1.5 -right-1.5 w-8 h-8 rounded-xl bg-primary text-white flex items-center justify-center shadow-lg hover:bg-primary-hover transition-transform hover:scale-110 ring-2 ring-slate-900"
                            title="Change Photo">
                        <span class="material-symbols-outlined text-sm">photo_camera</span>
                    </button>
                </div>

                <div class="space-y-1.5 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider
                            @if($user['role_name'] === 'management') bg-emerald-500/20 text-emerald-300 border border-emerald-500/30
                            @elseif($user['role_name'] === 'editor') bg-purple-500/20 text-purple-300 border border-purple-500/30
                            @elseif($user['role_name'] === 'journalist') bg-indigo-500/20 text-indigo-300 border border-indigo-500/30
                            @elseif($user['role_name'] === 'contributor') bg-sky-500/20 text-sky-300 border border-sky-500/30
                            @else bg-slate-500/20 text-slate-300 border border-slate-500/30
                            @endif">
                            {{ $user['role_display_name'] ?? $user['role_name'] }}
                        </span>
                        
                        <span class="text-xs text-slate-300 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-sky-400">school</span>
                            <span x-text="instChoice === 'Others' ? (instCustom || 'School / Institution') : instChoice"></span>
                        </span>

                        <span class="text-xs text-slate-400">&bull;</span>

                        <span class="text-xs text-amber-300 font-bold" x-text="gradeChoice === 'Others' ? (gradeCustom || 'Role / Position') : gradeChoice"></span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black font-display tracking-tight text-white truncate" x-text="fullName || '{{ $user['name'] }}'">
                        {{ $user['name'] }}
                    </h1>

                    <div class="flex items-center gap-3 text-xs text-slate-300 flex-wrap">
                        <span class="flex items-center gap-1 font-mono text-slate-300">
                            <span class="material-symbols-outlined text-xs text-primary-light">mail</span>
                            <span>{{ $user['email'] }}</span>
                        </span>

                        <template x-if="authorName">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-white/10 text-white text-[11px] font-semibold border border-white/10">
                                <span class="material-symbols-outlined text-xs text-amber-400">verified</span>
                                <span>Byline: <strong x-text="authorName"></strong></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right: Quick Activity Statistics -->
            <div class="grid grid-cols-3 gap-2.5 sm:gap-3 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-white/10 text-center">
                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Published</span>
                    <strong class="text-lg sm:text-xl font-black text-emerald-400 font-display">{{ number_format($articleStats['published_count'] ?? 0) }}</strong>
                </div>
                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Views</span>
                    <strong class="text-lg sm:text-xl font-black text-sky-400 font-display">{{ number_format($articleStats['total_views'] ?? 0) }}</strong>
                </div>
                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Bookmarks</span>
                    <strong class="text-lg sm:text-xl font-black text-rose-400 font-display">{{ number_format($bookmarksCount ?? 0) }}</strong>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs inside Hero -->
        <div class="mt-6 pt-4 border-t border-white/10 flex items-center justify-between flex-wrap gap-2 text-xs">
            <div class="flex items-center gap-2">
                <button type="button" @click="activeTab = 'profile'"
                        :class="activeTab === 'profile' ? 'bg-white text-slate-950 font-black shadow-md' : 'bg-white/10 hover:bg-white/20 text-white font-semibold'"
                        class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">badge</span>
                    <span>Profile & School Information</span>
                </button>
                <button type="button" @click="activeTab = 'security'"
                        :class="activeTab === 'security' ? 'bg-white text-slate-950 font-black shadow-md' : 'bg-white/10 hover:bg-white/20 text-white font-semibold'"
                        class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">lock_reset</span>
                    <span>Account Security</span>
                </button>
            </div>

            <a href="{{ url('dashboard') }}" class="text-slate-300 hover:text-white font-bold flex items-center gap-1 text-xs py-1">
                <span>Central Dashboard</span>
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </a>
        </div>
    </div>

    <!-- MAIN FORM CONTAINER -->
    <form action="{{ url('profile') }}" method="POST" enctype="multipart/form-data" class="space-y-6">

        <!-- Hidden input for avatar reset -->
        <input type="hidden" name="remove_avatar" :value="removeAvatar ? '1' : '0'">

        <!-- TAB 1: PROFILE & PERSONAL DETAILS -->
        <div x-show="activeTab === 'profile'" class="space-y-6 animate-in fade-in duration-200">
            
            <!-- SECTION 1: PHOTO UPLOAD & DEFAULT TEMPLATE CARD -->
            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">account_circle</span>
                        </span>
                        <div>
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Profile Photo & Avatar</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Upload a personal photo stored securely on the database server or use the default student template.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 pt-2">
                    <!-- Photo Preview Box -->
                    <div class="flex flex-col items-center gap-2 shrink-0">
                        <div class="relative group">
                            <img :src="avatarPreview" 
                                 alt="Avatar Preview" 
                                 class="w-28 h-28 rounded-3xl object-cover ring-4 ring-slate-100 dark:ring-white/10 shadow-lg bg-slate-800 transition-all">
                            <div class="absolute inset-0 rounded-3xl bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity cursor-pointer"
                                 @click="document.getElementById('avatarFileInput').click()">
                                <span class="material-symbols-outlined text-white text-2xl">upload</span>
                            </div>
                        </div>

                        <!-- Template Status Pill -->
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                              :class="hasCustomAvatar ? 'bg-primary/10 text-primary border border-primary/20' : 'bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-400'">
                            <span x-text="hasCustomAvatar ? 'Custom Photo Active' : 'Default Template Avatar'"></span>
                        </span>
                    </div>

                    <!-- Upload Controls & Guidance -->
                    <div class="flex-1 space-y-3 text-center sm:text-left min-w-0">
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Choose a New Photo</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                High-resolution photo recommended (square aspect ratio, JPG, PNG, WEBP, or GIF up to 5MB). Photo will be permanently stored on the Brighteen database server.
                            </p>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" id="avatarFileInput" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif"
                               @change="handleAvatarChange($event)" class="hidden">

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1">
                            <button type="button" @click="document.getElementById('avatarFileInput').click()"
                                    class="px-4 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20 transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">cloud_upload</span>
                                <span>Upload New Photo</span>
                            </button>

                            <button type="button" @click="resetToDefaultAvatar()"
                                    class="px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all flex items-center gap-1.5 border border-slate-200 dark:border-white/10">
                                <span class="material-symbols-outlined text-base text-purple-400">palette</span>
                                <span>Use Default Template</span>
                            </button>
                        </div>

                        <p class="text-[11px] text-slate-400 dark:text-slate-500">
                            If no photo is uploaded, your public profile and articles will automatically display the default school creator avatar.
                        </p>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: PERSONAL IDENTITY & BYLINE -->
            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">badge</span>
                        </span>
                        <div>
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Personal Identity & Author Byline</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Full name is automatically generated from your First Name and Last Name.</p>
                        </div>
                    </div>
                </div>

                <!-- 1. First Name * and Last Name * (2 Columns) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            First Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="first_name" x-model="firstName" @input="updateFullName()" required
                               placeholder="e.g. Jordan"
                               value="{{ $user['first_name'] ?? '' }}"
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Last Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="last_name" x-model="lastName" @input="updateFullName()" required
                               placeholder="e.g. Taylor"
                               value="{{ $user['last_name'] ?? '' }}"
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                    </div>
                </div>

                <!-- 2. Full Name (Auto-calculated) -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Full Name <span class="text-slate-400 text-[11px] font-normal">(Auto)</span>
                        </label>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-primary/10 text-primary font-bold border border-primary/20">
                            Auto-Synchronized
                        </span>
                    </div>
                    <div class="relative">
                        <input type="text" name="full_name" x-model="fullName" readonly
                               class="w-full pl-4 pr-10 py-3 rounded-2xl bg-slate-100 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10 text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-semibold cursor-not-allowed">
                        <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock</span>
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">
                        Automatically compiled from First Name and Last Name. Stored as your primary account name in the database.
                    </p>
                </div>

                <!-- 3. Author Name / Byline (Optional) -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Author Name / Byline <span class="text-slate-400 text-[11px] font-normal">(Optional)</span>
                        </label>
                        <span class="text-[10px] text-slate-400">Public byline on articles</span>
                    </div>
                    <div class="relative">
                        <input type="text" name="author_name" x-model="authorName"
                               placeholder="Public byline on articles (leave blank to use Full Name)"
                               value="{{ $user['author_name'] ?? '' }}"
                               class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">stylus_note</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-xs text-amber-500">info</span>
                        <span>How your name appears on published stories: <strong class="text-slate-800 dark:text-slate-200" x-text="authorName ? authorName : (fullName || 'Your Full Name')"></strong></span>
                    </p>
                </div>

            </div>

            <!-- SECTION 3: CONTACT & SCHOOL CREDENTIALS -->
            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">school</span>
                        </span>
                        <div>
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Contact & School Credentials</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">School affiliation, grade, WhatsApp, and date of birth.</p>
                        </div>
                    </div>
                </div>

                <!-- 4. Email Address * and WhatsApp (Optional) (2 Columns) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] text-primary font-mono font-bold">...@brighteen.id</span>
                        </div>
                        <div class="relative">
                            <input type="email" name="email" required
                                   placeholder="e.g. yourname@brighteen.id"
                                   value="{{ $user['email'] ?? '' }}"
                                   class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all font-mono">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">mail</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            WhatsApp Number <span class="text-slate-400 text-[11px] font-normal">(Optional)</span>
                        </label>
                        <div class="relative">
                            <input type="tel" name="whatsapp"
                                   placeholder="e.g. +62 812 3456 7890"
                                   value="{{ $user['whatsapp'] ?? '' }}"
                                   class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all font-mono">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">call</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Date of Birth * -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Date of Birth <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-slate-400">(pick from calendar or type)</span>
                    </div>
                    <div class="relative">
                        <input type="date" name="birth_date" required
                               value="{{ $user['birth_date'] ?? '' }}"
                               class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">calendar_month</span>
                    </div>
                </div>

                <!-- 6. School / Institution * -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        School / Institution <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <select name="institution_choice" x-model="instChoice"
                                class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all font-medium">
                            <option value="Brighton JHS Depok">Brighton JHS Depok</option>
                            <option value="Others">Others, mention...</option>
                        </select>

                        <!-- Custom School Mention Input -->
                        <div x-show="instChoice === 'Others'" x-transition class="w-full">
                            <input type="text" name="institution_custom" x-model="instCustom"
                                   placeholder="Mention school / institution..."
                                   class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-primary/40 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                        </div>
                    </div>
                </div>

                <!-- 7. Grade / Role / Position * -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Grade / Role / Position <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <select name="grade_choice" x-model="gradeChoice"
                                class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all font-medium">
                            <option value="7A">Grade 7A</option>
                            <option value="7B">Grade 7B</option>
                            <option value="8A">Grade 8A</option>
                            <option value="8B">Grade 8B</option>
                            <option value="9A">Grade 9A</option>
                            <option value="9B">Grade 9B</option>
                            <option value="Teacher">Teacher / Faculty</option>
                            <option value="Others">Others, mention...</option>
                        </select>

                        <!-- Custom Grade / Role Mention Input -->
                        <div x-show="gradeChoice === 'Others'" x-transition class="w-full">
                            <input type="text" name="grade_custom" x-model="gradeCustom"
                                   placeholder="Mention grade / role / position..."
                                   class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-primary/40 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                        </div>
                    </div>
                </div>

                <!-- 8. Bio / About You (Optional) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Bio / Student Motto <span class="text-slate-400 text-[11px] font-normal">(Optional)</span>
                    </label>
                    <textarea name="bio" rows="3"
                              placeholder="Brief bio or statement about your interests, reporting beat, or hobbies..."
                              class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all resize-y">{{ $user['bio'] ?? '' }}</textarea>
                </div>

            </div>
        </div>

        <!-- TAB 2: SECURITY & PASSWORD -->
        <div x-show="activeTab === 'security'" class="space-y-6 animate-in fade-in duration-200">
            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-sm space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">lock</span>
                        </span>
                        <div>
                            <h3 class="text-base font-black font-display text-slate-900 dark:text-white">Account Password</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Leave blank if you do not wish to change your current password.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter current password"
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">New Password</label>
                        <input type="password" name="new_password" placeholder="At least 6 characters"
                               class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- SUBMIT BAR (STICKY BOTTOM / ACTION ROW) -->
        <div class="p-4 sm:p-5 rounded-3xl bg-white dark:bg-surface-card border border-slate-200/80 dark:border-white/10 shadow-lg flex items-center justify-between flex-wrap gap-4">
            <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>All profile changes are instantly updated in the Brighteen database.</span>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ url('dashboard') }}" 
                   class="px-5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-bold transition-all">
                    Cancel
                </a>

                <button type="submit" 
                        class="px-6 sm:px-8 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-hover hover:to-indigo-700 text-white text-xs sm:text-sm font-black uppercase tracking-wider shadow-lg shadow-primary/25 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">save</span>
                    <span>Save Profile Changes</span>
                </button>
            </div>
        </div>

    </form>

</div>
@endsection
