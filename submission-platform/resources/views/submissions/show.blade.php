@extends('layouts.app')

@section('title', __('Submission details'))

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4 border-b border-gray-300 pb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Submission details') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Full grading report for this submission.') }}</p>
            </div>
            <div class="shrink-0">
                <a href="{{ route('submissions.index') }}" class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">
                    {{ __('Back to submissions') }}
                </a>
            </div>
        </div>

        @php
            $feedback = $submission->feedback ?? [];
            $results = data_get($feedback, 'results', []);
            $summary = data_get($results, 'summary', []);
            $tests = data_get($results, 'tests', []);
            $error = data_get($feedback, 'error');
            $hasSummary = ! empty($summary['total_tests']) || isset($summary['successful']);
            $successRate = isset($summary['success_rate']) ? round((float) $summary['success_rate'], 2) : null;
            $passed = isset($summary['successful'], $summary['total_tests']) && $summary['successful'] === $summary['total_tests'] && $submission->status === 'graded';
        @endphp

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Submitted by') }}</p>
                    <p class="text-sm text-gray-900">{{ $submission->student->user->name ?? __('Anonymous') }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Submitted at') }}</p>
                    <p class="text-sm text-gray-900">{{ $submission->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('File') }}</p>
                    <p class="text-sm text-gray-900">{{ basename($submission->file_path) }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Grading process') }}</p>
                    <p class="text-sm text-gray-900">{{ $submission->gradingProcess->name ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Status') }}</p>
                    <p class="text-sm text-gray-900">{{ ucfirst($submission->status) }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Grade') }}</p>
                    <p class="text-sm text-gray-900">
                        @if ($submission->grade !== null)
                            {{ number_format((float) $submission->grade, 1) }}%
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>

            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Grading summary') }}</p>

                    @if ($error)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <p class="font-semibold">{{ __('Error during grading') }}</p>
                            <p class="mt-1">{{ $error }}</p>
                            @if (! empty($feedback['detail']))
                                <p class="mt-2 text-xs text-red-600">{{ $feedback['detail'] }}</p>
                            @endif
                        </div>
                    @elseif ($hasSummary)
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Total tests') }}</p>
                                <p>{{ $summary['total_tests'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Passed') }}</p>
                                <p>{{ $summary['successful'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Failed') }}</p>
                                <p>{{ $summary['failed'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Success rate') }}</p>
                                <p>{{ $successRate !== null ? $successRate . '%' : '—' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $passed ? __('Passed') : __('Failed') }}
                            </span>
                            @if (! empty($summary['duration']))
                                <span class="text-sm text-gray-600">{{ __('Duration') }}: {{ round((float) $summary['duration'], 2) }}s</span>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-600">{{ __('No structured grading information is available for this submission.') }}</p>
                        @if (! empty($feedback['output']))
                            <p class="mt-3 text-sm text-gray-700 font-semibold">{{ __('Raw output summary') }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($feedback['output'], 300) }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        @if ($hasSummary && is_array($tests) && count($tests) > 0)
            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Test results') }}</h2>
                    <span class="text-sm text-gray-500">{{ __('Detailed test status') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('Test') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Message') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($tests as $test)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $test['name'] ?? __('Unnamed test') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ ($test['status'] ?? '') === 'passed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($test['status'] ?? __('unknown')) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $test['message'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
