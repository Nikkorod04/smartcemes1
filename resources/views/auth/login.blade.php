<x-guest-layout>
    @if (session('status'))
        <div class="mb-5 flex items-start gap-2.5 p-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-[12.5px] font-semibold leading-snug">{{ session('status') }}</p>
        </div>
    @endif

    <div class="text-center mb-6">
        <h2 class="font-extrabold text-[16.5px] tracking-tight">Sign in to your account</h2>
        </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input id="email" class="input mt-1.5 @error('email') !border-red-300 focus:!border-red-500 @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@lnu.edu.ph">
            @error('email')
                <p class="text-[11.5px] text-red-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <div class="flex items-center justify-between">
                <label for="password" class="label">{{ __('Password') }}</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-lnu-800 hover:text-lnu-600 transition mb-1">Forgot password?</a>
                @endif
            </div>
            <div class="relative mt-1.5">
                <input id="password" x-ref="pw"
                       :type="show ? 'text' : 'password'"
                       class="input !pr-11 @error('password') !border-red-300 focus:!border-red-500 @enderror"
                       type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                <button type="button" @click="show = !show" class="absolute right-1.5 top-1/2 -translate-y-1/2 p-2 rounded-lg text-gray-400 hover:text-lnu-700 hover:bg-lnu-50 transition">
                    <span x-show="!show" class="w-[18px] h-[18px] inline-flex [&>svg]:w-full [&>svg]:h-full">
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <span x-show="show" x-cloak class="w-[18px] h-[18px] inline-flex [&>svg]:w-full [&>svg]:h-full">
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </span>
                </button>
            </div>
            @error('password')
                <p class="text-[11.5px] text-red-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="flex items-center gap-2 select-none cursor-pointer">
            <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 accent-lnu-800" name="remember">
            <span class="text-[12.5px] text-gray-500 font-semibold">{{ __('Remember me') }}</span>
        </label>

        <button type="submit" class="btn btn-primary w-full !py-2.5 !text-[13.5px] mt-1">
            Log in
        </button>
    </form>

</x-guest-layout>
