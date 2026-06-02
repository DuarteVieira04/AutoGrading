@extends('layouts.app')

@section('title', __('Detalhes da submissão'))

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4 border-b border-gray-300 pb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Detalhes da submissão') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Relatório completo de correção desta submissão.') }}</p>
            </div>
            <div class="shrink-0">
                @php
                    $backRoute = route('submissions.index');
                    if (auth()->check() && auth()->user()->hasRole('teacher') && $submission->process) {
                        $backRoute = route('processes.submissions', $submission->process);
                    }
                @endphp
                <a href="{{ $backRoute }}" class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">
                    {{ __('Voltar às submissões') }}
                </a>
            </div>
        </div>

        @if (! ($canViewFinalGrade ?? false))
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p>{{ __('Não tem acesso a estes resultados.') }}</p>
            </div>
        @elseif (! $canViewGradingDetails && ($hasSummary ?? false))
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                <p>{{ __('A classificação final está disponível. Os detalhes por teste só são mostrados para pastas com finalidade formativa e visibilidade adequada ao seu perfil.') }}</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            @include('submissions.show.partials.meta-panel')

            @include('submissions.show.partials.summary-panel')

            @if (($submission->status ?? '') === 'failed')
                @include('submissions.show.partials.pipeline-error-panel')
            @endif

            @if (($canViewFinalGrade ?? false) && ($hasSummary ?? false) && ($tests ?? collect())->isNotEmpty())
                @include('submissions.show.partials.tests-table')
            @endif

            @if (($canViewFinalGrade ?? false) && auth()->check() && auth()->user()->hasRole('teacher') && ($logsTests ?? collect())->isNotEmpty())
                @include('submissions.show.partials.logs-section')
            @endif
        </div>
    </div>
@endsection
