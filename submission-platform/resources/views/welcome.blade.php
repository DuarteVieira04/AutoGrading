@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    <div class="max-w-xl space-y-4">
        <h1 class="text-xl font-semibold text-gray-900">{{ config('app.name') }}</h1>
        <p class="text-sm text-gray-600">
            {{ __('Submeta projetos do curso como ficheiros ZIP e acompanhe o seu estado.') }}
        </p>
        @auth
            <p class="text-sm">
                <a href="{{ route('submissions.index') }}" class="text-gray-900 underline">{{ __('Abrir submissões de projetos') }}</a>
            </p>
        @else
            <p class="text-sm text-gray-600">
                <a href="{{ route('login') }}" class="text-gray-900 underline">{{ __('Iniciar sessão') }}</a>
                @if (Route::has('register'))
                    <span class="text-gray-500"> · </span>
                    <a href="{{ route('register') }}" class="text-gray-900 underline">{{ __('Registar') }}</a>
                @endif
            </p>
        @endauth
    </div>
@endsection
