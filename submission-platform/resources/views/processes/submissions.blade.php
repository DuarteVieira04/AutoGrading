<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Submissões do processo') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $process->process_name ?? __('Processo sem nome') }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('processes.index') }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    {{ __('Voltar aos processos') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-6">
                @if ($process->submissions->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-lg font-medium text-gray-900">{{ __('Ainda não há submissões para este processo.') }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ __('As submissões dos alunos aparecerão aqui assim que forem enviadas.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('Estudante') }}</th>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('Grupo de testes') }}</th>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('Submetido em') }}</th>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('Ficheiro') }}</th>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('Estado') }}</th>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('Classificação') }}</th>
                                    <th class="px-4 py-3 text-right font-medium">{{ __('Ações') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($process->submissions as $submission)
                                    @php
                                        $result = $submission->submissionResult;
                                        $grade = $result?->final_grade;
                                        $hasReport = $result && $result->report_sent;
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-800">{{ $submission->student->name ?? __('Anónimo') }}</td>
                                        <td class="px-4 py-3 text-gray-800">{{ $submission->processTestGroup->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-800">{{ $submission->submission_date?->format('d/m/Y H:i') ?? $submission->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-800">{{ basename($submission->zip_file_path) }}</td>
                                        <td class="px-4 py-3 text-gray-800">{{ ucfirst($submission->status) }}</td>
                                        <td class="px-4 py-3 text-gray-800">
                                            @if ($grade !== null)
                                                {{ number_format((float) $grade, 1) }} {{ __('pts') }}
                                            @elseif ($hasReport)
                                                {{ __('Resultado pronto') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('submissions.show', $submission) }}"
                                               class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-900 hover:bg-gray-50">
                                                {{ __('Ver detalhes') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
