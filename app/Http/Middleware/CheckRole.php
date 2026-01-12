<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Verifica se o usuário tem uma das roles permitidas
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // super_admin tem acesso a tudo
        if ($request->user()->isSuperAdmin()) {
            return $next($request);
        }

        // Verifica se usuário tem algum dos roles permitidos
        if (in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso não autorizado para esta ação.'
            ], 403);
        }

        abort(403, 'Acesso não autorizado para esta ação.');
    }
}
