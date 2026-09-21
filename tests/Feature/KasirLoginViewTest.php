<?php

namespace Tests\Feature;

use Tests\TestCase;

class KasirLoginViewTest extends TestCase
{
    public function test_kasir_login_page_renders_with_brand_logo_and_portal_identity(): void
    {
        $response = $this->get(route('kasir.login'));

        $response->assertStatus(200);
        $response->assertSee('logo-mark.svg');
        $response->assertSee(config('cafe.name'));
        $response->assertSee('Staff & Cashier Portal', false);
        $response->assertSee('Masuk ke Terminal');
        $response->assertSee('Masuk Terminal');
    }

    public function test_kasir_login_page_renders_daily_motivational_quote(): void
    {
        $response = $this->get(route('kasir.login'));

        $response->assertStatus(200);
        $response->assertSee('Motivasi Shift Hari Ini');
        $response->assertSee('Motivasi Hari Ini');
        $response->assertSee('Semangat Bertugas');
    }
}
