<x-app-layout>
    <x-page-header title="Profil saya" subtitle="Kelola data akun dan keamanan kata sandi." />

    <div class="space-y-4">
        <x-card>
            <div class="max-w-xl">@include('profile.partials.update-profile-information-form')</div>
        </x-card>

        <x-card>
            <div class="max-w-xl">@include('profile.partials.update-password-form')</div>
        </x-card>

        <x-card>
            <div class="max-w-xl">@include('profile.partials.delete-user-form')</div>
        </x-card>
    </div>
</x-app-layout>
