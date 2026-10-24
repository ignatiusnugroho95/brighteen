@extends('layouts.app')

@section('content')
<div class="py-20 text-center max-w-md mx-auto px-4">
    <div class="w-16 h-16 mx-auto rounded-3xl bg-primary/10 text-primary flex items-center justify-center mb-6">
        <span class="material-symbols-outlined text-4xl">search_off</span>
    </div>
    <h1 class="text-4xl font-black font-display text-white mb-2">404 - Lost in Cyberspace</h1>
    <p class="text-xs text-slate-400 mb-8">{{ $message ?? 'The page or story you are looking for does not exist or has been moved.' }}</p>
    <a href="{{ url('') }}" class="px-6 py-3 rounded-2xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-lg shadow-primary/25 transition-all inline-block">
        Return to Homepage
    </a>
</div>
@endsection
