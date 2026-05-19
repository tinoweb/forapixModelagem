<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica se o operador admin possui uma permissão específica.
 * Uso nas rotas: middleware('admin.perm:manage_matches')
 */
class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::guard('admin')->user();

        if (!$user || !$user->hasAdminPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado — você não tem permissão para esta ação.',
                ], 403);
            }

            return redirect()->route('admin.dashboard')
                ->withErrors(['permissao' => 'Você não tem permissão para acessar esta seção.']);
        }

        return $next($request);
    }
}
