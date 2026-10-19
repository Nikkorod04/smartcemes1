<x-guest-layout>
    <div class="text-center mb-6">
        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-lnu-50 text-lnu-800 ring-1 ring-lnu-100">
            <x-sc.icon name="shield" class="h-5 w-5" />
        </div>
        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-lnu-700">Account setup</p>
        <h2 class="mt-1 font-extrabold text-[18px] tracking-tight">Create your password</h2>
        <p class="mt-2 text-[12px] font-medium leading-relaxed text-gray-500">Your administrator gave you a temporary password. Choose a private password before you continue to SmartCEMES.</p>
    </div>

    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] leading-relaxed text-amber-900">
            <x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
        <p>The temporary password will stop working after you save this new password.</p>
    </div>

    <form method="POST" action="{{ route('password.first.update') }}" class="space-y-4">
        @csrf

        <div x-data="{ show: false }">
            <label for="current_password" class="label">Temporary password <span class="text-red-500">*</span></label>
            <div class="relative mt-1.5">
                <input id="current_password" name="current_password" x-bind:type="show ? 'text' : 'password'" class="input !pr-11 @error('current_password') !border-red-300 focus:!border-red-500 @enderror" required autocomplete="current-password" autofocus>
                <button type="button" @click="show = !show" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-400 transition hover:bg-lnu-50 hover:text-lnu-700" :aria-label="show ? 'Hide temporary password' : 'Show temporary password'"><x-sc.icon name="eye" class="h-4 w-4" /></button>
            </div>
            @error('current_password') <p class="mt-1.5 text-[11.5px] font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="label">New password <span class="text-red-500">*</span></label>
            <div class="relative mt-1.5">
                <input id="password" name="password" x-bind:type="show ? 'text' : 'password'" class="input !pr-11 @error('password') !border-red-300 focus:!border-red-500 @enderror" required autocomplete="new-password" aria-describedby="password-help">
                <button type="button" @click="show = !show" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-400 transition hover:bg-lnu-50 hover:text-lnu-700" :aria-label="show ? 'Hide new password' : 'Show new password'"><x-sc.icon name="eye" class="h-4 w-4" /></button>
            </div>
            <p id="password-help" class="mt-1.5 text-[10.5px] font-medium text-gray-400">Use a strong password that you do not share with anyone.</p>
            @error('password') <p class="mt-1.5 text-[11.5px] font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ show: false }">
            <label for="password_confirmation" class="label">Confirm new password <span class="text-red-500">*</span></label>
            <div class="relative mt-1.5">
                <input id="password_confirmation" name="password_confirmation" x-bind:type="show ? 'text' : 'password'" class="input !pr-11" required autocomplete="new-password">
                <button type="button" @click="show = !show" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-400 transition hover:bg-lnu-50 hover:text-lnu-700" :aria-label="show ? 'Hide password confirmation' : 'Show password confirmation'"><x-sc.icon name="eye" class="h-4 w-4" /></button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-1 w-full !py-2.5 !text-[13.5px]">Save password</button>
    </form>
</x-guest-layout>
