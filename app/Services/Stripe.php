<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Minimal Stripe REST client (no SDK): form-encoded requests and webhook signatures. */
class Stripe
{
    public function configured(): bool
    {
        return (string) config('billing.stripe.secret') !== '';
    }

    /** @return array<string, mixed> */
    public function post(string $path, array $params = []): array
    {
        return $this->send('post', $path, $params);
    }

    /** @return array<string, mixed> */
    public function get(string $path, array $params = []): array
    {
        return $this->send('get', $path, $params);
    }

    /** @return array<string, mixed> */
    public function delete(string $path): array
    {
        return $this->send('delete', $path, []);
    }

    private function send(string $method, string $path, array $params): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Stripe is not configured (STRIPE_SECRET).');
        }
        $req = Http::withToken((string) config('billing.stripe.secret'))->acceptJson()->timeout(20)
            ->withHeaders(['Stripe-Version' => '2024-06-20']);
        $url = rtrim((string) config('billing.stripe.api'), '/').'/'.ltrim($path, '/');
        $res = $method === 'get' ? $req->get($url, $params) : ($method === 'delete' ? $req->delete($url) : $req->asForm()->post($url, $params));
        if ($res->failed()) {
            throw new RuntimeException('Stripe '.$path.': '.($res->json('error.message') ?? $res->status()));
        }

        return $res->json() ?? [];
    }

    /**
     * Verify a Stripe-Signature header (v1 HMAC-SHA256 of "t.payload") and decode the event.
     *
     * @return array<string, mixed>|null
     */
    public static function verifyWebhook(string $payload, ?string $header, ?string $secret, int $tolerance = 300): ?array
    {
        if (! $secret || ! $header) {
            return null;
        }
        $t = null;
        $sigs = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't') {
                $t = (int) $v;
            } elseif ($k === 'v1') {
                $sigs[] = $v;
            }
        }
        if (! $t || ! $sigs || abs(time() - $t) > $tolerance) {
            return null;
        }
        $expected = hash_hmac('sha256', $t.'.'.$payload, $secret);
        foreach ($sigs as $sig) {
            if (hash_equals($expected, $sig)) {
                $event = json_decode($payload, true);

                return is_array($event) ? $event : null;
            }
        }

        return null;
    }
}
