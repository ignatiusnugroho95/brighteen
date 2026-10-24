@extends('layouts.app')

@section('content')
<div class="py-20 text-center max-w-md mx-auto px-4">
    <div class="w-16 h-16 mx-auto rounded-3xl bg-rose-500/10 text-rose-500 flex items-center justify-center mb-6">
        <span class="material-symbols-outlined text-4xl">gpp_bad</span>
    </div>
    <h1 class="text-4xl font-black font-display text-white mb-2">403 - Access Restricted</h1>
    <p class="text-xs text-slate-400 mb-8">{{ $message ?? 'You do not have the required permissions to access this area.' }}</p>
    <div class="flex items-center justify-center gap-3">
        <a href="{{ url('') }}" class="px-5 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all">
            Home Feed
        </a>
        <a href="{{ url('login') }}" class="px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-lg shadow-primary/25 transition-all">
            Switch Account
        </a>
    </div>
</div>
@endsection
