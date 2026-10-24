@extends('layouts.app')

@section('content')
<div class="py-12 max-w-md mx-auto px-4">
    <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-200 dark:border-white/10 shadow-2xl">
        
        <div class="text-center mb-8">
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-tr from-primary to-tertiary text-white font-display font-black text-2xl shadow-lg shadow-primary/30 mb-3">
                B
            </span>
            <h2 class="text-2xl font-black font-display text-slate-900 dark:text-white">Welcome Back</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sign in to your Brighteen account</p>
        </div>

        @if(!empty($error))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">error</span>
                <span>{{ $error }}</span>
            </div>
        @endif

        <form action="{{ url('login') }}" method="POST" class="space-y-4">
            <input type="hidden" name="redirect" value="{{ $redirect ?? '' }}">

            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5">Email Address</label>
                <input type="email" name="email" required value="{{ $oldEmail ?? 'editor@brighteen.com' }}"
                       class="w-full px-4 py-3 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5">Password</label>
                <input type="password" name="password" required value="password123"
                       class="w-full px-4 py-3 rounded-2xl bg-slate-100 dark:bg-surface-card border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>

            <button type="submit" class="w-full py-3.5 px-6 rounded-2xl bg-primary hover:bg-primary-hover text-white font-bold text-sm shadow-lg shadow-primary/25 transition-all mt-2">
                Sign In
            </button>
        </form>

        <!-- Fast Demo Credentials Quick Picker -->
        <div class="mt-8 pt-6 border-t border-slate-200 dark:border-white/10 space-y-3">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider text-center">
                ⚡ Instant 1-Click Role Switcher
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <a href="{{ url('switch-user/management') }}" class="p-2.5 rounded-xl bg-secondary/10 hover:bg-secondary/20 text-secondary-light font-bold text-center border border-secondary/20 transition-all">
                    Management
                </a>
                <a href="{{ url('switch-user/editor') }}" class="p-2.5 rounded-xl bg-tertiary/10 hover:bg-tertiary/20 text-tertiary-light font-bold text-center border border-tertiary/20 transition-all">
                    Editor
                </a>
                <a href="{{ url('switch-user/journalist') }}" class="p-2.5 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary-light font-bold text-center border border-primary/20 transition-all">
                    Journalist
                </a>
                <a href="{{ url('switch-user/contributor') }}" class="p-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 font-bold text-center border border-amber-500/20 transition-all">
                    Contributor
                </a>
            </div>
            <a href="{{ url('switch-user/reader_registered') }}" class="block w-full py-2 px-3 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 font-bold text-xs text-center border border-white/5 transition-all">
                Login as Verified Reader
            </a>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            Don't have an account? <a href="{{ url('register') }}" class="text-primary hover:underline font-bold">Register free</a>
        </div>

    </div>
</div>
@endsection
