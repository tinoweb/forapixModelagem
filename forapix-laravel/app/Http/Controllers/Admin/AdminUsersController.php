<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gerencia os usuários operadores do painel administrativo.
 * Diferente do UserManagementController (que gerencia apostadores),
 * este controller cuida de quem ACESSA o admin e quais permissões possui.
 */
class AdminUsersController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('is_admin', true)->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'super') {
                $query->where(function ($q) {
                    $q->whereNull('admin_permissions')
                      ->orWhereJsonLength('admin_permissions', 0);
                });
            } elseif ($request->type === 'operator') {
                $query->whereNotNull('admin_permissions')
                      ->whereJsonLength('admin_permissions', '>', 0);
            }
        }

        $adminUsers = $query->paginate(20)->withQueryString();

        $stats = [
            'total'      => User::where('is_admin', true)->count(),
            'super'      => User::where('is_admin', true)
                                ->where(function ($q) {
                                    $q->whereNull('admin_permissions')
                                      ->orWhereJsonLength('admin_permissions', 0);
                                })->count(),
            'operators'  => User::where('is_admin', true)
                                ->whereNotNull('admin_permissions')
                                ->whereJsonLength('admin_permissions', '>', 0)
                                ->count(),
            'active'     => User::where('is_admin', true)->where('status', 'active')->count(),
        ];

        $availablePermissions = User::$availablePermissions;
        $currentUser = Auth::guard('admin')->user();

        return view('admin.admin-users.index', compact('adminUsers', 'stats', 'availablePermissions', 'currentUser'));
    }

    public function create()
    {
        $availablePermissions = User::$availablePermissions;
        return view('admin.admin-users.create', compact('availablePermissions'));
    }

    public function store(Request $request)
    {
        $this->authorize403();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(User::$availablePermissions)),
        ]);

        User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'is_admin'          => true,
            'admin_permissions' => $validated['permissions'] ?? [],
            'status'            => 'active',
            'balance'           => 0,
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('success', "Operador {$validated['name']} criado com sucesso.");
    }

    public function edit(User $adminUser)
    {
        abort_unless($adminUser->is_admin, 404);
        $availablePermissions = User::$availablePermissions;
        $currentUser = Auth::guard('admin')->user();
        return view('admin.admin-users.edit', compact('adminUser', 'availablePermissions', 'currentUser'));
    }

    public function update(Request $request, User $adminUser)
    {
        $this->authorize403();
        abort_unless($adminUser->is_admin, 404);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => "required|email|unique:users,email,{$adminUser->id}",
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(User::$availablePermissions)),
            'status'        => 'required|in:active,suspended',
        ]);

        // Não permite remover permissões do próprio usuário logado
        $currentUser = Auth::guard('admin')->user();
        if ($currentUser->id === $adminUser->id) {
            return back()->withErrors(['permissions' => 'Você não pode alterar suas próprias permissões.']);
        }

        // Super admin: envia [] → null (sem restrições)
        $permissions = $validated['permissions'] ?? [];

        $adminUser->update([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'admin_permissions' => $permissions ?: null,
            'status'            => $validated['status'],
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('success', "Operador {$adminUser->name} atualizado.");
    }

    public function resetPassword(User $adminUser)
    {
        $this->authorize403();
        abort_unless($adminUser->is_admin, 404);

        $newPassword = Str::random(12);
        $adminUser->update(['password' => Hash::make($newPassword)]);

        return response()->json([
            'success'  => true,
            'message'  => 'Senha redefinida.',
            'password' => $newPassword,
        ]);
    }

    public function toggleStatus(Request $request, User $adminUser)
    {
        $this->authorize403();
        abort_unless($adminUser->is_admin, 404);

        $currentUser = Auth::guard('admin')->user();
        if ($currentUser->id === $adminUser->id) {
            return response()->json(['success' => false, 'message' => 'Você não pode suspender a si mesmo.'], 422);
        }

        $newStatus = $adminUser->status === 'active' ? 'suspended' : 'active';
        $adminUser->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Operador {$adminUser->name} " . ($newStatus === 'active' ? 'ativado' : 'suspenso') . ".",
            'status'  => $newStatus,
        ]);
    }

    public function destroy(User $adminUser)
    {
        $this->authorize403();
        abort_unless($adminUser->is_admin, 404);

        $currentUser = Auth::guard('admin')->user();
        if ($currentUser->id === $adminUser->id) {
            return response()->json(['success' => false, 'message' => 'Você não pode excluir a si mesmo.'], 422);
        }

        $name = $adminUser->name;
        // Remove tokens antes de excluir
        $adminUser->tokens()->delete();
        $adminUser->delete();

        return response()->json(['success' => true, 'message' => "Operador {$name} removido."]);
    }

    /**
     * Garante que apenas super admins podem gerenciar operadores.
     */
    private function authorize403(): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Apenas super administradores podem gerenciar operadores.');
        }
    }
}
