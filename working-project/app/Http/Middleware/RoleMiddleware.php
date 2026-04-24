<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check() || !$user = $request->user()) {
            return redirect('login');
        }

        // Garante que o tipo do utilizador é um Enum e obtém o seu valor em string
        $userRoleValue = $user->type?->value;

        // Se o tipo do utilizador estiver na lista de roles permitidas, deixa passar
        if ($userRoleValue && in_array($userRoleValue, $roles)) {
            return $next($request);
        }

        // Caso contrário, nega o acesso
        abort(403, 'Acesso Não Autorizado.');
    }
}