@php
$role = auth()->user()->role;
$navGroups = \App\Support\Navigation::groups($role);
$families = \App\Support\Navigation::families($navGroups);
$initials = collect(explode(' ', auth()->user()->name))
    ->filter(fn ($p) => $p && str_starts_with($p, strtoupper(substr($p, 0, 1))))
    ->map(fn ($p) => mb_substr(preg_replace('/^(Dr\.|Prof\.|Mr\.|Ms\.)\s*/u', '', $p), 0, 1))
    ->take(2)
    ->implode('');
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-100 flex flex-col no-print">
    <div class="h-16 flex items-center gap-3 px-5 border-b border-gray-100">
        <img src="{{ asset('ceso_logobig.png') }}" alt="SmartCEMES" class="w-9 h-9 rounded-xl object-contain shadow-sm shrink-0">
        <div class="leading-tight">
            <p class="font-extrabold text-charcoal text-[15px] tracking-tight">Smart<span class="text-gold-600">CEMES</span></p>
            <p class="text-[10.5px] text-gray-400 font-medium">Leyte Normal University</p>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-4">
        @foreach ($navGroups as $group)
            <div class="mb-5">
                <p class="px-3 mb-2 text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400">{{ $group['section'] }}</p>
                <nav class="space-y-1">
                    @foreach ($group['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           @class(['sc-nav-link', 'active' => \App\Support\Navigation::isActive($item, $families)])>
                            <x-sc.icon :name="$item['icon']" class="w-5 h-5" />
                            <span>{{ $item['label'] }}</span>
                            @if (isset($item['badge']) && $item['badge'])
                                <span class="sc-nav-badge">{{ $item['badge'] }}</span>
                            @endif
                            @if (! empty($item['subs']))
                                {{-- drill-down signal: this entry opens a whole chain --}}
                                <x-sc.icon name="chevron-right" class="w-3.5 h-3.5 nav-chevron" />
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        @endforeach
    </div>

    <div class="border-t border-gray-100 p-3">
        <div class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-gray-50 transition">
            <span class="avatar w-9 h-9 text-xs">{{ $initials }}</span>
            <div class="min-w-0">
                <p class="text-[13px] font-semibold text-charcoal truncate">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-gray-400 truncate">{{ ucfirst($role) }}</p>
            </div>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               title="Sign out"
               class="ml-auto p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                <x-sc.icon name="logout" class="w-5 h-5" />
            </a>
        </div>
    </div>
</aside>

<form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
    @csrf
</form>