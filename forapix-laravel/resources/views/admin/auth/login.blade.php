<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JrPix - Login Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a2e',
                        secondary: '#16213e',
                        accent: '#7c3aed',
                        dark: '#0f0f1a',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-dark min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 mb-4">
                <i class="fas fa-bolt text-accent text-3xl"></i>
                <span class="text-3xl font-bold text-white">JRPIX</span>
            </div>
            <h1 class="text-xl text-gray-400">Painel Administrativo</h1>
        </div>

        <!-- Login Form -->
        <div class="bg-primary rounded-2xl p-8 border border-gray-700">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-900/20 border border-green-600/30 rounded-xl text-green-400">
                    {{ session('success') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-900/20 border border-red-600/30 rounded-xl text-red-400">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            
            <form method="POST" action="{{ route('admin.login') }}" id="loginForm">
                @csrf
                <input type="hidden" name="_token" value="{{ csrf_token() }}" id="csrfToken">
                
                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}"
                           class="w-full px-4 py-3 bg-secondary border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                           placeholder="admin@jrpix.com"
                           required>
                    @error('email')
                        <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-lock mr-2"></i>Senha
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password"
                               class="w-full px-4 py-3 bg-secondary border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 transition pr-12"
                               placeholder="••••••••"
                               required>
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition">
                            <i id="passwordIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="remember" 
                               class="w-4 h-4 text-accent bg-secondary border-gray-600 rounded focus:ring-accent focus:ring-2">
                        <span class="ml-2 text-sm text-gray-300">Lembrar-me</span>
                    </label>
                    
                    <a href="#" class="text-sm text-accent hover:text-purple-400 transition">
                        Esqueceu a senha?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-accent hover:bg-purple-600 text-white font-semibold py-3 px-4 rounded-xl transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-accent/50">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Entrar no Painel
                </button>
            </form>

            <!-- Security Notice -->
            <div class="mt-6 p-4 bg-yellow-900/20 border border-yellow-600/30 rounded-xl">
                <div class="flex items-start gap-3">
                    <i class="fas fa-shield-alt text-yellow-400 mt-1"></i>
                    <div>
                        <h4 class="text-yellow-400 font-semibold text-sm">Acesso Restrito</h4>
                        <p class="text-yellow-300 text-xs mt-1">
                            Este painel é exclusivo para administradores autorizados. 
                            Todas as ações são monitoradas e registradas.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-gray-500 text-sm">
                © {{ date('Y') }} JrPix. Todos os direitos reservados.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        // Refresh CSRF token every 10 minutes
        function refreshCSRFToken() {
            fetch('/admin/refresh-csrf', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.token) {
                    document.getElementById('csrfToken').value = data.token;
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                }
            })
            .catch(error => console.log('CSRF refresh error:', error));
        }

        // Refresh token every 10 minutes
        setInterval(refreshCSRFToken, 600000);

        // Handle form submission with better error handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Entrando...';
            
            // Re-enable button after 5 seconds to prevent permanent disable
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Entrar no Painel';
            }, 5000);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
