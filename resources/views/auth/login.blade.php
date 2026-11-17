<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Budget Desk</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <script>
        (function () {
            try {
                var theme = localStorage.getItem('theme');
                if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden">
    <div class="aurora" aria-hidden="true">
        <div class="blob blob-a"></div>
        <div class="blob blob-b"></div>
        <div class="blob blob-c"></div>
        <div class="grid-fade"></div>
    </div>

    <button type="button" class="btn-icon fixed right-5 top-5 z-20" onclick="toggleTheme()" aria-label="Toggle theme">
        <x-icon name="sun" class="hidden h-4 w-4 dark:block" />
        <x-icon name="moon" class="h-4 w-4 dark:hidden" />
    </button>

    <div class="relative z-10 mx-auto flex min-h-screen w-full max-w-6xl items-center px-5 py-10">
        <div class="grid w-full items-center gap-10 lg:grid-cols-[1.05fr_.95fr]">
            <div class="rise hidden lg:block">
                <div class="inline-flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-emerald-300 via-emerald-400 to-cyan-400 text-slate-950 shadow-lg shadow-emerald-400/25">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5" />
                            <circle cx="12" cy="12" r="2.6" />
                            <path d="M6 9.5h.01M18 14.5h.01" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Budget Desk</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-400">Personal finance</p>
                    </div>
                </div>

                <h1 class="mt-10 max-w-md text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 dark:text-white">
                    Make every
                    <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-300 bg-clip-text text-transparent">payday</span>
                    count.
                </h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Track daily spending, split your 15-day salary, move money to savings, and watch every bank account in one calm dashboard.
                </p>

                <div class="stagger mt-8 flex flex-wrap gap-2">
                    <span class="chip border border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">15-day pay cycle</span>
                    <span class="chip border border-cyan-500/20 bg-cyan-500/10 text-cyan-700 dark:text-cyan-300">Savings advice</span>
                    <span class="chip border border-violet-500/20 bg-violet-500/10 text-violet-700 dark:text-violet-300">Multi-account</span>
                    <span class="chip border border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300">Private by default</span>
                </div>
            </div>

            <div class="card rise mx-auto w-full max-w-md p-6 sm:p-8">
                <div class="mb-7 lg:hidden">
                    <p class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-white">Budget Desk</p>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-400">Personal finance</p>
                </div>

                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Welcome back</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sign in to your private workspace.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-4">
                    @csrf

                    <div>
                        <label class="label" for="email">Email</label>
                        <input class="input" type="email" id="email" name="email" value="{{ old('email', 'me@budgetdesk.local') }}" required autofocus autocomplete="username">
                        @error('email')
                            <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label" for="password">Password</label>
                        <input class="input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        @error('password')
                            <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <input class="h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-400 dark:border-white/20 dark:bg-white/5" type="checkbox" name="remember" value="1">
                        Keep me signed in on this device
                    </label>

                    <button type="submit" class="btn-primary w-full">
                        Sign in
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </form>

                @if (app()->environment('local'))
                    <p class="mt-6 rounded-xl border border-slate-200/70 bg-slate-50/70 px-3 py-2 text-[11px] text-slate-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-400">
                        Local demo login: <span class="font-semibold text-slate-700 dark:text-slate-200">me@budgetdesk.local</span> / <span class="font-semibold text-slate-700 dark:text-slate-200">password</span>
                    </p>
                @endif

                <p class="mt-6 flex items-center justify-center gap-1.5 text-[10px] text-slate-400">
                    <x-icon name="lock" class="h-3 w-3" /> Single-user app · no registration
                </p>
            </div>
        </div>
    </div>
</body>
</html>
