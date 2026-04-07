<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsTeacher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next) {
        if (!$request->user() || !$request->user()->isTeacher()) {
            return response()->json(['error' => 'Acesso negado. Apenas professores.'], 403);
        }
        return $next($request);
    }
}
