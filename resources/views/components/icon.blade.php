@props(['name' => 'dot', 'class' => 'h-5 w-5'])
<svg
    {{ $attributes->merge(['class' => $class.' shrink-0']) }}
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.8"
    stroke="currentColor"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="9" rx="1.6" />
            <rect x="14" y="3" width="7" height="5" rx="1.6" />
            <rect x="14" y="12" width="7" height="9" rx="1.6" />
            <rect x="3" y="16" width="7" height="5" rx="1.6" />
            @break

        @case('expenses')
            <path d="M6 2.5h12a1 1 0 0 1 1 1v18l-2.6-1.6-2.4 1.6-2.4-1.6L9.2 21.5 6 19.9V3.5a1 1 0 0 1 1-1Z" />
            <path d="M9.5 8h5M9.5 12h5M9.5 16h3" />
            @break

        @case('paydays')
            <circle cx="8" cy="8" r="5" />
            <path d="M18.1 10.4a6 6 0 1 1-7.7 7.7" />
            <path d="M6.8 5.8h.2v4.4M15.5 12.6l.8.9M18.6 16.6l-.1.1" />
            @break

        @case('bills')
            <path d="M14 2.5H6a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-11Z" />
            <path d="M14 2.5v6h6" />
            <path d="M8 13h8M8 17h5" />
            @break

        @case('budgets')
            <path d="M21.2 15.9A10 10 0 1 1 8.1 2.8" />
            <path d="M21 12a9 9 0 0 0-9-9v9h9Z" />
            @break

        @case('savings')
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="12" r="4.5" />
            <path d="M12 3v3M21 12h-3M12 21v-3M3 12h3" />
            @break

        @case('accounts')
            <path d="M20 7H5a2 2 0 0 1 0-4h14a1 1 0 0 1 1 1v3" />
            <path d="M3 5v14a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-3" />
            <path d="M21 9v5a1 1 0 0 1-1 1h-4.5a3.5 3.5 0 0 1 0-7H20a1 1 0 0 1 1 1Z" />
            <circle cx="16.5" cy="11.5" r=".6" fill="currentColor" stroke="none" />
            @break

        @case('reports')
            <path d="M3 3v18h18" />
            <path d="M7 16v-5M12 16V7M17 16v-9" />
            @break

        @case('settings')
            <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3" />
            <path d="M1.5 14h5M9.5 8h5M17.5 16h5" />
            @break

        @case('logout')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="m16 17 5-5-5-5M21 12H9" />
            @break

        @case('sun')
            <circle cx="12" cy="12" r="4.2" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
            @break

        @case('moon')
            <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z" />
            @break

        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break

        @case('x')
            <path d="M18 6 6 18M6 6l12 12" />
            @break

        @case('check')
            <path d="m4 12 5 5L20 6" />
            @break

        @case('trash')
            <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />
            @break

        @case('pencil')
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
            @break

        @case('arrow-up')
            <path d="M12 19V5M5 12l7-7 7 7" />
            @break

        @case('arrow-down')
            <path d="M12 5v14M19 12l-7 7-7-7" />
            @break

        @case('arrow-right')
            <path d="M5 12h14M12 5l7 7-7 7" />
            @break

        @case('chevron-left')
            <path d="m15 18-6-6 6-6" />
            @break

        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
            @break

        @case('sparkles')
            <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6Z" />
            <path d="M5 16.5 5.8 18.5 8 19.5l-2.2 1L5 22.5l-1-2-2-1 2-1Z" />
            <path d="M19 15.5l.6 1.6 1.4.7-1.4.6L19 20l-.6-1.6-1.4-.6 1.4-.7Z" />
            @break

        @case('target')
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="12" r="5" />
            <circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none" />
            @break

        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            @break

        @case('bank')
            <path d="m3 10 9-6 9 6" />
            <path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 21h18" />
            @break

        @case('download')
            <path d="M12 3v12M7 10l5 5 5-5M5 21h14" />
            @break

        @case('alert')
            <path d="M12 9v4M12 17h.01" />
            <path d="M10.3 3.7 2.4 17.4A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3.1L13.7 3.7a2 2 0 0 0-3.4 0Z" />
            @break

        @case('lock')
            <rect x="4" y="10" width="16" height="11" rx="2" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break

        @case('trend-up')
            <path d="m3 17 6-6 4 4 8-8" />
            <path d="M15 7h6v6" />
            @break

        @case('wallet')
            <path d="M20 7H5a2 2 0 0 1 0-4h14a1 1 0 0 1 1 1v3" />
            <path d="M3 5v14a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-3" />
            <path d="M21 9v5a1 1 0 0 1-1 1h-4.5a3.5 3.5 0 0 1 0-7H20a1 1 0 0 1 1 1Z" />
            @break

        @default
            <circle cx="12" cy="12" r="3" />
    @endswitch
</svg>
