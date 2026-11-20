<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Throwable;

/** Append-only account and admin log (sign-ins, edits, suspensions, impersonation). */
class Audit
{
    public const ACTIONS = [
        'login' => 'Logged in', 'login_failed' => 'Failed login', 'logout' => 'Logged out',
        'player_updated' => 'Player edited', 'player_suspended' => 'Player suspended', 'player_restored' => 'Player restored',
        'player_deleted' => 'Player deleted', 'admin_granted' => 'Admin granted', 'admin_revoked' => 'Admin revoked',
        'tokens_revoked' => 'Game sign-ins revoked', 'impersonation_started' => 'Viewed as player', 'impersonation_stopped' => 'Stopped viewing as player',
    ];

    public static function log(string $action, ?User $subject = null, array $details = [], ?User $actor = null, ?string $label = null): void
    {
        try {
            $request = request();
            AuditLog::create([
                'actor_id' => ($actor ?? auth()->user())?->id,
                'subject_id' => $subject?->exists ? $subject->id : null,
                'subject_label' => $label ?? $subject?->email,
                'action' => $action,
                'details' => $details ?: null,
                'ip' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e); // logging must never break the request
        }
    }

    public static function label(string $action): string
    {
        return self::ACTIONS[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
