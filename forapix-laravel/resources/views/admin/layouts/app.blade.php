<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ForaPix Admin')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f1226',
                        secondary: '#151a35',
                        dark: '#0a0b1a',
                        accent: '#7c3aed',
                        'accent-light': '#8b5cf6',
                        success: '#22c55e',
                        'success-light': '#4ade80',
                        warning: '#f59e0b',
                        gold: '#f59e0b',
                        'gold-light': '#fbbf24',
                        danger: '#ef4444'
                    }
                }
            }
        }
    </script>

    @stack('styles')
</head>
<body class="bg-dark text-white min-h-screen" style="font-family: 'Space Grotesk', sans-serif;">
    <div class="flex min-h-screen bg-gradient-to-br from-[#0b0f1d] via-[#111936] to-[#0d0f21]">
        <!-- Sidebar -->
        <aside class="w-[260px] bg-[#0f1324]/95 backdrop-blur-xl border-r border-white/5 hidden lg:flex flex-col shadow-2xl">
            <div class="px-6 py-6 border-b border-white/5 flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#0f1226] to-[#151a35] border border-white/10 flex items-center justify-center shadow-lg">
                    <i class="fas fa-leaf text-success-light text-xl -rotate-12" style="filter: drop-shadow(0 0 6px rgba(74, 222, 128, 0.55));"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-[0.2em]">Painel Admin</p>
                    <p class="text-2xl font-bold tracking-tight">ForaPix</p>
                </div>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2 text-sm">
                <p class="text-[11px] uppercase text-gray-500 tracking-[0.25em] mb-4">Navegação</p>
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.games.index') }}" class="nav-link {{ request()->routeIs('admin.games.*') ? 'active' : '' }}">
                    <i class="fas fa-gamepad"></i>
                    Jogos
                </a>
                <a href="{{ route('admin.matches.index') }}" class="nav-link {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}">
                    <i class="fas fa-fist-raised"></i>
                    Partidas
                </a>
                <a href="{{ route('admin.players.index') }}" class="nav-link {{ request()->routeIs('admin.players.*') ? 'active' : '' }}">
                    <i class="fas fa-user"></i>
                    Jogadores
                </a>
                <a href="{{ route('admin.bets.index') }}" class="nav-link {{ request()->routeIs('admin.bets.*') ? 'active' : '' }}">
                    <i class="fas fa-ticket"></i>
                    Apostas
                </a>
            </nav>
            <div class="px-6 py-6 border-t border-white/5 text-xs text-gray-500">
                <p class="text-gray-300 font-semibold">{{ auth('admin')->user()->name ?? 'Admin' }}</p>
                <p>Hoje é {{ now()->translatedFormat('d \d\e F') }}</p>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col">
            <header class="bg-[#111731]/80 backdrop-blur-lg border-b border-white/5 px-4 lg:px-10 py-4 flex items-center justify-between shadow-xl">
                <div class="flex items-center gap-4">
                    <button class="lg:hidden w-10 h-10 rounded-2xl bg-white/5 border border-white/10 text-white">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <p class="text-[11px] uppercase text-gray-400 tracking-[0.3em]">@yield('breadcrumb', 'Administração')</p>
                        <h1 class="text-xl font-semibold tracking-tight">@yield('title', 'ForaPix Admin')</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden md:flex items-center bg-white/5 border border-white/10 rounded-2xl px-4 py-2">
                        <i class="fas fa-search text-gray-400 mr-2"></i>
                        <input type="text" placeholder="Buscar..." class="bg-transparent text-sm focus:outline-none placeholder:text-gray-500">
                    </div>
                    <button class="w-10 h-10 rounded-2xl bg-white/5 border border-white/10 text-gray-300 hover:text-white transition">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-2xl px-3 py-2">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-sm font-semibold">
                            {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold leading-none">{{ auth('admin')->user()->name ?? 'Admin' }}</p>
                            <p class="text-xs text-gray-400">Administrador</p>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="ml-2 text-gray-400 hover:text-red-400 transition" title="Sair">
                                <i class="fas fa-power-off"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 lg:p-10">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="adminToast" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        class ValidationError extends Error {
            constructor(message) {
                super(message);
                this.name = 'ValidationError';
            }
        }

        function clearFormErrors(form) {
            form.querySelectorAll('.form-error-message').forEach(el => el.remove());
            form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        }

        function showFieldErrors(form, errors) {
            Object.entries(errors).forEach(([field, messages]) => {
                const input = form.querySelector(`[name="${field}"]`);
                if (!input) return;

                input.classList.add('input-error');
                const errorEl = document.createElement('p');
                errorEl.className = 'form-error-message mt-1 text-xs text-red-400';
                errorEl.textContent = messages.join(' ');

                const target = input.closest('.form-control') || input.parentElement;
                target?.appendChild(errorEl);
            });
        }

        function showAdminToast(message, type = 'success') {
            const container = document.getElementById('adminToast');
            if (!container) return;

            const colors = {
                success: 'bg-green-500/20 border-green-500/40 text-green-200',
                error: 'bg-red-500/20 border-red-500/40 text-red-200',
                warning: 'bg-yellow-500/20 border-yellow-500/40 text-yellow-200'
            };

            const div = document.createElement('div');
            div.className = `border px-4 py-3 rounded-xl backdrop-blur shadow-lg text-sm ${colors[type] || colors.success}`;
            div.textContent = message;
            container.appendChild(div);
            setTimeout(() => div.remove(), 4000);
        }

        function bindAjaxForms() {
            document.querySelectorAll('form.ajax-form').forEach(form => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.dataset.originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando';
                    }

                    clearFormErrors(form);

                    try {
                        const response = await fetch(form.action, {
                            method: form.getAttribute('method') || 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: new FormData(form)
                        });

                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            if (response.status === 422 && data?.errors) {
                                showFieldErrors(form, data.errors);
                                throw new ValidationError('Corrija os campos destacados.');
                            }
                            throw new Error(data?.message || 'Erro ao salvar');
                        }

                        showAdminToast(data.message || 'Registro salvo!');
                        if (form.dataset.reset !== 'false') {
                            form.reset();
                        }
                        if (form.dataset.reload !== 'false') {
                            setTimeout(() => window.location.reload(), 800);
                        }
                    } catch (error) {
                        if (!(error instanceof ValidationError)) {
                            console.error(error);
                        }
                        showAdminToast(error.message, 'error');
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.dataset.originalText;
                        }
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', bindAjaxForms);
    </script>

    <style>
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.85rem 1.1rem;
            border-radius: 1rem;
            color: #9ca3af;
            font-weight: 600;
            background: rgba(255,255,255,0.01);
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-link i {
            width: 18px;
            text-align: center;
        }

        .nav-link__pill {
            margin-left: auto;
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: rgba(34, 197, 94, 0.18);
            color: #4ade80;
            padding: 2px 8px;
            border-radius: 999px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.04);
            color: #fff;
        }

        .nav-link.active {
            background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(99,102,241,0.18));
            border-color: rgba(124,58,237,0.45);
            color: #fff;
            box-shadow: 0 10px 30px rgba(15,23,42,0.35);
        }

        .glass-card {
            background: rgba(15, 19, 36, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(3, 7, 18, 0.65);
        }

        .input-error {
            border-color: #f87171 !important;
            box-shadow: 0 0 0 1px #f87171;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(124,58,237,0.12), rgba(15,23,42,0.4));
            border-radius: 20px;
            border: 1px solid rgba(124,58,237,0.2);
            padding: 1.5rem;
        }

        .admin-btn-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: #fff;
            padding: 0.6rem 1rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .admin-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(124,58,237,0.4);
        }

        .admin-btn-gold {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #1a1208;
        }

        .admin-btn-gold:hover {
            box-shadow: 0 10px 25px rgba(245,158,11,0.4);
        }

        .admin-btn-ghost {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #cbd5e1;
        }

        .admin-btn-ghost:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .admin-btn-danger {
            background: rgba(239,68,68,0.12);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,0.3);
        }

        .admin-btn-danger:hover {
            background: rgba(239,68,68,0.22);
            color: #fff;
        }

        .input-admin {
            width: 100%;
            padding: 0.65rem 0.9rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 0.9rem;
            color: #fff;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .input-admin:focus {
            outline: none;
            border-color: rgba(124,58,237,0.6);
            box-shadow: 0 0 0 2px rgba(124,58,237,0.25);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .badge-success { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
        .badge-warning { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .badge-danger  { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }
        .badge-info    { background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.3); }
        .badge-muted   { background: rgba(148,163,184,0.12); color: #cbd5e1; border: 1px solid rgba(148,163,184,0.2); }
    </style>

    @stack('scripts')
</body>
</html>
