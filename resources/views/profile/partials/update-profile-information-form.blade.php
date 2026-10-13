<section class="profile-card" aria-labelledby="profile-information-title">
    <header class="profile-card-head">
        <span class="profile-card-icon profile-card-icon--blue"><x-sc.icon name="users" class="h-5 w-5" /></span>
        <div>
            <p class="profile-card-kicker">Account details</p>
            <h2 id="profile-information-title">Profile information</h2>
            <p>Update the name and email used across SmartCEMES.</p>
        </div>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="profile-form">
        @csrf
        @method('patch')

        <div class="profile-field">
            <label class="profile-label" for="name">Name <span class="profile-required">*</span></label>
            <input id="name" name="name" type="text" class="profile-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" aria-describedby="name-help" />
            <p id="name-help" class="profile-help">Use the name that should appear in system records and notifications.</p>
            <x-input-error class="profile-error" :messages="$errors->get('name')" />
        </div>

        <div class="profile-field">
            <label class="profile-label" for="email">Email address <span class="profile-required">*</span></label>
            <input id="email" name="email" type="email" class="profile-input" value="{{ old('email', $user->email) }}" required autocomplete="username" aria-describedby="email-help" />
            <p id="email-help" class="profile-help">This address is used for account access and system notifications.</p>
            <x-input-error class="profile-error" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="profile-alert profile-alert--warning">
                    <x-sc.icon name="alert" class="h-4 w-4 shrink-0" />
                    <p>
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="profile-inline-action">{{ __('Resend verification email') }}</button>
                    </p>
                </div>
            @endif
        </div>

        <div class="profile-form-actions">
            <button type="submit" class="btn btn-primary profile-submit"><x-sc.icon name="check" class="h-4 w-4" />{{ __('Save changes') }}</button>
            @if (session('status') === 'profile-updated')
                <p class="profile-save-state" role="status"><x-sc.icon name="check" class="h-3.5 w-3.5" />{{ __('Profile saved') }}</p>
            @endif
        </div>
    </form>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="hidden">
        @csrf
    </form>
</section>
