@extends('layouts.app')

@section('title', __('Project submissions'))

@section('content')
    <div class="space-y-10">
        <header class="border-b border-gray-300 pb-6">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Project submissions') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-gray-600">
                {{ __('Send a single .zip file, or select your whole project folder (browser will upload all files). Total size limit about 500 MB. PHP post_max_size / upload_max_size must allow your upload.') }}
            </p>
            @if (config('queue.default') === 'database')
                <p class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 px-3 py-2 rounded">
                    {{ __('Correção automática usa fila: execute em paralelo') }}
                    <code class="bg-white px-1 rounded">php artisan queue:work</code>
                    {{ __('para processar entregas.') }}
                </p>
            @endif
        </header>

        @if (! $hasStudentProfile)
            <div class="border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-800" role="status">
                {{ __('This login is not linked to a student record. Ask your teacher or administrator to create a student profile for your account, or use an account that was set up as a student.') }}
            </div>
        @else
            <section aria-labelledby="upload-heading" class="border border-gray-300 bg-gray-50 p-6">
                <h2 id="upload-heading" class="text-base font-medium text-gray-900">{{ __('Upload') }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ __('Use one option: upload one .zip, or pick a folder (Chrome, Edge, Safari). Do not use both.') }}</p>

                <form method="post" action="{{ route('submissions.store') }}" enctype="multipart/form-data" class="mt-5 space-y-6">
                    @csrf
                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-800">{{ __('Option A — ZIP file') }}</label>
                        <input id="file" name="file" type="file" accept=".zip,application/zip"
                            class="mt-2 block w-full text-sm text-gray-800 file:mr-3 file:border file:border-gray-400 file:bg-white file:px-3 file:py-2 file:text-sm file:text-gray-900">
                        @error('file')
                            <p class="mt-2 text-sm text-gray-900">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="files" class="block text-sm font-medium text-gray-800">{{ __('Option B — Project folder') }}</label>
                        <input id="files" name="files[]" type="file" multiple
                            class="mt-2 block w-full text-sm text-gray-800 file:mr-3 file:border file:border-gray-400 file:bg-white file:px-3 file:py-2 file:text-sm file:text-gray-900"
                            webkitdirectory directory>
                        @error('files')
                            <p class="mt-2 text-sm text-gray-900">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">{{ __('The server packs the folder into one .zip and shows that name in the table. PHP may limit how many files are accepted per request (see max_file_uploads in php.ini).') }}</p>
                    </div>
                    <div>
                        <button type="submit" class="border border-gray-400 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100">
                            {{ __('Submit project') }}
                        </button>
                    </div>
                </form>
            </section>
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
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Stored file') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Grade') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Feedback') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($submissions as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-gray-800">{{ $row->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2 text-gray-800">{{ basename($row->file_path) }}</td>
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
