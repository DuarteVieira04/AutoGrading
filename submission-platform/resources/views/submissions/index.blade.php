@extends('layouts.app')

@section('title', __('Submissões'))

@section('content')
    @php use Illuminate\Support\Str; @endphp
    <div class="space-y-10">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('success') }}
            </div>
        @endif

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
                    <label for="process_test_group_id" class="block text-sm font-medium text-gray-800">{{ __('Processo de correção') }}</label>
                    <select id="process_test_group_id" name="process_test_group_id" required
                            class="mt-2 block w-full text-sm text-gray-800 border-gray-300 rounded-md">
                        <option value="">{{ __('Selecione um processo de correção') }}</option>
                        @foreach ($processes as $process)
                            @php
                                $defaultGroup = $process->processTestGroups->first();
                                $limit = $process->submissionLimit();
                                $used = $limit > 0 ? $process->submissionsCountForStudent((int) auth()->id()) : 0;
                                $suffix = $limit > 0
                                    ? ' — '.__(':used/:limit submissões', ['used' => $used, 'limit' => $limit])
                                    : '';
                            @endphp
                            @if ($defaultGroup)
                                <option value="{{ $defaultGroup->id }}">{{ ($process->process_name ?? __('Processo sem nome')).$suffix }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('process_test_group_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if ($processes->isEmpty())
                        <p class="mt-2 text-sm text-gray-600">{{ __('Não há processos de correção disponíveis para a sua turma (sem projeto pronto ou limite de submissões atingido).') }}</p>
                    @endif
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
                    <table
                        id="submissions-table"
                        class="min-w-full divide-y divide-gray-200 text-left text-sm"
                        data-poll-url="{{ route('submissions.poll-statuses') }}"
                        data-poll-interval="3000"
                    >
                        <thead class="bg-gray-100 text-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Processo') }}</th>
                                <th scope="col" class="px-4 py-2 font-medium">{{ __('Grupo') }}</th>
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
                                    $rowView = \App\Support\SubmissionRowPresenter::forSubmission($row);
                                    $rowView['isPolling'] = in_array($row->status, ['pending', 'processing'], true);
                                @endphp
                                @include('submissions.index.partials.table-row', $rowView)
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
