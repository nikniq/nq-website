<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $id = $this->route('game')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9\-]+$/', Rule::unique('games', 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url', 'max:2048'],
            'thumbnail' => ['nullable', 'string', 'max:2048'],
            'genre' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'string', 'max:500'],
            'redirect_uris' => ['nullable', 'string', 'max:4000'],
            'is_public' => ['sometimes', 'boolean'],
            'is_enabled' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Normalise the textarea/CSV inputs into arrays the model expects. */
    public function gameAttributes(): array
    {
        $v = $this->validated();

        $lines = fn (?string $s) => collect(preg_split('/[\r\n,]+/', (string) $s))->map(fn ($x) => trim((string) $x))->filter()->unique()->values()->all();

        return [
            'name' => $v['name'],
            'slug' => $v['slug'],
            'description' => $v['description'] ?? null,
            'url' => $v['url'] ?? null,
            'thumbnail' => $v['thumbnail'] ?? null,
            'genre' => $v['genre'] ?? null,
            'tags' => $lines($v['tags'] ?? null),
            'redirect_uris' => $lines($v['redirect_uris'] ?? null),
            'is_public' => $this->boolean('is_public'),
            'is_enabled' => $this->boolean('is_enabled'),
            'is_featured' => $this->boolean('is_featured'),
            'sort_order' => (int) ($v['sort_order'] ?? 0),
            'notes' => $v['notes'] ?? null,
        ];
    }
}
