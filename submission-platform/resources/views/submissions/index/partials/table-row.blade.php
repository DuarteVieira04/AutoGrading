<tr
    data-submission-id="{{ $row->id }}"
    data-poll="{{ ($isPolling ?? false) ? '1' : '0' }}"
>
    <td class="px-4 py-2 text-gray-800">{{ $row->process->process_name ?? '—' }}</td>
    <td class="px-4 py-2 text-gray-800">{{ $row->processTestGroup->name ?? '—' }}</td>
    <td class="whitespace-nowrap px-4 py-2 text-gray-800">{{ $row->created_at->format('Y-m-d H:i') }}</td>
    <td class="px-4 py-2 text-gray-800">{{ basename($row->zip_file_path) }}</td>
    <td class="px-4 py-2 text-gray-800" data-cell="status">
        @include('submissions.partials.status-label', ['row' => $row])
    </td>
    <td class="px-4 py-2 text-gray-800" data-cell="grade">
        @include('submissions.partials.grade-points', [
            'result' => $result,
            'finalGradePoints' => $finalGradePoints,
            'maxGradePoints' => $maxGradePoints,
            'canView' => true,
            'showMax' => false,
        ])
    </td>
    <td class="px-4 py-2 text-gray-800 align-top max-w-md" data-cell="details">
        @include('submissions.index.partials.details-cell')
    </td>
</tr>
