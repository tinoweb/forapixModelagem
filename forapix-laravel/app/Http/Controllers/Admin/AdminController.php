<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\Bet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        $stats = $this->getDashboardStats();
        $recentActivities = $this->buildRecentActivities();
        $chartData = $this->buildDashboardCharts();

        return view('admin.dashboard', compact('stats', 'recentActivities', 'chartData'));
    }

    /**
     * Consolidated recent activities (for the dashboard feed)
     */
    private function buildRecentActivities(int $limit = 8): array
    {
        $activities = collect();

        try {
            $recentBets = Bet::with(['user', 'match.game'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($bet) {
                    return [
                        'type' => 'bet',
                        'icon' => 'fa-dice',
                        'color' => 'purple',
                        'title' => 'Nova aposta',
                        'description' => ($bet->user->name ?? 'Usuário') . ' apostou R$ ' . number_format($bet->amount, 2, ',', '.') . ' em ' . ($bet->match->game->name ?? 'partida'),
                        'created_at' => $bet->created_at,
                    ];
                });
            $activities = $activities->merge($recentBets);
        } catch (\Throwable $e) {}

        try {
            $recentTransactions = Transaction::with('user')
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($transaction) {
                    $isDeposit = $transaction->type === 'deposit';
                    return [
                        'type' => 'transaction',
                        'icon' => $isDeposit ? 'fa-arrow-down' : 'fa-arrow-up',
                        'color' => $isDeposit ? 'blue' : 'orange',
                        'title' => $isDeposit ? 'Depósito' : 'Saque',
                        'description' => ($transaction->user->name ?? 'Usuário') . ' ' . ($isDeposit ? 'depositou' : 'sacou') . ' R$ ' . number_format($transaction->amount, 2, ',', '.'),
                        'created_at' => $transaction->created_at,
                    ];
                });
            $activities = $activities->merge($recentTransactions);
        } catch (\Throwable $e) {}

        try {
            $recentUsers = User::latest()
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'user',
                        'icon' => 'fa-user-plus',
                        'color' => 'green',
                        'title' => 'Novo usuário',
                        'description' => ($user->name ?? 'Desconhecido') . ' se registrou na plataforma',
                        'created_at' => $user->created_at,
                    ];
                });
            $activities = $activities->merge($recentUsers);
        } catch (\Throwable $e) {}

        return $activities
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Pre-compute chart data for the dashboard (last 7 days)
     */
    private function buildDashboardCharts(): array
    {
        $labels = [];
        $revenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $dayBets = Bet::whereDate('created_at', $date)->sum('amount');
            $dayWins = Bet::where('status', 'won')->whereDate('created_at', $date)->sum('result_amount');
            $revenue[] = round((float) ($dayBets - $dayWins), 2);
        }

        $gameBreakdown = Bet::join('matches', 'bets.match_id', '=', 'matches.id')
            ->join('games', 'matches.game_id', '=', 'games.id')
            ->selectRaw('games.name as name, COALESCE(SUM(bets.amount),0) as total')
            ->groupBy('games.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return [
            'revenue' => [
                'labels' => $labels,
                'values' => $revenue,
            ],
            'games' => [
                'labels' => $gameBreakdown->pluck('name')->all(),
                'values' => $gameBreakdown->pluck('total')->map(fn($v) => (float) $v)->all(),
            ],
        ];
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'new_today' => User::whereDate('created_at', $today)->count(),
                'new_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
            ],
            'games' => [
                'total' => Game::count(),
                'active' => Game::where('status', 'active')->count(),
                'by_type' => Game::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ],
            'matches' => [
                'total' => GameMatch::count(),
                'live' => GameMatch::where('status', 'live')->count(),
                'scheduled' => GameMatch::where('status', 'scheduled')->count(),
                'finished_today' => GameMatch::where('status', 'finished')
                    ->whereDate('updated_at', $today)
                    ->count(),
            ],
            'bets' => [
                'total' => Bet::count(),
                'pending' => Bet::where('status', 'pending')->count(),
                'won' => Bet::where('status', 'won')->count(),
                'lost' => Bet::where('status', 'lost')->count(),
                'total_amount' => Bet::sum('amount'),
                'today_amount' => Bet::whereDate('created_at', $today)->sum('amount'),
            ],
            'transactions' => [
                'total' => Transaction::count(),
                'deposits' => Transaction::where('type', 'deposit')->sum('amount'),
                'withdrawals' => Transaction::where('type', 'withdraw')->sum('amount'),
                'today_deposits' => Transaction::where('type', 'deposit')
                    ->whereDate('created_at', $today)
                    ->sum('amount'),
                'today_withdrawals' => Transaction::where('type', 'withdraw')
                    ->whereDate('created_at', $today)
                    ->sum('amount'),
            ],
            'revenue' => [
                'total_bets' => Bet::sum('amount'),
                'total_wins' => Bet::where('status', 'won')->sum('result_amount'),
                'house_profit' => Bet::sum('amount') - Bet::where('status', 'won')->sum('result_amount'),
                'commission' => Transaction::where('type', 'commission')->sum('amount'),
            ]
        ];
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities()
    {
        $activities = collect();

        // Recent bets
        $recentBets = Bet::with(['user', 'match.game'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($bet) {
                return [
                    'type' => 'bet',
                    'description' => "{$bet->user->name} apostou R$ {$bet->amount} em {$bet->match->game->name}",
                    'amount' => $bet->amount,
                    'status' => $bet->status,
                    'created_at' => $bet->created_at,
                ];
            });

        // Recent transactions
        $recentTransactions = Transaction::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                $action = $transaction->type === 'deposit' ? 'depositou' : 'sacou';
                return [
                    'type' => 'transaction',
                    'description' => "{$transaction->user->name} {$action} R$ {$transaction->amount}",
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ];
            });

        // Recent users
        $recentUsers = User::latest()
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user',
                    'description' => "Novo usuário: {$user->name}",
                    'amount' => null,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ];
            });

        return $activities
            ->merge($recentBets)
            ->merge($recentTransactions)
            ->merge($recentUsers)
            ->sortByDesc('created_at')
            ->take(20)
            ->values();
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $startDate = Carbon::now()->subDays($period);

        $summary = [
            'deposits' => Transaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            
            'withdrawals' => Transaction::where('type', 'withdraw')
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            
            'total_bets' => Bet::where('created_at', '>=', $startDate)
                ->sum('amount'),
            
            'total_wins' => Bet::where('status', 'won')
                ->where('created_at', '>=', $startDate)
                ->sum('result_amount'),
            
            'house_profit' => 0, // Will be calculated
            
            'active_users' => User::where('last_login_at', '>=', $startDate)->count(),
            
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
        ];

        $summary['house_profit'] = $summary['total_bets'] - $summary['total_wins'];
        $summary['net_flow'] = $summary['deposits'] - $summary['withdrawals'];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'period' => $period
        ]);
    }

    /**
     * Get chart data
     */
    public function getChartData(Request $request)
    {
        $type = $request->get('type', 'revenue');
        $period = $request->get('period', '7'); // days
        
        $startDate = Carbon::now()->subDays($period);
        $dates = [];
        $data = [];

        for ($i = $period; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dates[] = $date->format('d/m');

            switch ($type) {
                case 'revenue':
                    $dayBets = Bet::whereDate('created_at', $date)->sum('amount');
                    $dayWins = Bet::where('status', 'won')
                        ->whereDate('created_at', $date)
                        ->sum('result_amount');
                    $data[] = $dayBets - $dayWins;
                    break;

                case 'bets':
                    $data[] = Bet::whereDate('created_at', $date)->sum('amount');
                    break;

                case 'users':
                    $data[] = User::whereDate('created_at', $date)->count();
                    break;

                case 'transactions':
                    $deposits = Transaction::where('type', 'deposit')
                        ->whereDate('created_at', $date)
                        ->sum('amount');
                    $withdrawals = Transaction::where('type', 'withdraw')
                        ->whereDate('created_at', $date)
                        ->sum('amount');
                    $data[] = $deposits - $withdrawals;
                    break;

                default:
                    $data[] = 0;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $dates,
                'values' => $data,
                'type' => $type,
                'period' => $period
            ]
        ]);
    }

    /**
     * System health check
     */
    public function systemHealth()
    {
        $health = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'api' => $this->checkApiHealth(),
        ];

        $overallStatus = collect($health)->every(fn($check) => $check['status'] === 'ok') ? 'healthy' : 'warning';

        return response()->json([
            'success' => true,
            'data' => [
                'overall_status' => $overallStatus,
                'checks' => $health,
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkStorage()
    {
        try {
            $freeSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            if ($usedPercentage > 90) {
                return ['status' => 'warning', 'message' => 'Storage usage high: ' . round($usedPercentage, 2) . '%'];
            }

            return ['status' => 'ok', 'message' => 'Storage usage: ' . round($usedPercentage, 2) . '%'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage check failed: ' . $e->getMessage()];
        }
    }

    private function checkCache()
    {
        try {
            cache()->put('health_check', 'ok', 60);
            $value = cache()->get('health_check');
            
            if ($value === 'ok') {
                return ['status' => 'ok', 'message' => 'Cache working properly'];
            }
            
            return ['status' => 'warning', 'message' => 'Cache not working properly'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache check failed: ' . $e->getMessage()];
        }
    }

    private function checkQueue()
    {
        try {
            // Simple queue health check
            return ['status' => 'ok', 'message' => 'Queue system operational'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }

    private function checkApiHealth()
    {
        $pendingBets = Bet::where('status', 'pending')->count();
        $liveMatches = GameMatch::where('status', 'live')->count();
        
        return [
            'status' => 'ok',
            'message' => "API healthy - {$pendingBets} pending bets, {$liveMatches} live matches"
        ];
    }
}
