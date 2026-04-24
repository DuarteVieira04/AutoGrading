<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        @include('partials.main-content-headings')
        @include('partials.main-content-alerts')
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
