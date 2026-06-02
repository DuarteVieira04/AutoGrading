<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The request body exceeds the server post_max_size limit.',
                ], 413);
            }

            $message = __('O ficheiro ZIP excede o limite de upload do PHP (post_max_size / upload_max_filesize). Aumenta esses valores no php.ini (ver submission-platform/php.ini).');

            if ($request->routeIs('submissions.store')) {
                return redirect()->route('submissions.index')->withErrors(['file' => $message]);
            }

            if ($request->routeIs('processes.store', 'processes.update')) {
                return redirect()->back()->withInput()->withErrors(['project_zip' => $message]);
            }

            return redirect()->back()->withInput()->withErrors(['file' => $message]);
        });
    }
}
