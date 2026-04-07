@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    <div class="max-w-xl space-y-4">
        <h1 class="text-xl font-semibold text-gray-900">{{ config('app.name') }}</h1>
        <p class="text-sm text-gray-600">
            {{ __('Submit course projects as ZIP archives and track their status.') }}
        </p>
        @auth
            <p class="text-sm">
                <a href="{{ route('submissions.index') }}" class="text-gray-900 underline">{{ __('Open project submissions') }}</a>
            </p>
        @else
            <p class="text-sm text-gray-600">
                <a href="{{ route('login') }}" class="text-gray-900 underline">{{ __('Log in') }}</a>
                @if (Route::has('register'))
                    <span class="text-gray-500"> · </span>
                    <a href="{{ route('register') }}" class="text-gray-900 underline">{{ __('Register') }}</a>
                @endif
            </p>
        @endauth
    </div>
@endsection
