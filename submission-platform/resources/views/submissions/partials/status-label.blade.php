@php
    $statusValue = $status ?? $row->status ?? '';
@endphp
<span class="submission-status-label">{{ \App\Support\SubmissionRowPresenter::statusLabel($statusValue) }}</span>
