<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlayerManagementController extends Controller
{
    public function index(Request $request)
    {
        $players = Player::with('sport')
            ->when($request->filled('sport_id'), fn($q) => $q->where('sport_id', $request->sport_id))
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(12);
        $sports = Sport::orderBy('name')->get();

        return view('admin.players.index', compact('players', 'sports'));
    }

    public function create()
    {
        $sports = Sport::orderBy('name')->get();

        return view('admin.players.create', compact('sports'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePlayer($request);
        $data['slug'] = Str::slug($request->name . '-' . uniqid());
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $this->uploadPhoto($request);
        }

        Player::create($data);

        return redirect()->route('admin.players.index')->with('success', 'Jogador cadastrado com sucesso');
    }

    public function edit(Player $player)
    {
        $sports = Sport::orderBy('name')->get();

        return view('admin.players.edit', compact('player', 'sports'));
    }

    public function update(Request $request, Player $player)
    {
        $data = $this->validatePlayer($request, $player->id);
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $this->uploadPhoto($request);
        }

        $player->update($data);

        return redirect()->route('admin.players.index')->with('success', 'Jogador atualizado com sucesso');
    }

    public function confirmDelete(Player $player)
    {
        return view('admin.players.delete', compact('player'));
    }

    public function destroy(Player $player)
    {
        $player->delete();

        return redirect()->route('admin.players.index')->with('success', 'Jogador removido com sucesso');
    }

    public function apiIndex(Request $request)
    {
        $players = Player::with('sport')
            ->when($request->filled('sport_id'), fn($q) => $q->where('sport_id', $request->sport_id))
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $players
        ]);
    }

    private function validatePlayer(Request $request, ?int $playerId = null): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'sport_id'    => 'required|exists:sports,id',
            'bio'         => 'nullable|string',
            'photo'       => 'nullable|image|max:5120',
            'birth_date'  => 'nullable|date',
            'nationality' => 'nullable|string|size:3',
            'rating'      => 'nullable|numeric|min:0'
        ]);
    }

    /**
     * Faz upload da foto do jogador em storage/app/uploads/players/.
     * Servido via rota /uploads/{path} — funciona em qualquer hospedagem
     * sem depender de storage:link ou public_path.
     */
    private function uploadPhoto(Request $request): string
    {
        $ext      = $request->file('photo')->getClientOriginalExtension();
        $filename = 'player_' . uniqid() . '.' . $ext;
        $dir      = storage_path('app/uploads/players');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $request->file('photo')->move($dir, $filename);

        return 'uploads/players/' . $filename;
    }
}
