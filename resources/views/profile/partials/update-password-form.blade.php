<section class="profile-card" aria-labelledby="update-password-title">
    <header class="profile-card-head">
        <span class="profile-card-icon profile-card-icon--gold"><x-sc.icon name="shield" class="h-5 w-5" /></span>
        <div>
            <p class="profile-card-kicker">Account security</p>
            <h2 id="update-password-title">Change password</h2>
            <p>Use a strong password to keep your account secure.</p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="profile-form">
        @csrf
        @method('put')

        <div class="profile-field">
            <label class="profile-label" for="update_password_current_password">Current password <span class="profile-required">*</span></label>
            <div x-data="{ show: false }" class="profile-password-wrap">
                <input id="update_password_current_password" name="current_password" x-bind:type="show ? 'text' : 'password'" class="profile-input profile-input--action" autocomplete="current-password" />
                <button type="button" class="profile-input-action" @click="show = !show" :aria-label="show ? 'Hide current password' : 'Show current password'"><x-sc.icon name="eye" class="h-4 w-4" /></button>
            </div>
            <x-input-error class="profile-error" :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="profile-field">
            <label class="profile-label" for="update_password_password">New password <span class="profile-required">*</span></label>
            <div x-data="{ show: false }" class="profile-password-wrap">
                <input id="update_password_password" name="password" x-bind:type="show ? 'text' : 'password'" class="profile-input profile-input--action" autocomplete="new-password" aria-describedby="password-help" />
                <button type="button" class="profile-input-action" @click="show = !show" :aria-label="show ? 'Hide new password' : 'Show new password'"><x-sc.icon name="eye" class="h-4 w-4" /></button>
            </div>
            <p id="password-help" class="profile-help">Use at least 8 characters with a mix of letters, numbers, and symbols.</p>
            <x-input-error class="profile-error" :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="profile-field">
            <label class="profile-label" for="update_password_password_confirmation">Confirm new password <span class="profile-required">*</span></label>
            <div x-data="{ show: false }" class="profile-password-wrap">
                <input id="update_password_password_confirmation" name="password_confirmation" x-bind:type="show ? 'text' : 'password'" class="profile-input profile-input--action" autocomplete="new-password" aria-describedby="confirmation-help" />
                <button type="button" class="profile-input-action" @click="show = !show" :aria-label="show ? 'Hide password confirmation' : 'Show password confirmation'"><x-sc.icon name="eye" class="h-4 w-4" /></button>
            </div>
            <p id="confirmation-help" class="profile-help">Re-enter the new password to confirm it.</p>
            <x-input-error class="profile-error" :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="profile-form-actions">
            <button type="submit" class="btn btn-primary profile-submit"><x-sc.icon name="shield" class="h-4 w-4" />{{ __('Update password') }}</button>
            @if (session('status') === 'password-updated')
                <p class="profile-save-state" role="status"><x-sc.icon name="check" class="h-3.5 w-3.5" />{{ __('Password updated') }}</p>
            @endif
        </div>
    </form>
</section>
