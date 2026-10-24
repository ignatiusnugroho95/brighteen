<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'brighteen - bright student, active teen' }}</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Symbols Outlined -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        background: "#0f131d",
                        surface: "#171b26",
                        "surface-card": "#1c1f2a",
                        "surface-high": "#262a35",
                        "surface-border": "#313540",
                        primary: "#3b82f6",
                        "primary-hover": "#2563eb",
                        "primary-light": "#b4c5ff",
                        secondary: "#10b981",
                        "secondary-light": "#4edea3",
                        tertiary: "#8b5cf6",
                        "tertiary-light": "#d2bbff",
                        accent: "#f43f5e",
                        warning: "#f59e0b"
                    },
                    fontFamily: {
                        display: ["Plus Jakarta Sans", "sans-serif"],
                        body: ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        'xl': '1rem',
                        '2xl': '1.5rem',
                        '3xl': '2rem'
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f131d; }
        ::-webkit-scrollbar-thumb { background: #313540; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #3b82f6; }
        .glass-panel {
            background: rgba(28, 31, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .light .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
        .gradient-glow {
            background: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
        }
        @keyframes heartPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(1); opacity: 0; }
        }
        .animate-heart-pop {
            animation: heartPop 0.8s ease-out forwards;
        }
        .font-fill {
            font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;
        }
        @keyframes heartPulseGlow {
            0% {
                transform: scale(1);
                filter: drop-shadow(0 0 0 rgba(244, 63, 94, 0));
            }
            30% {
                transform: scale(1.45);
                filter: drop-shadow(0 0 16px rgba(244, 63, 94, 0.95)) drop-shadow(0 0 6px #f43f5e);
            }
            60% {
                transform: scale(0.9);
                filter: drop-shadow(0 0 10px rgba(244, 63, 94, 0.7));
            }
            100% {
                transform: scale(1);
                filter: drop-shadow(0 0 5px rgba(244, 63, 94, 0.5));
            }
        }
        .animate-heart-glow {
            animation: heartPulseGlow 0.65s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes bookmarkPulseGlow {
            0% {
                transform: scale(1) translateY(0);
                filter: drop-shadow(0 0 0 rgba(59, 130, 246, 0));
            }
            30% {
                transform: scale(1.4) translateY(-3px);
                filter: drop-shadow(0 0 16px rgba(59, 130, 246, 0.95)) drop-shadow(0 0 6px #3b82f6);
            }
            60% {
                transform: scale(0.92) translateY(1px);
                filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.7));
            }
            100% {
                transform: scale(1) translateY(0);
                filter: drop-shadow(0 0 5px rgba(59, 130, 246, 0.5));
            }
        }
        .animate-bookmark-glow {
            animation: bookmarkPulseGlow 0.65s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .glow-red {
            filter: drop-shadow(0 0 8px rgba(244, 63, 94, 0.65));
        }
        .glow-primary {
            filter: drop-shadow(0 0 8px rgba(59, 130, 246, 0.65));
        }
        .story-gradient {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }
        .ig-gradient-text {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        @keyframes marqueeScroll {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
        .animate-marquee-smooth {
            display: inline-flex;
            white-space: nowrap;
            width: max-content;
            animation: marqueeScroll 35s linear infinite;
        }
        .animate-marquee-smooth:hover {
            animation-play-state: paused;
        }
    </style>

    <!-- Dark/Light Theme Persistence Script -->
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-background text-slate-800 dark:text-slate-100 font-body transition-colors duration-300 min-h-screen flex flex-col antialiased selection:bg-primary selection:text-white pb-20 md:pb-0 overflow-x-hidden w-full max-w-full"
      x-data="{ 
          sidebarOpen: false,
          guestModalOpen: false,
          guestActionText: 'interact with posts',
          toastMessage: '',
          toastType: 'success',
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
          },
          showToast(msg, type = 'success') {
              this.toastMessage = msg;
              this.toastType = type;
              setTimeout(() => { this.toastMessage = ''; }, 3500);
          }
      }">

    <!-- Toast Notification -->
    <div x-cloak x-show="toastMessage" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-24 md:bottom-8 right-4 sm:right-6 left-4 sm:left-auto z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl glass-panel text-sm font-semibold border"
         :class="toastType === 'success' ? 'border-secondary/40 text-emerald-400' : 'border-accent/40 text-rose-400'">
        <span class="material-symbols-outlined text-xl" x-text="toastType === 'success' ? 'check_circle' : 'error'"></span>
        <span x-text="toastMessage"></span>
    </div>

    <!-- Main Navigation Header -->
    @include('layouts.header')

    <!-- Main Page Content -->
    <main class="flex-grow w-full max-w-full overflow-x-hidden">
        @yield('content')
    </main>

    <!-- Footer -->
    @include('layouts.footer')

    <!-- Mobile Sticky Bottom Navigation -->
    @include('layouts.bottom-nav')

    <!-- Sleek Guest Interaction Login Modal -->
    <div x-cloak x-show="guestModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" 
             @click="guestModalOpen = false"></div>

        <!-- Modal Box -->
        <div class="relative w-full max-w-md p-6 sm:p-8 rounded-3xl glass-panel shadow-2xl border border-white/10 text-center z-10 animate-in fade-in zoom-in-95 duration-200">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-primary to-tertiary flex items-center justify-center text-white mb-5 shadow-lg shadow-primary/30">
                <span class="material-symbols-outlined text-3xl">lock_open</span>
            </div>
            
            <h3 class="text-2xl font-black font-display text-slate-900 dark:text-white mb-2">Join Brighteen</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                You need a verified account to <span class="text-primary font-semibold" x-text="guestActionText"></span>. It takes less than 30 seconds!
            </p>

            <div class="space-y-3">
                <a href="{{ url('login') }}" class="block w-full py-3.5 px-6 rounded-2xl bg-primary hover:bg-primary-hover text-white font-semibold font-display shadow-lg shadow-primary/25 transition-all transform hover:-translate-y-0.5">
                    Sign In to Your Account
                </a>
                <a href="{{ url('register') }}" class="block w-full py-3.5 px-6 rounded-2xl bg-slate-100 dark:bg-surface-card hover:bg-slate-200 dark:hover:bg-surface-high text-slate-800 dark:text-white font-semibold font-display transition-all border border-slate-200 dark:border-white/10">
                    Create Free Profile
                </a>
            </div>

            <button type="button" @click="guestModalOpen = false" class="mt-5 text-xs text-slate-400 hover:text-white underline">
                Continue browsing as guest
            </button>
        </div>
    </div>

    <!-- Unified Sidebar & Profile Drawer (Full-Viewport Root Layer) -->
    @include('layouts.sidebar')

</body>
</html>
