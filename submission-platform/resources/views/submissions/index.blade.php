@extends('layouts.app')

@section('title', __('Submissões'))

@section('content')
    @php use Illuminate\Support\Str; @endphp
    <div class="space-y-10">
        <header class="border-b border-gray-300 pb-6">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Submissões') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-gray-600">
                {{ __('Envie um único ficheiro .zip para correção automática. O limite de tamanho total é cerca de 500 MB.') }}
            </p>
        </header>

        @if ($hasStudentProfile)
            <form method="post" action="{{ route('submissions.store') }}" enctype="multipart/form-data" class="mt-5 space-y-6">
                @csrf
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-800">{{ __('Enviar ficheiro ZIP') }}</label>
                    <input id="file" name="file" type="file" accept=".zip,application/zip"
                        class="mt-2 block w-full text-sm text-gray-800 file:mr-3 file:border file:border-gray-400 file:bg-white file:px-3 file:py-2 file:text-sm file:text-gray-900">
                    @error('file')
                        <p class="mt-2 text-sm text-gray-900">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="evaluation_process" class="block text-sm font-medium text-gray-800">{{ __('Processo de correção') }}</label>
                    <select id="evaluation_process" name="evaluation_process_id"
                            class="mt-2 block w-full text-sm text-gray-800 border-gray-300 rounded-md">
                        <option value="">{{ __('Selecione um processo de correção') }}</option>
                        @foreach ($processes as $process)
                            <option value="{{ $process->id }}">{{ $process->process_name }}</option>
                        @endforeach
                    </select>
                    @error('evaluation_process_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button type="submit" class="border border-gray-400 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100">
                        {{ __('Submeter projeto') }}
                    </button>
                </div>
            </form>
        @endif

        <section aria-labelledby="history-heading">
            <h2 id="history-heading" class="text-base font-medium text-gray-900">{{ __('As suas submissões') }}</h2>

            @if ($submissions->isEmpty())
                <p class="mt-3 text-sm text-gray-600">{{ __('Ainda não foram enviados ficheiros.') }}</p>
            @else
                <div class="mt-4 overflow-x-auto border border-gray-300">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-100 text-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Processo') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Data') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Ficheiro armazenado') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Estado') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Classificação') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Detalhes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($submissions as $row)
                                @php
                                    $result = $row->submissionResult;
                                    $summary = data_get($result?->report_sent_payload, 'results.summary', []);
                                    $tests = $result?->testExecutions ?? collect();
                                    $hasTests = $tests->isNotEmpty();
                                    $totalTests = $hasTests ? $tests->count() : ($summary['total_tests'] ?? 0);
                                    $passedCount = $hasTests ? $tests->where('status', 'passed')->count() : ($summary['successful'] ?? ($summary['passed'] ?? 0));
                                    $failedCount = $hasTests ? $totalTests - $passedCount : ($summary['failed'] ?? null);
                                    $hasSummary = $hasTests || ! empty($summary);
                                    $passed = $hasSummary && $failedCount === 0 && $row->status === 'graded';
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-gray-800">{{ $row->process->process_name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-gray-800">{{ $row->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2 text-gray-800">{{ basename($row->zip_file_path) }}</td>
                                    <td class="px-4 py-2 text-gray-800">{{ ucfirst($row->status) }}</td>
                                    <td class="px-4 py-2 text-gray-800">
                                        @if ($result && $result->final_grade !== null)
                                            {{ number_format((float) $result->final_grade, 1) }}%
                                        @elseif ($result && $result->report_sent)
                                            {{ __('A aguardar classificação estruturada') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-800 align-top max-w-md">
                                        @if ($row->status === 'processing' || $row->status === 'pending')
                                            <span class="text-gray-500">{{ __('A aguardar correção automática…') }}</span>
                                        @elseif ($hasSummary)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $passed ? __('Aprovado') : __('Reprovado') }}
                                            </span>
                                            <div class="mt-1 text-sm text-gray-600">
                                                {{ __('Tests passed') }}: {{ $passedCount }} / {{ $totalTests }}
                                            </div>
                                        @elseif ($result && $result->report_sent)
                                            <div class="space-y-2">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('Resultado pronto') }}</span>
                                                <p class="mt-1 text-sm text-gray-600">{{ __('Foi produzida saída de correção, mas não há resumo detalhado de testes disponível.') }}</p>
                                                <p class="mt-1 text-sm text-gray-700 break-words">{{ Str::limit($result->report_sent, 140) }}</p>
                                            </div>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif

                                        <div class="mt-3">
                                            <a href="{{ route('submissions.show', $row) }}" class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-900 hover:bg-gray-50">
                                                {{ __('Ver detalhes') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
