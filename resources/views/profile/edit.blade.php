@php
    $profileInitials = \App\View\Components\Initials::for($user->name);
    $profileRole = ucfirst($user->role ?? 'user');
@endphp

<x-app-layout>
    <div class="profile-page">
        <section class="profile-identity" aria-labelledby="profile-identity-title">
            <div class="profile-identity-glow"></div>
            <div class="profile-avatar">{{ $profileInitials }}</div>
            <div class="profile-identity-copy">
                <p class="profile-eyebrow profile-eyebrow--light">SmartCEMES account</p>
                <h1 id="profile-identity-title">{{ $user->name }}</h1>
                <p>{{ $user->email }}</p>
                <div class="profile-chip-row">
                    <span class="profile-chip profile-chip--role"><x-sc.icon name="shield" class="h-3.5 w-3.5" />{{ $profileRole }}</span>
                </div>
            </div>
            <div class="profile-identity-aside">
                <span class="profile-identity-aside-label">Community Extension Services Office</span>
                <span class="profile-identity-aside-mark">LNU</span>
            </div>
        </section>

        <div class="profile-settings-grid">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
