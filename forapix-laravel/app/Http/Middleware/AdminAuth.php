<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated as admin
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso não autorizado'
                ], 401);
            }
            
            return redirect()->route('admin.login');
        }

        $user = Auth::guard('admin')->user();

        // Check if user is actually an admin
        if (!$user->is_admin) {
            Auth::guard('admin')->logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado - privilégios insuficientes'
                ], 403);
            }
            
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Acesso negado - privilégios insuficientes']);
        }

        // Check if account is active
        if ($user->status !== 'active') {
            Auth::guard('admin')->logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conta inativa ou suspensa'
                ], 403);
            }
            
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Conta inativa ou suspensa']);
        }

        return $next($request);
    }
}
