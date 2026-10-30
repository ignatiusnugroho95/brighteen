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



        <div class="mt-6 text-center text-xs text-slate-400">
            Don't have an account? <a href="{{ url('register') }}" class="text-primary hover:underline font-bold">Register free</a>
        </div>

    </div>
</div>
@endsection
