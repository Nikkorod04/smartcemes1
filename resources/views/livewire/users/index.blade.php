<div class="users-page">
    <section class="mt-6 grid grid-cols-4 gap-4">
        <x-sc.color-card tone="blue" class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-blue-100">Total accounts</p><p class="mt-3 text-3xl font-black tracking-tight text-white">{{ $summary['total'] }}</p></div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white"><x-sc.icon name="people" class="h-5 w-5" /></span>
            </div>
            <p class="mt-3 text-[11px] font-semibold text-blue-100">All registered sign-in accounts</p>
        </x-sc.color-card>
        <x-sc.color-card tone="teal" class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-teal-100">Faculty accounts</p><p class="mt-3 text-3xl font-black tracking-tight text-white">{{ $summary['faculty'] }}</p></div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white"><x-sc.icon name="users" class="h-5 w-5" /></span>
            </div>
            <p class="mt-3 text-[11px] font-semibold text-teal-100">Linked to faculty profiles</p>
        </x-sc.color-card>
        <x-sc.color-card tone="indigo" class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-indigo-100">Secretaries</p><p class="mt-3 text-3xl font-black tracking-tight text-white">{{ $summary['secretaries'] }}</p></div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white"><x-sc.icon name="shield" class="h-5 w-5" /></span>
            </div>
            <p class="mt-3 text-[11px] font-semibold text-indigo-100">Operational access accounts</p>
        </x-sc.color-card>
        <x-sc.color-card tone="gold" class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-amber-100">Inactive</p><p class="mt-3 text-3xl font-black tracking-tight text-white">{{ $summary['inactive'] }}</p></div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white"><x-sc.icon name="alert" class="h-5 w-5" /></span>
            </div>
            <p class="mt-3 text-[11px] font-semibold text-amber-100">Blocked from signing in</p>
        </x-sc.color-card>
    </section>

    <section class="mt-6">
        <div class="sc-card p-4">
            <div class="flex items-center gap-3">
                <label class="sc-search relative min-w-[360px] flex-[2]">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="h-4 w-4" /></span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="input !pr-9" placeholder="Search name or email…" aria-label="Search users">
                    @if ($search)
                        <button type="button" wire:click="$set('search', '')" class="absolute right-2 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Clear search"><x-sc.icon name="x" class="h-3.5 w-3.5" /></button>
                    @endif
                </label>
                <select wire:model.live="roleFilter" class="input !w-[150px] !py-2.5 text-[12px]">
                    <option value="">All roles</option>
                    <option value="faculty">Faculty</option>
                    <option value="secretary">Secretary</option>
                    <option value="admin">Administrator</option>
                </select>
                <select wire:model.live="statusFilter" class="input !w-[145px] !py-2.5 text-[12px]">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button type="button" wire:click="create" class="btn btn-primary ml-auto shrink-0"><x-sc.icon name="plus" class="h-4 w-4" /> Add user</button>
            </div>
            @if ($search || $roleFilter || $statusFilter)
                <div class="mt-3 flex items-center gap-2 text-[11px] font-semibold text-gray-400">
                    <span>Filters active</span><button type="button" wire:click="clearFilters" class="font-extrabold text-lnu-700 hover:underline">Clear all</button>
                </div>
            @endif
        </div>
    </section>

    <section class="mt-4">
        <div class="sc-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div><h2 class="text-[15px] font-black tracking-tight text-charcoal">Account directory</h2><p class="mt-0.5 text-[11px] font-medium text-gray-400">Only active accounts can sign in.</p></div>
                <span class="badge badge-gray">{{ $users->total() }} matching</span>
            </div>
            @if ($users->isEmpty())
                <div class="px-5 py-16 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-lnu-50 text-lnu-700"><x-sc.icon name="people" class="h-6 w-6" /></span><p class="mt-4 text-[14px] font-extrabold text-charcoal">No user accounts found</p><p class="mt-1 text-[12px] font-medium text-gray-400">Try changing the search or filters.</p></div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($users as $user)
                        @php
                            $roleLabel = $user->isAdmin() ? 'Administrator' : ($user->isFaculty() ? 'Faculty' : 'Secretary');
                            $roleTone = $user->isAdmin() ? 'blue' : ($user->isFaculty() ? 'green' : 'gray');
                        @endphp
                        <div class="group flex items-center gap-4 px-5 py-4 transition hover:bg-lnu-50/40" wire:key="user-{{ $user->id }}">
                            <span class="avatar h-11 w-11 shrink-0 text-[12px]">{{ collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') }}</span>
                            <div class="min-w-0 w-[250px]">
                                <p class="truncate text-[13px] font-extrabold text-charcoal">{{ $user->name }}</p>
                                <p class="mt-0.5 truncate text-[11px] font-medium text-gray-500">{{ $user->email }}</p>
                            </div>
                            <div class="w-[125px]"><span class="badge badge-{{ $roleTone }}">{{ $roleLabel }}</span>@if ($user->isFaculty())<p class="mt-1 text-[10px] font-semibold text-gray-400">{{ $user->faculty?->employee_id ?? 'Profile incomplete' }}</p>@endif</div>
                            <div class="w-[130px]"><span class="badge badge-{{ $user->is_active ? 'green' : 'gray' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>@if ($user->must_change_password)<p class="mt-1 text-[10px] font-semibold text-amber-700">Temporary password</p>@else<p class="mt-1 text-[10px] font-semibold text-gray-400">Password set</p>@endif</div>
                            <div class="ml-auto w-[105px] text-right"><p class="text-[11px] font-semibold text-gray-500">{{ $user->created_at?->format('M j, Y') }}</p><p class="mt-1 text-[10px] font-medium text-gray-400">Created</p></div>
                            <div class="flex shrink-0 items-center gap-1 opacity-70 transition group-hover:opacity-100">
                                <button type="button" wire:click="edit({{ $user->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-lnu-100 hover:text-lnu-800" title="Edit account" aria-label="Edit {{ $user->name }}"><x-sc.icon name="edit" class="h-4 w-4" /></button>
                                <button type="button" wire:click="prepareReset({{ $user->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-amber-100 hover:text-amber-800" title="Reset password" aria-label="Reset password for {{ $user->name }}"><x-sc.icon name="shield" class="h-4 w-4" /></button>
                                @if ($user->id !== auth()->id())
                                    <button type="button" wire:click="prepareStatusChange({{ $user->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-charcoal" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }} account" aria-label="{{ $user->is_active ? 'Deactivate' : 'Activate' }} {{ $user->name }}"><x-sc.icon name="{{ $user->is_active ? 'logout' : 'check' }}" class="h-4 w-4" /></button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($users->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">{{ $users->onEachSide(1)->links() }}</div>
                @endif
            @endif
        </div>
    </section>

    @if ($showForm)
        <div x-data @keydown.escape.window="$wire.closeForm()" class="fixed inset-0 z-[60] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true" aria-labelledby="user-form-title">
            <div class="fixed inset-0 bg-charcoal/55 backdrop-blur-[3px]" wire:click="closeForm"></div>
            <form wire:submit.prevent="save" class="relative z-10 flex max-h-[min(720px,calc(100vh-2rem))] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <div class="shrink-0 border-b border-lnu-100 bg-gradient-to-br from-lnu-800 to-lnu-600 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20"><x-sc.icon name="people" class="h-5 w-5" /></span><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/70">Account management</p><h2 id="user-form-title" class="mt-1 text-[19px] font-black tracking-tight">{{ $editingId ? 'Edit user account' : 'Add new user' }}</h2></div></div><button type="button" wire:click="closeForm" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/15 hover:text-white" aria-label="Close user form"><x-sc.icon name="x" class="h-4 w-4" /></button></div>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900"><x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" /><p><span class="font-extrabold">Access note.</span> New accounts receive a secure temporary password and must choose a personal password after their first sign-in.</p></div>
                    <div class="space-y-5">
                        <section><div class="mb-3 flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span><div><h3 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Identity</h3><p class="text-[11px] font-medium text-gray-400">The account name and sign-in address.</p></div></div><div class="grid gap-3 sm:grid-cols-2"><div><label class="label" for="managed-user-name">Full name <span class="text-red-500">*</span></label><input id="managed-user-name" type="text" wire:model="form.name" class="input" autocomplete="name" required>@error('form.name')<p class="mt-1 text-[11px] font-semibold text-red-600">{{ $message }}</p>@enderror</div><div><label class="label" for="managed-user-email">Email address <span class="text-red-500">*</span></label><input id="managed-user-email" type="email" wire:model="form.email" class="input" autocomplete="email" required>@error('form.email')<p class="mt-1 text-[11px] font-semibold text-red-600">{{ $message }}</p>@enderror</div></div></section>
                        <section><div class="mb-3 flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span><div><h3 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Access role</h3><p class="text-[11px] font-medium text-gray-400">Choose the work area this account can enter.</p></div></div>@if ($editingId)<div class="rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3"><p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Assigned role</p><p class="mt-1 text-[13px] font-extrabold text-charcoal">{{ $form['role'] === 'faculty' ? 'Faculty' : ($form['role'] === 'secretary' ? 'Secretary' : 'Administrator') }}</p><p class="mt-1 text-[10.5px] font-medium text-gray-400">Role changes are managed separately to protect linked records.</p></div>@else<div class="grid gap-3 sm:grid-cols-2"><label class="cursor-pointer rounded-xl border p-4 transition has-[:checked]:border-lnu-500 has-[:checked]:bg-lnu-50"><input type="radio" wire:model="form.role" value="secretary" class="sr-only"><span class="flex items-center gap-2 text-[13px] font-extrabold text-charcoal"><x-sc.icon name="shield" class="h-4 w-4 text-lnu-700" /> Secretary</span><span class="mt-1 block text-[11px] font-medium leading-relaxed text-gray-500">Operational workflows, validation, and records support.</span></label><label class="cursor-pointer rounded-xl border p-4 transition has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50"><input type="radio" wire:model="form.role" value="faculty" class="sr-only"><span class="flex items-center gap-2 text-[13px] font-extrabold text-charcoal"><x-sc.icon name="users" class="h-4 w-4 text-teal-700" /> Faculty</span><span class="mt-1 block text-[11px] font-medium leading-relaxed text-gray-500">Faculty workspace with an auto-created profile.</span></label></div>@endif @error('form.role')<p class="mt-1 text-[11px] font-semibold text-red-600">{{ $message }}</p>@enderror</section>
                    </div>
                </div>
                <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end gap-2"><button type="button" wire:click="closeForm" class="btn btn-ghost">Cancel</button><button type="submit" class="btn btn-primary min-w-[128px]" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Create account' }}</span><span wire:loading wire:target="save" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span></button></div></div>
            </form>
        </div>
    @endif

    @if ($showStatusConfirm)
        <div x-data @keydown.escape.window="$wire.cancelStatusChange()" class="fixed inset-0 z-[70] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true" aria-labelledby="user-status-title"><div class="fixed inset-0 bg-charcoal/55 backdrop-blur-[3px]" wire:click="cancelStatusChange"></div><div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-pop"><div class="border-b border-amber-100 bg-gradient-to-br from-amber-700 to-amber-600 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex items-start gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"><x-sc.icon name="alert" class="h-5 w-5" /></span><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/70">Account access</p><h2 id="user-status-title" class="mt-1 text-[18px] font-black">{{ ucfirst($statusTargetAction) }} account?</h2></div></div><button type="button" wire:click="cancelStatusChange" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/70 hover:bg-white/15 hover:text-white" aria-label="Close confirmation"><x-sc.icon name="x" class="h-4 w-4" /></button></div></div><div class="px-5 py-5 sm:px-6"><p class="text-[13px] leading-relaxed text-gray-600">{{ $statusTargetAction === 'deactivate' ? 'This will prevent ' : 'This will allow ' }}<span class="font-extrabold text-charcoal">{{ $statusTargetName }}</span>{{ $statusTargetAction === 'deactivate' ? ' from signing in. Their records will remain available.' : ' to sign in again.' }}</p></div><div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><button type="button" wire:click="cancelStatusChange" class="btn btn-ghost">Cancel</button><button type="button" wire:click="changeStatus" class="btn {{ $statusTargetAction === 'deactivate' ? '!bg-amber-700 hover:!bg-amber-800' : 'btn-primary' }} !text-white" wire:loading.attr="disabled" wire:target="changeStatus">{{ ucfirst($statusTargetAction) }} account</button></div></div></div>
    @endif

    @if ($showResetConfirm)
        <div x-data @keydown.escape.window="$wire.cancelReset()" class="fixed inset-0 z-[70] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true" aria-labelledby="user-reset-title"><div class="fixed inset-0 bg-charcoal/55 backdrop-blur-[3px]" wire:click="cancelReset"></div><div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-pop"><div class="border-b border-amber-100 bg-gradient-to-br from-amber-700 to-amber-600 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex items-start gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"><x-sc.icon name="shield" class="h-5 w-5" /></span><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/70">Account access</p><h2 id="user-reset-title" class="mt-1 text-[18px] font-black">Reset password?</h2></div></div><button type="button" wire:click="cancelReset" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/70 hover:bg-white/15 hover:text-white" aria-label="Close confirmation"><x-sc.icon name="x" class="h-4 w-4" /></button></div></div><div class="space-y-3 px-5 py-5 sm:px-6"><p class="text-[13px] leading-relaxed text-gray-600">A new temporary password will be generated for <span class="font-extrabold text-charcoal">{{ $resettingName }}</span> and the current password will stop working.</p><div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] leading-relaxed text-amber-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" /><p>The new password will be shown once. Share it securely.</p></div></div><div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><button type="button" wire:click="cancelReset" class="btn btn-ghost">Cancel</button><button type="button" wire:click="resetUserPassword" class="btn !bg-amber-700 !text-white hover:!bg-amber-800" wire:loading.attr="disabled" wire:target="resetUserPassword">Reset password</button></div></div></div>
    @endif

    @if ($showCredentials && $generatedPassword)
        <div x-data="{ copied: false, copyPassword() { navigator.clipboard?.writeText(@js($generatedPassword)); this.copied = true; setTimeout(() => this.copied = false, 1600); } }" @keydown.escape.window="$wire.closeCredentials()" class="fixed inset-0 z-[80] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true" aria-labelledby="user-credentials-title"><div class="fixed inset-0 bg-charcoal/55 backdrop-blur-[3px]" wire:click="closeCredentials"></div><div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-pop"><div class="border-b border-emerald-100 bg-gradient-to-br from-emerald-700 to-emerald-600 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex items-start gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"><x-sc.icon name="check" class="h-5 w-5" /></span><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/70">Credentials ready</p><h2 id="user-credentials-title" class="mt-1 text-[18px] font-black">{{ $credentialAction === 'reset' ? 'Password reset complete' : 'User account created' }}</h2></div></div><button type="button" wire:click="closeCredentials" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/70 hover:bg-white/15 hover:text-white" aria-label="Close credentials"><x-sc.icon name="x" class="h-4 w-4" /></button></div></div><div class="space-y-4 px-5 py-5 sm:px-6"><div><p class="text-[13px] font-extrabold text-charcoal">{{ $generatedAccountName }}</p><p class="mt-0.5 text-[12px] font-medium text-gray-500">{{ $generatedAccountEmail }}</p></div><div class="rounded-xl border border-lnu-100 bg-lnu-50/70 p-3.5"><div class="flex items-center justify-between gap-3"><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-lnu-700">Temporary password</p><p class="mt-1 break-all font-mono text-[15px] font-extrabold tracking-wide text-lnu-900">{{ $generatedPassword }}</p></div><button type="button" @click="copyPassword" class="btn btn-outline shrink-0 !px-3 !py-2 !text-[11px]" x-text="copied ? 'Copied' : 'Copy'"></button></div></div><div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] leading-relaxed text-amber-900"><x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" /><p><span class="font-extrabold">Share securely.</span> This password is temporary and the user must create a personal password after signing in.</p></div></div><div class="flex justify-end border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><button type="button" wire:click="closeCredentials" class="btn btn-primary">Done</button></div></div></div>
    @endif
</div>
