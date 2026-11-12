<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_branded_error_pages_render(): void
    {
        foreach ([403 => 'Not allowed', 419 => 'Page expired', 429 => 'Slow down', 500 => 'Something broke', 503 => 'Back shortly'] as $code => $text) {
            Route::get("/_err/$code", fn () => abort($code));
            $this->get("/_err/$code")->assertStatus($code)->assertSee($text)->assertSee('Browse games');
        }
    }
}
