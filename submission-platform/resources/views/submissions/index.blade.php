@extends('layouts.app')

@section('title', __('Project submissions'))

@section('content')
    <div class="space-y-10">
        <header class="border-b border-gray-300 pb-6">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Submissões de Projetos') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-gray-600">
                {{ __('Send a single .zip file, or select your whole project folder (browser will upload all files). Total size limit about 500 MB.') }}
            </p>
            @if (config('queue.default') === 'database')
                <p class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 px-3 py-2 rounded">
                    {{ __('Correção automática usa fila: execute em paralelo') }}
                    <code class="bg-white px-1 rounded">php artisan queue:work</code>
                    {{ __('para processar entregas.') }}
                </p>
            @endif
        </header>

        @if ($hasStudentProfile)
            <form method="post" action="{{ route('submissions.store') }}" enctype="multipart/form-data" class="mt-5 space-y-6">
                @csrf
                <input type="hidden" name="student_id" value="{{ auth()->user()->student->id }}">

                <div>
                    <label for="file" class="block text-sm font-medium text-gray-800">{{ __('Upload ZIP file') }}</label>
                    <input id="file" name="file" type="file" accept=".zip,application/zip"
                        class="mt-2 block w-full text-sm text-gray-800 file:mr-3 file:border file:border-gray-400 file:bg-white file:px-3 file:py-2 file:text-sm file:text-gray-900">
                    @error('file')
                        <p class="mt-2 text-sm text-gray-900">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                <label for="grading_process" class="block text-sm font-medium text-gray-800">{{ __('Grading Process') }}</label>
                <select id="grading_process" name="grading_process_id"
                        class="mt-2 block w-full text-sm text-gray-800 border-gray-300 rounded-md">
                    <option value="">{{ __('Select a grading process') }}</option>
                    @foreach ($gradingProcesses as $process)
                        @if ($process->is_active)
                            <option value="{{ $process->id }}">{{ $process->name }}</option>
                        @endif
                    @endforeach
                </select>
                @error('grading_process_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                </div>

                <div>
                    <button type="submit" class="border border-gray-400 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100">
                        {{ __('Submit project') }}
                    </button>
                </div>
            </form>
        @endif
        <section aria-labelledby="history-heading">
            <h2 id="history-heading" class="text-base font-medium text-gray-900">{{ __('Your uploads') }}</h2>

            @if ($submissions->isEmpty())
                <p class="mt-3 text-sm text-gray-600">{{ __('No files uploaded yet.') }}</p>
            @else
                <div class="mt-4 overflow-x-auto border border-gray-300">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-100 text-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Grupo') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Stored file') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Grading process') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Grade') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Feedback') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($submissions as $row)
                                <tr>
                                    <td class="px-4 py-2 text-gray-800">
                                        @if ($groups->isNotEmpty())
                                            @foreach ($groups as $group)
                                                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                                    {{ $group->name }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400 text-sm italic">{{ __('Não tem grupo') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-gray-800">{{ $row->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2 text-gray-800">{{ basename($row->file_path) }}</td>
                                    <td class="px-4 py-2 text-gray-800"> {{ $row->gradingProcess->name ?? '—' }} </td>
                                    <td class="px-4 py-2 text-gray-800">{{ ucfirst($row->status) }}</td>
                                    <td class="px-4 py-2 text-gray-800">
                                        @if ($row->grade !== null)
                                            {{ number_format((float) $row->grade, 1) }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-800 align-top max-w-md">
                                        @if ($row->status === 'processing' || $row->status === 'pending')
                                            <span class="text-gray-500">{{ __('Waiting for automatic grading…') }}</span>
                                        @elseif (! empty($row->feedback['results']['summary']))
                                            @php $s = $row->feedback['results']['summary']; @endphp
                                            <span class="text-sm">
                                                {{ __('Tests passed') }}:
                                                {{ $s['successful'] ?? 0 }} / {{ $s['total_tests'] ?? 0 }}
                                                @if (! empty($s['duration']))
                                                    · {{ round((float) $s['duration'], 1) }}s
                                                @endif
                                            </span>
                                        @elseif (! empty($row->feedback['error']))
                                            <span class="text-red-700 text-sm">{{ \Illuminate\Support\Str::limit($row->feedback['error'], 200) }}</span>
                                        @else
                                            —
                                        @endif
                                        @if (! empty($row->feedback) && is_array($row->feedback))
                                            <details class="mt-2 text-xs">
                                                <summary class="cursor-pointer text-gray-600 hover:underline">{{ __('Full details') }}</summary>
                                                <pre class="mt-2 max-h-64 overflow-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs">{{ json_encode($row->feedback, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </details>
                                        @endif
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
