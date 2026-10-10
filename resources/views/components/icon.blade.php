@props(['name' => 'document'])
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('home') <path d="m3 10 9-7 9 7v10H3z M9 20v-7h6v7"/> @break
        @case('folder') <path d="M3 7V4h6l3 3h9v13H3z"/> @break
        @case('download') <path d="M12 3v13m-5-5 5 5 5-5M4 17v4h16v-4"/> @break
        @case('upload') <path d="M12 16V3m-5 5 5-5 5 5M4 15v6h16v-6"/> @break
        @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/> @break
        @case('check') <path d="m7 12 3 3 7-7"/><rect x="3" y="3" width="18" height="18" rx="3"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('users') <circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v2"/> @break
        @case('settings') <path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3" fill="currentColor"/><circle cx="15" cy="17" r="3" fill="currentColor"/> @break
        @case('logout') <path d="M9 4H3v16h6m6-12 4 4-4 4M8 12h11"/> @break
        @case('login') <path d="M14 4h7v16h-7M3 12h12m-4-4 4 4-4 4"/> @break
        @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
        @case('shield') <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z m-4 9 3 3 5-6"/> @break
        @case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v.1"/> @break
        @case('eye') <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12"/><circle cx="12" cy="12" r="3"/> @break
        @case('building') <path d="m3 7 9-4 9 4M3 21h18M5 10v8m5-8v8m4-8v8m5-8v8M3 7h18"/> @break
        @default <path d="M6 3h8l4 4v14H6z M14 3v5h4M9 12h6m-6 4h6"/>
    @endswitch
</svg>
