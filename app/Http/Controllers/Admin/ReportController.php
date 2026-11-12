<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        $days = $this->days($request);

        return view('admin.reports.index', [
            'days' => $days,
            'ranges' => ReportService::RANGES,
            'kpis' => $this->reports->kpis($days),
            'daily' => $this->reports->daily($days),
            'games' => $this->reports->games($days),
            'players' => $this->reports->topPlayers($days),
        ]);
    }

    /** CSV download: ?report=games|daily&days=N */
    public function export(Request $request): StreamedResponse
    {
        $days = $this->days($request);
        $report = $request->query('report') === 'daily' ? 'daily' : 'games';

        [$head, $rows] = $report === 'daily'
            ? [['date', 'plays', 'launches', 'active_players', 'new_players'],
                $this->reports->daily($days)->map(fn ($r) => [$r->date->toDateString(), $r->plays, $r->launches, $r->active_players, $r->new_players])]
            : [['game', 'slug', 'enabled', 'plays', 'launches', 'players', 'minutes_played', 'best_score', 'favourites_added', 'favourites_total'],
                $this->reports->games($days)->map(fn ($r) => [$r->game->name, $r->game->slug, $r->game->is_enabled ? 'yes' : 'no', $r->plays, $r->launches, $r->players, $r->minutes, $r->best_score, $r->favourites_added, $r->favourites_total])];

        return response()->streamDownload(function () use ($head, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $head);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "nikniq-{$report}-{$days}d-".now()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function days(Request $request): int
    {
        $days = (int) $request->query('days', 30);

        return in_array($days, ReportService::RANGES, true) ? $days : 30;
    }
}
