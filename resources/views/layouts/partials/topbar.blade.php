@php
$role = auth()->user()->role;
/* Shared with the sidebar so the highlight and the page title cannot drift.
   A sub-page of a collapsed hub resolves to the HUB's label, so a project hub
   still reads "Manage Extension Programs". */
$pageLabel = \App\Support\Navigation::pageLabel($role);
$initials = collect(explode(' ', auth()->user()->name))
    ->map(fn ($p) => mb_substr(preg_replace('/^(Dr\.|Prof\.|Mr\.|Ms\.)\s*/u', '', $p), 0, 1))
    ->take(2)
    ->implode('');
$notifications = auth()->user()->notifications()->take(8)->get();
$unreadCount = auth()->user()->unreadNotifications->count();
@endphp

<header class="sticky top-0 z-30 h-16 bg-white/90 backdrop-blur border-b border-gray-100 flex items-center pl-72 pr-6 gap-4 no-print">
    <div>
        <h1 class="text-[17px] font-extrabold text-charcoal tracking-tight leading-none">{{ $pageLabel }}</h1>
        <p class="text-[11px] text-gray-400 mt-1 font-medium">Community Extension Services Office · LNU</p>
    </div>
    <div class="ml-auto flex items-center gap-2">
        <div x-data="{ open: false }" class="relative">
            <button @click="open=!open" class="relative p-2.5 rounded-xl hover:bg-gray-100 transition text-gray-500">
                <x-sc.icon name="bell" class="w-5 h-5" />
                @if ($unreadCount)
                    <span class="bell-dot"></span>
                @endif
            </button>
            <div x-cloak x-show="open" @click.outside="open=false" x-transition.opacity.duration.150ms
                 class="absolute right-0 mt-2 w-96 sc-card p-0 overflow-hidden shadow-pop" style="display:none">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="font-bold text-[13.5px] text-charcoal">Notifications</p>
                    @if ($unreadCount)
                        <span class="badge badge-gold">{{ $unreadCount }} new</span>
                    @endif
                </div>
                <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                    @forelse (auth()->user()->notifications()->take(8)->get() as $n)
                        @php($d = $n->data)
                        <div class="flex gap-3 px-4 py-3 hover:bg-gray-50 transition {{ $n->unread() ? 'bg-blue-50/30' : '' }}">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ match ($d['color'] ?? 'blue') { 'green' => 'text-emerald-600 bg-emerald-50', 'yellow' => 'text-amber-600 bg-amber-50', 'red' => 'text-red-600 bg-red-50', 'gold' => 'text-gold-700 bg-gold-50', default => 'text-lnu-600 bg-lnu-50' } }}">
                                <x-sc.icon :name="$d['icon'] ?? 'bell'" class="w-[18px] h-[18px]" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold text-charcoal leading-snug">{{ $d['title'] }}</p>
                                <p class="text-[12px] text-gray-500 leading-snug mt-0.5">{{ $d['body'] }}</p>
                                <p class="text-[10.5px] text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                            </div>
                            @if ($n->unread())
                                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                    @csrf
                                    <button class="w-2 h-2 rounded-full bg-gold-500 mt-2 shrink-0 pulse-dot" title="Mark as read"></button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-[12.5px] text-gray-400">
                            No notifications yet.
                        </div>
                    @endforelse
                </div>
                @if ($unreadCount)
                    <form method="POST" action="{{ route('notifications.readAll') }}">
                        @csrf
                        <button class="w-full py-2.5 text-[12.5px] font-semibold text-lnu-800 hover:bg-lnu-50 transition">Mark all as read</button>
                    </form>
                @endif
            </div>
        </div>
        <div x-data="{ open: false }" class="relative">
            <button @click="open=!open" class="flex items-center gap-2.5 pl-1 pr-2 py-1 rounded-xl hover:bg-gray-100 transition">
                <span class="avatar w-8 h-8 text-[11px]">{{ $initials }}</span>
                <div class="hidden lg:block leading-tight text-left">
                    <p class="text-[12.5px] font-bold text-charcoal">{{ auth()->user()->name }}</p>
                    <p class="text-[10.5px] text-gray-400">{{ ucfirst($role) }}</p>
                </div>
            </button>
            <div x-cloak x-show="open" @click.outside="open=false" x-transition.opacity.duration.150ms
                 class="absolute right-0 mt-2 w-44 sc-card p-1.5 shadow-pop" style="display:none">
                <a href="{{ route('profile.edit') }}"
                   class="block px-3 py-2 rounded-lg text-[13px] font-medium text-gray-600 hover:bg-lnu-50 hover:text-lnu-800 transition">Profile</a>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="block px-3 py-2 rounded-lg text-[12.5px] font-medium text-red-500 hover:bg-red-50 transition">Sign out</a>
            </div>
        </div>
    </div>
</header>