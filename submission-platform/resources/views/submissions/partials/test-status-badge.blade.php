@php
    $testStatus = (string) ($status ?? 'unknown');
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $testStatus === 'passed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
    {{ \App\Support\SubmissionRowPresenter::testStatusLabel($testStatus) }}
</span>
