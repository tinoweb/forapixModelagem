<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show admin login form
     */
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login-simple');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Rate limiting
        $key = Str::lower($request->input('email')).'|'.$request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Muitas tentativas de login. Tente novamente em {$seconds} segundos."
            ]);
        }

        // Check if user exists and is admin
        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->is_admin || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 300); // 5 minutes lockout
            
            return back()->withErrors([
                'email' => 'Credenciais inválidas ou acesso não autorizado.'
            ])->withInput();
        }

        // Check if account is active
        if ($user->status !== 'active') {
            return back()->withErrors([
                'email' => 'Conta inativa ou suspensa.'
            ])->withInput();
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        // Login user
        Auth::guard('admin')->login($user, $request->filled('remember'));

        // Update login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Log admin login
        $this->logAdminActivity('admin_login', null, null, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        $this->logAdminActivity('admin_logout');

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logout realizado com sucesso.');
    }

    /**
     * Show admin profile
     */
    public function profile()
    {
        $user = Auth::guard('admin')->user();
        return view('admin.auth.profile', compact('user'));
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('admin')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Verify current password if changing password
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Senha atual incorreta.']);
            }
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $oldData = $user->only(['name', 'email']);
        $user->update($userData);

        $this->logAdminActivity('profile_update', 'User', $user->id, $oldData, $userData);

        return back()->with('success', 'Perfil atualizado com sucesso.');
    }

    /**
     * Show two-factor authentication setup
     */
    public function showTwoFactor()
    {
        $user = Auth::guard('admin')->user();
        return view('admin.auth.two-factor', compact('user'));
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(Request $request)
    {
        $user = Auth::guard('admin')->user();

        // Generate secret key
        $secret = $this->generateTwoFactorSecret();
        
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
        ]);

        $this->logAdminActivity('two_factor_enabled');

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'qr_code' => $this->generateQrCode($user->email, $secret)
        ]);
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::guard('admin')->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false, 
                'errors' => ['password' => ['Senha incorreta.']]
            ], 422);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
        ]);

        $this->logAdminActivity('two_factor_disabled');

        return response()->json(['success' => true]);
    }

    /**
     * Show admin sessions
     */
    public function sessions()
    {
        $user = Auth::guard('admin')->user();
        
        // Get recent login sessions from logs
        $sessions = \DB::table('admin_logs')
            ->where('user_id', $user->id)
            ->where('action', 'admin_login')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.auth.sessions', compact('sessions'));
    }

    /**
     * Generate two-factor secret
     */
    private function generateTwoFactorSecret()
    {
        return Str::random(32);
    }

    /**
     * Generate QR code for 2FA
     */
    private function generateQrCode($email, $secret)
    {
        $appName = config('app.name');
        $qrCodeUrl = "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";
        
        // In production, use a proper QR code library
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrCodeUrl);
    }

    /**
     * Log admin activity
     */
    private function logAdminActivity($action, $model = null, $modelId = null, $oldValues = null, $newValues = null)
    {
        if (!Auth::guard('admin')->check()) {
            return;
        }

        \DB::table('admin_logs')->insert([
            'user_id' => Auth::guard('admin')->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
