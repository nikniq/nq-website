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
            'screenshots' => ['nullable', 'string', 'max:8000'],
            'screenshot_files' => ['nullable', 'array', 'max:12'],
            'screenshot_files.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'genre' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'string', 'max:500'],
            'redirect_uris' => ['nullable', 'string', 'max:4000'],
            'is_public' => ['sometimes', 'boolean'],
            'is_enabled' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_dementia' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Store uploaded screenshots in public_html/images/games/{slug}/ and return their URLs. */
    public function storeScreenshots(string $slug): array
    {
        $urls = [];
        $dir = public_path('images/games/'.$slug);
        foreach ($this->file('screenshot_files', []) as $file) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = now()->format('YmdHis').'-'.\Illuminate\Support\Str::random(6).'.'.strtolower($file->extension());
            $file->move($dir, $name);
            $urls[] = '/images/games/'.$slug.'/'.$name;
        }

        return $urls;
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
            'screenshots' => array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', (string) ($v['screenshots'] ?? ''))))),
            'genre' => $v['genre'] ?? null,
            'tags' => $lines($v['tags'] ?? null),
            'redirect_uris' => $lines($v['redirect_uris'] ?? null),
            'is_public' => $this->boolean('is_public'),
            'is_enabled' => $this->boolean('is_enabled'),
            'is_featured' => $this->boolean('is_featured'),
            'is_dementia' => $this->boolean('is_dementia'),
            'sort_order' => (int) ($v['sort_order'] ?? 0),
            'notes' => $v['notes'] ?? null,
        ];
    }
}
