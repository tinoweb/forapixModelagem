<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b0f1d">
    <title>@yield('title', 'JrPix Admin')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

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

    <!-- ══════════════════════════════════════════════
         MOBILE SIDEBAR BACKDROP
    ══════════════════════════════════════════════ -->
    <div id="sidebarBackdrop"
         class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm hidden lg:hidden"
         onclick="closeSidebar()"></div>

    <!-- ══════════════════════════════════════════════
         SIDEBAR (desktop fixo | mobile drawer)
    ══════════════════════════════════════════════ -->
    <aside id="adminSidebar"
           class="fixed top-0 left-0 h-full z-50 w-[280px] bg-[#0c1020]/98 backdrop-blur-xl border-r border-white/5 flex flex-col shadow-2xl
                  transition-transform duration-300 ease-in-out
                  -translate-x-full lg:translate-x-0">

        <!-- Logo -->
        <div class="px-5 py-5 border-b border-white/5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0f1226] to-[#151a35] border border-white/10 flex items-center justify-center shadow-lg flex-shrink-0">
                    <i class="fas fa-leaf text-success-light text-base -rotate-12" style="filter:drop-shadow(0 0 6px rgba(74,222,128,0.55))"></i>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-[0.2em]">Painel Admin</p>
                    <p class="text-xl font-bold tracking-tight leading-none">JrPix</p>
                </div>
            </div>
            <button onclick="closeSidebar()" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl bg-white/5 border border-white/10 text-gray-400 hover:text-white">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-5 space-y-1 text-sm overflow-y-auto">
            <p class="text-[10px] uppercase text-gray-500 tracking-[0.25em] mb-3 px-2">Navegação</p>
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="{{ route('admin.matches.index') }}" class="nav-link {{ request()->routeIs('admin.matches.index') || (request()->routeIs('admin.matches.*') && !request()->routeIs('admin.matches.betting-locks')) ? 'active' : '' }}">
                <i class="fas fa-trophy"></i> Partidas
                @php $liveCount = \App\Models\GameMatch::where('status','live')->count(); @endphp
                @if($liveCount > 0)
                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-500/20 text-green-300 animate-pulse">{{ $liveCount }} AO VIVO</span>
                @endif
            </a>
            <a href="{{ route('admin.matches.betting-locks') }}" class="nav-link {{ request()->routeIs('admin.matches.betting-locks') ? 'active' : '' }}">
                <i class="fas fa-lock"></i> Trancar Apostas
                @php $lockedCount = \App\Models\GameMatch::whereIn('status',['scheduled','live'])->where('betting_locked', true)->count(); @endphp
                @if($lockedCount > 0)
                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-300">{{ $lockedCount }} TRAN.</span>
                @endif
            </a>
            <a href="{{ route('admin.bets.index') }}" class="nav-link {{ request()->routeIs('admin.bets.*') ? 'active' : '' }}">
                <i class="fas fa-ticket"></i> Apostas
            </a>
            <a href="{{ route('admin.players.index') }}" class="nav-link {{ request()->routeIs('admin.players.*') ? 'active' : '' }}">
                <i class="fas fa-user"></i> Jogadores
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Usuários
            </a>
            @php $pendingWithdrawals = \App\Models\Transaction::where('type','withdraw')->where('status','pending')->count(); @endphp
            <a href="{{ route('admin.financial.index') }}" class="nav-link {{ request()->routeIs('admin.financial.*') ? 'active' : '' }}">
                <i class="fas fa-dollar-sign"></i> Financeiro
                @if($pendingWithdrawals > 0)
                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500/20 text-red-300">{{ $pendingWithdrawals }}</span>
                @endif
            </a>
            <a href="{{ route('admin.games.index') }}" class="nav-link {{ request()->routeIs('admin.games.*') ? 'active' : '' }}">
                <i class="fas fa-gamepad"></i> Jogos
            </a>
            <div class="pt-3 mt-2 border-t border-white/5">
                <p class="text-[10px] uppercase text-gray-500 tracking-[0.25em] mb-3 px-2">Conta</p>
                <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile*') ? 'active' : '' }}">
                    <i class="fas fa-user-circle"></i> Meu Perfil
                </a>
            </div>
            @if(auth('admin')->user()?->isSuperAdmin())
            <div class="pt-3 mt-2 border-t border-white/5">
                <p class="text-[10px] uppercase text-gray-500 tracking-[0.25em] mb-3 px-2">Administração</p>
                <a href="{{ route('admin.admin-users.index') }}" class="nav-link {{ request()->routeIs('admin.admin-users.*') ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i> Operadores
                </a>
                <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Configurações
                </a>
            </div>
            @endif
        </nav>

        <!-- User info + logout -->
        <div class="px-4 py-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth('admin')->user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-gray-400">{{ now()->translatedFormat('d \d\e F') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition" title="Sair">
                        <i class="fas fa-power-off text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- ══════════════════════════════════════════════
         MAIN WRAPPER
    ══════════════════════════════════════════════ -->
    <div class="lg:pl-[280px] min-h-screen flex flex-col bg-gradient-to-br from-[#0b0f1d] via-[#111936] to-[#0d0f21]">

        <!-- TOP HEADER -->
        <header class="sticky top-0 z-30 bg-[#0c1020]/90 backdrop-blur-lg border-b border-white/5 px-4 lg:px-8 py-3 flex items-center justify-between gap-3 shadow-xl">
            <!-- Left: hamburguer + título -->
            <div class="flex items-center gap-3 min-w-0">
                <button id="sidebarToggle" onclick="openSidebar()"
                        class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 border border-white/10 text-white active:scale-95 transition flex-shrink-0">
                    <i class="fas fa-bars text-base"></i>
                </button>
                <div class="min-w-0">
                    <p class="text-[10px] uppercase text-gray-500 tracking-[0.3em] hidden sm:block truncate">@yield('breadcrumb', 'Administração')</p>
                    <h1 class="text-base lg:text-xl font-semibold tracking-tight truncate">@yield('title', 'JrPix Admin')</h1>
                </div>
            </div>

            <!-- Right: ações rápidas -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Acesso rápido: Partidas ao vivo -->
                @if(isset($liveCount) && $liveCount > 0)
                <a href="{{ route('admin.matches.index', ['status' => 'live']) }}"
                   class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl bg-green-500/15 border border-green-500/25 text-green-300 text-xs font-bold animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                    {{ $liveCount }} ao vivo
                </a>
                @endif

                <!-- Avatar desktop -->
                <div class="hidden lg:flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-3 py-2">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 2)) }}
                    </div>
                    <span class="text-sm font-semibold">{{ explode(' ', auth('admin')->user()->name ?? 'Admin')[0] }}</span>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="flex-1 overflow-x-hidden p-3 sm:p-5 lg:p-8 pb-24 lg:pb-8">
            @if(session('success'))
                <div class="mb-4 flex items-start gap-3 bg-green-500/10 border border-green-500/25 rounded-2xl px-4 py-3 text-sm text-green-300">
                    <i class="fas fa-circle-check mt-0.5 flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 flex items-start gap-3 bg-red-500/10 border border-red-500/25 rounded-2xl px-4 py-3 text-sm text-red-300">
                    <i class="fas fa-circle-xmark mt-0.5 flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @yield('content')
        </main>

        <!-- ══════════════════════════════════════════════
             BOTTOM NAV — somente mobile
        ══════════════════════════════════════════════ -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-[#0c1020]/95 backdrop-blur-xl border-t border-white/8 flex items-center safe-area-bottom shadow-2xl">
            @php
                $bottomNav = [
                    ['route' => 'admin.dashboard',           'icon' => 'fa-chart-line', 'label' => 'Dashboard'],
                    ['route' => 'admin.matches.index',       'icon' => 'fa-trophy',     'label' => 'Partidas'],
                    ['route' => 'admin.matches.betting-locks','icon' => 'fa-lock',       'label' => 'Trancar'],
                    ['route' => 'admin.bets.index',          'icon' => 'fa-ticket',     'label' => 'Apostas'],
                    ['route' => 'admin.users.index',         'icon' => 'fa-users',      'label' => 'Usuários'],
                ];
            @endphp
            @foreach($bottomNav as $item)
                @php $isActive = request()->routeIs(str_replace('.index','',$item['route']).'*'); @endphp
                <a href="{{ route($item['route']) }}"
                   class="bottom-nav-item {{ $isActive ? 'active' : '' }}">
                    <i class="fas {{ $item['icon'] }} text-lg"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <!-- More button abre sidebar -->
            <button onclick="openSidebar()" class="bottom-nav-item">
                <i class="fas fa-ellipsis text-lg"></i>
                <span>Mais</span>
            </button>
        </nav>
    </div>

    <!-- Toast container -->
    <div id="adminToast" class="fixed top-4 right-4 z-[9999] space-y-2 max-w-[calc(100vw-2rem)]"></div>

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
                        if (form.dataset.redirect) {
                            setTimeout(() => window.location.href = form.dataset.redirect, 800);
                        } else if (form.dataset.reload !== 'false') {
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

        // ── Sidebar drawer (mobile) ──────────────────────────
        function openSidebar() {
            document.getElementById('adminSidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebarBackdrop').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            document.getElementById('adminSidebar').classList.add('-translate-x-full');
            document.getElementById('sidebarBackdrop').classList.add('hidden');
            document.body.style.overflow = '';
        }
        // Fechar ao redimensionar para desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) closeSidebar();
        });
    </script>

    <style>
        /* ── SIDEBAR NAV ─────────────────────────────────────────── */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1rem;
            border-radius: 0.9rem;
            color: #9ca3af;
            font-weight: 600;
            font-size: 0.9rem;
            background: transparent;
            border: 1px solid transparent;
            transition: all 0.25s ease;
            min-height: 48px; /* touch target */
        }
        .nav-link i { width: 18px; text-align: center; flex-shrink: 0; }
        .nav-link:hover  { background: rgba(255,255,255,0.04); color: #fff; }
        .nav-link.active {
            background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(99,102,241,0.18));
            border-color: rgba(124,58,237,0.45);
            color: #fff;
        }

        /* ── BOTTOM NAV (mobile) ──────────────────────────────────── */
        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 10px 4px;
            color: #6b7280;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            min-height: 56px;
            text-decoration: none;
        }
        .bottom-nav-item.active { color: #8b5cf6; }
        .bottom-nav-item:active  { opacity: 0.7; }
        .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 0px); }

        /* ── GLASS CARD ──────────────────────────────────────────── */
        .glass-card {
            background: rgba(15,19,36,0.9);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 1.25rem;
            box-shadow: 0 20px 60px rgba(3,7,18,0.6);
        }
        @media (max-width: 640px) {
            .glass-card { border-radius: 1rem; }
        }

        /* ── BUTTONS ─────────────────────────────────────────────── */
        .admin-btn-primary,
        .admin-btn-ghost,
        .admin-btn-danger,
        .admin-btn-warning,
        .admin-btn-gold {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.6rem 0.9rem;
            border-radius: 0.85rem;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            min-height: 40px;
            white-space: nowrap;
            cursor: pointer;
            border: none;
        }
        @media (max-width: 640px) {
            .admin-btn-primary,
            .admin-btn-ghost,
            .admin-btn-danger,
            .admin-btn-warning,
            .admin-btn-gold {
                padding: 0.65rem 0.8rem;
                min-height: 44px; /* touch target maior no mobile */
                font-size: 0.82rem;
            }
        }
        .admin-btn-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .admin-btn-primary:hover  { box-shadow: 0 8px 20px rgba(124,58,237,0.4); }
        .admin-btn-primary:active { transform: scale(0.97); }
        .admin-btn-gold {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #1a1208;
        }
        .admin-btn-gold:hover  { box-shadow: 0 8px 20px rgba(245,158,11,0.4); }
        .admin-btn-ghost {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #cbd5e1;
        }
        .admin-btn-ghost:hover  { background: rgba(255,255,255,0.08); color: #fff; }
        .admin-btn-danger {
            background: rgba(239,68,68,0.12);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,0.3);
        }
        .admin-btn-danger:hover { background: rgba(239,68,68,0.22); color: #fff; }
        .admin-btn-warning {
            background: rgba(245,158,11,0.12);
            color: #fbbf24;
            border: 1px solid rgba(245,158,11,0.3);
        }
        .admin-btn-warning:hover { background: rgba(245,158,11,0.22); color: #fff; }

        /* ── INPUTS ──────────────────────────────────────────────── */
        .input-admin {
            width: 100%;
            padding: 0.7rem 0.9rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 0.9rem;
            color: #fff;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            color-scheme: dark;
            min-height: 44px; /* touch friendly */
        }
        .input-admin:focus {
            outline: none;
            border-color: rgba(124,58,237,0.6);
            box-shadow: 0 0 0 2px rgba(124,58,237,0.2);
        }
        select.input-admin { background-color: #0f1629; }
        .input-admin option { background-color: #0f1629; color: #e5e7eb; }
        .input-error { border-color: #f87171 !important; box-shadow: 0 0 0 1px #f87171; }

        /* ── STAT CARD ───────────────────────────────────────────── */
        .stat-card {
            background: linear-gradient(135deg, rgba(124,58,237,0.12), rgba(15,23,42,0.4));
            border-radius: 1.2rem;
            border: 1px solid rgba(124,58,237,0.2);
            padding: 1.2rem;
        }
        @media (max-width: 640px) {
            .stat-card { padding: 1rem; border-radius: 1rem; }
        }

        /* ── BADGES ──────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .badge-success { background: rgba(34,197,94,0.15);  color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
        .badge-warning { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .badge-danger  { background: rgba(239,68,68,0.15);  color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }
        .badge-info    { background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.3); }
        .badge-muted   { background: rgba(148,163,184,0.12);color: #cbd5e1; border: 1px solid rgba(148,163,184,0.2); }

        /* ── TABLES → cards no mobile ────────────────────────────── */
        @media (max-width: 768px) {
            .admin-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .admin-table-wrap table { min-width: 600px; }
        }

        /* ── FORMS ───────────────────────────────────────────────── */
        @media (max-width: 640px) {
            .form-grid-2 { grid-template-columns: 1fr !important; }
            .form-grid-3 { grid-template-columns: 1fr !important; }
        }
    </style>

    {{-- ══ MODAL DE CONFIRMAÇÃO GLOBAL ══ --}}
    <div id="adminConfirmOverlay"
         style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(3,7,18,0.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1rem;">
        <div id="adminConfirmBox"
             style="width:100%;max-width:420px;background:linear-gradient(135deg,#0f1629 0%,#111827 100%);border:1px solid rgba(255,255,255,0.08);border-radius:1.25rem;box-shadow:0 32px 80px rgba(0,0,0,0.7);overflow:hidden;transform:scale(0.92);opacity:0;transition:transform 0.2s ease,opacity 0.2s ease;">
            {{-- Stripe de cor --}}
            <div id="adminConfirmStripe" style="height:4px;width:100%;"></div>
            <div style="padding:1.75rem 1.75rem 1.5rem;">
                {{-- Ícone + Título --}}
                <div style="display:flex;align-items:flex-start;gap:1rem;margin-bottom:1rem;">
                    <div id="adminConfirmIconWrap"
                         style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px;">
                        <i id="adminConfirmIcon"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h3 id="adminConfirmTitle" style="font-size:1rem;font-weight:700;color:#f1f5f9;line-height:1.4;margin:0 0 0.35rem;"></h3>
                        <p id="adminConfirmMessage" style="font-size:0.875rem;color:#94a3b8;line-height:1.55;margin:0;"></p>
                    </div>
                </div>
                {{-- Input opcional (prompt) --}}
                <div id="adminConfirmInputWrap" style="display:none;margin-bottom:1rem;">
                    <input id="adminConfirmInput" type="text"
                           style="width:100%;padding:0.65rem 0.9rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;color:#fff;font-size:0.875rem;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='rgba(124,58,237,0.6)'"
                           onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                </div>
                {{-- Botões --}}
                <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.25rem;">
                    <button id="adminConfirmCancel"
                            style="padding:0.6rem 1.2rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;color:#94a3b8;font-size:0.85rem;font-weight:600;cursor:pointer;transition:all 0.2s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.09)';this.style.color='#fff'"
                            onmouseout="this.style.background='rgba(255,255,255,0.05)';this.style.color='#94a3b8'">
                        Cancelar
                    </button>
                    <button id="adminConfirmOk"
                            style="padding:0.6rem 1.4rem;border:none;border-radius:0.75rem;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;transition:all 0.2s;">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── AdminConfirm — modal de confirmação global ──────────────────────────
    const AdminConfirm = (() => {
        const overlay  = document.getElementById('adminConfirmOverlay');
        const box      = document.getElementById('adminConfirmBox');
        const stripe   = document.getElementById('adminConfirmStripe');
        const iconWrap = document.getElementById('adminConfirmIconWrap');
        const icon     = document.getElementById('adminConfirmIcon');
        const title    = document.getElementById('adminConfirmTitle');
        const message  = document.getElementById('adminConfirmMessage');
        const inputWrap= document.getElementById('adminConfirmInputWrap');
        const input    = document.getElementById('adminConfirmInput');
        const btnOk    = document.getElementById('adminConfirmOk');
        const btnCancel= document.getElementById('adminConfirmCancel');

        const variants = {
            danger:  { stripe:'#ef4444', icon:'fa-triangle-exclamation', iconBg:'rgba(239,68,68,0.15)',  iconColor:'#f87171', btnBg:'linear-gradient(135deg,#dc2626,#ef4444)', btnShadow:'rgba(239,68,68,0.35)' },
            warning: { stripe:'#f59e0b', icon:'fa-circle-exclamation',   iconBg:'rgba(245,158,11,0.15)', iconColor:'#fbbf24', btnBg:'linear-gradient(135deg,#d97706,#f59e0b)', btnShadow:'rgba(245,158,11,0.35)' },
            success: { stripe:'#22c55e', icon:'fa-circle-check',          iconBg:'rgba(34,197,94,0.15)',  iconColor:'#4ade80', btnBg:'linear-gradient(135deg,#16a34a,#22c55e)', btnShadow:'rgba(34,197,94,0.35)'  },
            info:    { stripe:'#7c3aed', icon:'fa-circle-question',        iconBg:'rgba(124,58,237,0.15)', iconColor:'#a78bfa', btnBg:'linear-gradient(135deg,#7c3aed,#8b5cf6)', btnShadow:'rgba(124,58,237,0.35)' },
        };

        let _resolve = null;

        function close(value) {
            box.style.transform = 'scale(0.92)';
            box.style.opacity   = '0';
            setTimeout(() => { overlay.style.display = 'none'; }, 180);
            if (_resolve) { _resolve(value); _resolve = null; }
        }

        btnCancel.addEventListener('click', () => close(false));
        btnOk.addEventListener('click', () => {
            const hasInput = inputWrap.style.display !== 'none';
            close(hasInput ? (input.value || '') : true);
        });
        overlay.addEventListener('click', e => { if (e.target === overlay) close(false); });
        document.addEventListener('keydown', e => {
            if (overlay.style.display === 'flex') {
                if (e.key === 'Escape') close(false);
                if (e.key === 'Enter' && document.activeElement !== input) btnOk.click();
            }
        });

        return {
            /**
             * @param {object} opts - { title, message, confirmText, cancelText, variant, withInput, inputPlaceholder, inputValue }
             * @returns {Promise<boolean|string>}
             */
            show(opts = {}) {
                const v = variants[opts.variant || 'info'];

                stripe.style.background    = v.stripe;
                iconWrap.style.background  = v.iconBg;
                iconWrap.style.color       = v.iconColor;
                icon.className             = `fas ${v.icon}`;
                title.textContent          = opts.title    || 'Confirmar ação';
                message.innerHTML          = opts.message  || '';
                btnOk.textContent          = opts.confirmText || 'Confirmar';
                btnOk.style.background     = v.btnBg;
                btnOk.style.boxShadow      = `0 4px 14px ${v.btnShadow}`;
                btnCancel.textContent      = opts.cancelText || 'Cancelar';

                if (opts.withInput) {
                    inputWrap.style.display = 'block';
                    input.placeholder       = opts.inputPlaceholder || '';
                    input.value             = opts.inputValue || '';
                    setTimeout(() => input.focus(), 220);
                } else {
                    inputWrap.style.display = 'none';
                }

                overlay.style.display = 'flex';
                requestAnimationFrame(() => {
                    box.style.transform = 'scale(1)';
                    box.style.opacity   = '1';
                });

                return new Promise(resolve => { _resolve = resolve; });
            }
        };
    })();
    </script>

    @stack('scripts')
</body>
</html>
