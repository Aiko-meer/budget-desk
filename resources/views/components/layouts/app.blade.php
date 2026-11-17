<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · Budget Desk</title>
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
    @livewireStyles
</head>
<body class="min-h-screen">
    <div class="aurora" aria-hidden="true">
        <div class="blob blob-a"></div>
        <div class="blob blob-b"></div>
        <div class="blob blob-c"></div>
        <div class="grid-fade"></div>
    </div>

    @php
        $nav = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'expenses', 'label' => 'Expenses', 'icon' => 'expenses'],
            ['route' => 'paydays', 'label' => 'Paydays', 'icon' => 'paydays'],
            ['route' => 'bills', 'label' => 'Bills', 'icon' => 'bills'],
            ['route' => 'budgets', 'label' => 'Budgets', 'icon' => 'budgets'],
            ['route' => 'savings', 'label' => 'Savings', 'icon' => 'savings'],
            ['route' => 'accounts', 'label' => 'Accounts', 'icon' => 'accounts'],
            ['route' => 'reports', 'label' => 'Reports', 'icon' => 'reports'],
        ];
    @endphp

    <div x-data="{ sidebarOpen: false, userMenu: false }" class="min-h-screen">
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[264px] flex-col border-r border-slate-200/70 bg-white/80 backdrop-blur-2xl transition-transform duration-300 dark:border-white/[0.06] dark:bg-[#070b14]/90 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center justify-between px-5 pb-4 pt-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="relative grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-emerald-300 via-emerald-400 to-cyan-400 text-slate-950 shadow-lg shadow-emerald-400/25">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5" />
                            <circle cx="12" cy="12" r="2.6" />
                            <path d="M6 9.5h.01M18 14.5h.01" />
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-[15px] font-extrabold tracking-tight text-slate-900 dark:text-white">Budget Desk</span>
                        <span class="block text-[9px] font-semibold uppercase tracking-[0.22em] text-slate-400">Personal finance</span>
                    </span>
                </a>
                <button class="btn-icon lg:hidden" @click="sidebarOpen = false" aria-label="Close menu">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="mx-5 mb-5 flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/[0.07] px-3 py-2">
                <span class="pulse-dot h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">Payday cycle · 15th &amp; month-end</span>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3" aria-label="Main">
                @foreach ($nav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class(['group nav-link', 'nav-link-active' => request()->routeIs($item['route'])])
                    >
                        <x-icon :name="$item['icon']" class="h-[18px] w-[18px] transition-transform duration-300 group-hover:scale-110" />
                        <span>{{ $item['label'] }}</span>
                        @if ($item['route'] === 'savings')
                            <span class="ml-auto rounded-full bg-emerald-400/15 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-300">goal</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="px-3 pb-3">
                <a
                    href="{{ route('settings') }}"
                    wire:navigate
                    @class(['group nav-link', 'nav-link-active' => request()->routeIs('settings')])
                >
                    <x-icon name="settings" class="h-[18px] w-[18px] transition-transform duration-300 group-hover:scale-110" />
                    <span>Settings</span>
                </a>
            </div>

            <div class="border-t border-slate-200/70 p-3 dark:border-white/[0.06]">
                <div class="flex items-center gap-3 rounded-xl px-2 py-2">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-slate-200 to-slate-100 text-xs font-bold text-slate-700 dark:from-white/15 dark:to-white/5 dark:text-white">
                        {{ strtoupper(substr(auth()->user()->name ?? 'B', 0, 2)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->name ?? 'Budget Owner' }}</p>
                        <p class="truncate text-[10px] text-slate-400">{{ auth()->user()->email ?? 'local' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-icon h-8 w-8" title="Log out" aria-label="Log out">
                            <x-icon name="logout" class="h-4 w-4" />
                        </button>
                    </form>
                </div>
                <p class="flex items-center gap-1.5 px-2 pt-1 text-[10px] text-slate-400">
                    <x-icon name="lock" class="h-3 w-3" /> Private, local workspace
                </p>
            </div>
        </aside>

        <div class="lg:pl-[264px]">
            <header class="sticky top-0 z-30 border-b border-slate-200/60 bg-white/60 backdrop-blur-xl dark:border-white/[0.06] dark:bg-[#05080f]/60">
                <div class="mx-auto flex h-16 max-w-[1440px] items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button class="btn-icon lg:hidden" @click="sidebarOpen = true" aria-label="Open menu">
                        <x-icon name="menu" class="h-4 w-4" />
                    </button>

                    <div class="hidden items-center gap-2 sm:flex">
                        <span class="text-xs font-medium text-slate-400">{{ now()->format('D, M j, Y') }}</span>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <span class="hidden items-center gap-1.5 rounded-full border border-slate-200/70 bg-white/50 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400 sm:inline-flex">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Local only
                        </span>

                        <button
                            type="button"
                            class="btn-icon"
                            onclick="toggleTheme()"
                            title="Toggle light or dark theme"
                            aria-label="Toggle theme"
                        >
                            <x-icon name="sun" class="hidden h-4 w-4 dark:block" />
                            <x-icon name="moon" class="h-4 w-4 dark:hidden" />
                        </button>

                        <div class="relative">
                            <button class="flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/50 py-1.5 pl-1.5 pr-2.5 transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10" @click="userMenu = !userMenu">
                                <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-emerald-300 to-cyan-400 text-[10px] font-extrabold text-slate-950">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'B', 0, 2)) }}
                                </span>
                                <x-icon name="chevron-right" class="h-3.5 w-3.5 rotate-90 text-slate-400" />
                            </button>
                            <div
                                x-cloak
                                x-show="userMenu"
                                x-transition.origin.top.right
                                @click.outside="userMenu = false"
                                class="absolute right-0 top-12 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white/95 p-1 shadow-2xl backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/95"
                            >
                                <p class="px-3 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Signed in</p>
                                <p class="truncate px-3 pb-2 text-xs text-slate-600 dark:text-slate-300">{{ auth()->user()->email }}</p>
                                <a href="{{ route('settings') }}" wire:navigate class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">
                                    <x-icon name="settings" class="h-3.5 w-3.5" /> Settings
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-rose-500 hover:bg-rose-500/10">
                                        <x-icon name="logout" class="h-3.5 w-3.5" /> Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <livewire:expense-quick-add />

    <div id="toast-host" class="fixed bottom-4 right-4 z-[100] flex flex-col items-end gap-2" aria-live="polite"></div>

    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', () => window.toast(@json(session('status'))));
        </script>
    @endif

    @livewireScripts
</body>
</html>
