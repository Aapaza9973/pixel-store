@extends('layouts.app')

@section('title', 'Mi perfil')
@section('subtitle', 'Actualiza tu información personal y tu seguridad')

@section('content')
<div class="max-w-3xl space-y-6">
    <x-card>
        @include('profile.partials.update-profile-information-form')
    </x-card>

    <x-card>
        @include('profile.partials.update-password-form')
    </x-card>

    <x-card>
        @include('profile.partials.delete-user-form')
    </x-card>
</div>
@endsection
