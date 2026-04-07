<?php

namespace App\Http\Controllers;

use App\Jobs\GradeProjectSubmissionJob;
use App\Models\GradingProcess;
use App\Models\ProjectSubmissions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class SubmissionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $student = $user->student;
        $submissions = $student
            ? $student->codeDeliveries()->latest()->get()
            : collect();

        return view('submissions.index', [
            'submissions' => $submissions,
            'hasStudentProfile' => (bool) $student,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        if (! $student) {
            return back()->withErrors([
                'file' => 'Your account must be linked to a student profile to upload.',
            ]);
        }

        $request->validate([
            'file' => ['nullable', 'file', 'mimes:zip', 'max:512000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file'],
        ]);

        $hasZip = $request->hasFile('file');
        $folderFiles = $request->file('files', []);
        $folderFiles = is_array($folderFiles) ? array_values(array_filter($folderFiles)) : [];
        $hasFolder = count($folderFiles) > 0;

        if (! $hasZip && ! $hasFolder) {
            return back()->withErrors([
                'file' => __('Upload a .zip file or choose a project folder.'),
            ])->withInput();
        }

        if ($hasZip && $hasFolder) {
            return back()->withErrors([
                'file' => __('Use either a ZIP file or a folder, not both.'),
            ])->withInput();
        }

        $maxBytes = 512000 * 1024;

        if ($hasZip) {
            $path = $request->file('file')->store('submissions', 'public');
        } else {
            $totalBytes = collect($folderFiles)->sum(fn (UploadedFile $f) => $f->getSize());
            if ($totalBytes > $maxBytes) {
                return back()->withErrors([
                    'file' => __('Total folder size exceeds the maximum allowed (about 500 MB).'),
                ])->withInput();
            }

            $path = $this->storeFolderAsZip($folderFiles, $student->id);
        }

        $submission = ProjectSubmissions::create([
            'student_id' => $student->id,
            'grading_process_id' => GradingProcess::active()?->id,
            'file_path' => $path,
            'status' => 'pending',
        ]);

        GradeProjectSubmissionJob::dispatch($submission->id)->afterCommit();

        return redirect()
            ->route('submissions.index')
            ->with('status', __('Upload received. Automatic grading has been queued — refresh in a few moments for feedback.'));
    }

    /**
     * Pack uploaded directory files into a zip on the public disk.
     */
    private function storeFolderAsZip(array $uploadedFiles, int $studentId): string
    {
        $zipName = sprintf('submission-%d-%s.zip', $studentId, now()->format('YmdHis'));

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempZip = $tempDir.DIRECTORY_SEPARATOR.uniqid('zip_', true).'.zip';

        $zip = new ZipArchive;
        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create archive.');
        }

        $added = 0;
        foreach ($uploadedFiles as $uploadedFile) {
            if (! $uploadedFile instanceof UploadedFile || ! $uploadedFile->isValid()) {
                continue;
            }

            $relative = $this->zipEntryName($uploadedFile);
            $realPath = $uploadedFile->getRealPath();
            if ($realPath && is_file($realPath)) {
                $zip->addFile($realPath, $relative);
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($tempZip);

            throw ValidationException::withMessages([
                'files' => [__('No files could be packed from the selected folder. Try a ZIP upload instead.')],
            ]);
        }

        $storedPath = Storage::disk('public')->putFileAs('submissions', $tempZip, $zipName);

        @unlink($tempZip);

        if ($storedPath === false) {
            abort(500, 'Could not store archive.');
        }

        return $storedPath;
    }

    private function zipEntryName(UploadedFile $uploadedFile): string
    {
        $path = method_exists($uploadedFile, 'getClientOriginalPath')
            ? $uploadedFile->getClientOriginalPath()
            : '';

        if ($path === '' || $path === null) {
            $path = $uploadedFile->getClientOriginalName();
        }

        $path = str_replace('\\', '/', (string) $path);
        $parts = array_values(array_filter(explode('/', $path), function ($segment) {
            return $segment !== '' && $segment !== '.' && $segment !== '..';
        }));

        return implode('/', $parts) ?: 'file.bin';
    }
}
