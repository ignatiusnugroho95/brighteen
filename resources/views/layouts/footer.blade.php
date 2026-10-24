<!-- Footer -->
<footer class="mt-12 sm:mt-16 border-t border-slate-200/80 dark:border-surface-border/60 bg-white/70 dark:bg-surface/70 backdrop-blur-md pb-24 md:pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            
            <!-- Brand & Identity -->
            <div class="space-y-2 max-w-xl">
                <div class="flex items-center flex-wrap gap-2.5">
                    <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-tr from-primary to-tertiary text-white font-display font-black text-lg shadow-md shadow-primary/20">b</span>
                    <span class="text-xl font-black font-display tracking-tight leading-none">
                        <span class="text-black dark:text-white">brigh</span><span class="text-[#1e3a8a] dark:text-blue-400">teen</span>
                    </span>
                    <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">&bull;</span>
                    <p class="text-xs font-bold text-primary dark:text-blue-400 tracking-wide">bright student, active teen</p>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    The official school magazine of Brighton JHS published since 2010. A creative publication media by and for brightonians and the school community.
                </p>
            </div>

            <!-- Social / Community & Status -->
            <div class="flex flex-col sm:flex-row md:flex-col lg:flex-row items-start md:items-end lg:items-center gap-4">
                <!-- Social & Community links -->
                <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                    <a href="{{ url('about') }}" title="About brighteen" class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 dark:bg-surface-card hover:bg-primary/10 hover:text-primary transition-all">
                        <span class="material-symbols-outlined text-base">public</span>
                    </a>
                    <a href="{{ url('?category=pop-culture-lore') }}" title="Podcasts & Culture" class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 dark:bg-surface-card hover:bg-primary/10 hover:text-primary transition-all">
                        <span class="material-symbols-outlined text-base">podcasts</span>
                    </a>
                    <a href="{{ url('?category=tech-future') }}" title="Community Forum" class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 dark:bg-surface-card hover:bg-primary/10 hover:text-primary transition-all">
                        <span class="material-symbols-outlined text-base">forum</span>
                    </a>
                </div>

                <div class="inline-flex items-center gap-1.5 text-xs text-emerald-500 dark:text-emerald-400 font-medium bg-emerald-500/10 dark:bg-emerald-500/15 px-3 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Gemini AI 1.5 Active
                </div>
            </div>

        </div>

        <!-- Bottom Copyright -->
        <div class="mt-6 pt-4 border-t border-slate-200/70 dark:border-surface-border/40 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-400">
            <p>&copy; 2026 brighteen &bull; bright student, active teen. Brighton JHS Community.</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Established December 2010</p>
        </div>
    </div>
</footer>
