<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_is_absent_outside_production(): void
    {
        $this->get('/')->assertOk()->assertDontSee('googletagmanager');
    }

    public function test_tag_renders_when_forced(): void
    {
        config(['portal.ga_always' => true, 'portal.ga_measurement_id' => 'G-TEST123']);

        $this->get('/')->assertOk()->assertSee('gtag/js?id=G-TEST123', false)->assertSee('"G-TEST123"', false);
    }
}
