<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Bet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('bets')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'total'     => User::count(),
            'active'    => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'new_today' => User::whereDate('created_at', today())->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'balance'  => 'nullable|numeric|min:0',
            'is_admin' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'balance'  => $validated['balance'] ?? 0,
            'status'   => 'active',
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Usuário {$user->name} criado com sucesso.");
    }

    public function show(User $user)
    {
        $user->load('bets.match');
        $bets         = $user->bets()->latest()->take(10)->get();
        $totalBet     = $user->bets()->sum('amount');
        $totalWon     = $user->bets()->where('status', 'won')->sum('result_amount');
        $pendingBets  = $user->bets()->where('status', 'pending')->count();

        return view('admin.users.show', compact('user', 'bets', 'totalBet', 'totalWon', 'pendingBets'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:users,email,{$user->id}",
            'balance'  => 'nullable|numeric|min:0',
            'is_admin' => 'nullable|boolean',
        ]);

        $user->update([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'balance'  => $validated['balance'] ?? $user->balance,
            'is_admin' => $validated['is_admin'] ?? $user->is_admin,
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Dados atualizados com sucesso.');
    }

    public function suspend(Request $request, User $user)
    {
        if ($user->is_admin) {
            return response()->json(['success' => false, 'message' => 'Não é possível suspender um administrador.'], 422);
        }

        $user->update(['status' => 'suspended']);

        return response()->json(['success' => true, 'message' => "Usuário {$user->name} suspenso."]);
    }

    public function activate(User $user)
    {
        $user->update(['status' => 'active']);

        return response()->json(['success' => true, 'message' => "Usuário {$user->name} ativado."]);
    }

    public function resetPassword(User $user)
    {
        $newPassword = Str::random(10);
        $user->update(['password' => Hash::make($newPassword)]);

        return response()->json([
            'success'  => true,
            'message'  => "Senha redefinida com sucesso.",
            'password' => $newPassword,
        ]);
    }
}
