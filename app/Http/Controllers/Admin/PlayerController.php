<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $players = User::query()
            ->withCount(['favouriteGames', 'gameEvents'])
            ->withMax('gameEvents', 'occurred_at')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.players.index', ['players' => $players, 'q' => $q]);
    }

    public function activity(): View
    {
        return view('admin.players.activity', [
            'events' => GameEvent::with(['game:id,name,slug', 'user:id,name'])->orderByDesc('occurred_at')->paginate(100),
        ]);
    }
}
