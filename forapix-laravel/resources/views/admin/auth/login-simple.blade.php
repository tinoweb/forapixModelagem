<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JrPix - Login Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white;
        }
        .container { 
            max-width: 400px; 
            width: 100%; 
            padding: 20px;
        }
        .logo { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .logo h1 { 
            font-size: 2.5rem; 
            color: #7c3aed; 
            margin-bottom: 10px; 
        }
        .form-container { 
            background: rgba(26, 26, 46, 0.8); 
            padding: 40px; 
            border-radius: 20px; 
            border: 1px solid rgba(124, 58, 237, 0.3);
            backdrop-filter: blur(10px);
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold; 
            color: #e5e7eb;
        }
        input[type="email"], input[type="password"] { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #374151; 
            border-radius: 10px; 
            background: #1f2937; 
            color: white; 
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="email"]:focus, input[type="password"]:focus { 
            outline: none; 
            border-color: #7c3aed; 
        }
        .btn { 
            width: 100%; 
            padding: 15px; 
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: transform 0.2s;
        }
        .btn:hover { 
            transform: translateY(-2px); 
        }
        .error { 
            background: rgba(239, 68, 68, 0.2); 
            border: 1px solid #ef4444; 
            color: #fca5a5; 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
        }
        .success { 
            background: rgba(34, 197, 94, 0.2); 
            border: 1px solid #22c55e; 
            color: #86efac; 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
        }
        .loading { 
            opacity: 0.7; 
            pointer-events: none; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>⚡ JRPIX</h1>
            <p>Painel Administrativo</p>
        </div>
        
        <div class="form-container">
            @if(session('success'))
                <div class="success">{{ session('success') }}</div>
            @endif
            
            @if($errors->any())
                <div class="error">
                    @foreach($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif
            
            <form method="POST" action="{{ route('admin.login') }}" id="loginForm">
                @csrf
                
                <div class="form-group">
                    <label for="email">📧 Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Seu email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Senha</label>
                    <input type="password" id="password" name="password" placeholder="Sua senha" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember" style="margin-right: 10px;">
                        Lembrar-me
                    </label>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    🚀 Entrar no Painel
                </button>
            </form>
            
            <div style="margin-top: 20px; text-align: center; font-size: 14px; color: #9ca3af;">
                <p>🔐 Acesso restrito para administradores</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '⏳ Entrando...';
            btn.disabled = true;
            this.classList.add('loading');
        });
        
        // Auto-focus no email
        document.getElementById('email').focus();
    </script>
</body>
</html>
