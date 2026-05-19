<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Auth;

trait ChecksAdminPermission
{
    protected function requirePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        if (!$user || !$user->hasAdminPermission($permission)) {
            abort(403, 'Você não tem permissão para acessar esta seção.');
        }
    }
}
