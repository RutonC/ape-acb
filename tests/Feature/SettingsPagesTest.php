<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_general_settings_start_with_confirmed_ape_values(): void
    {
        $settings = app(GeneralSettings::class);

        $this->assertSame('APE, Agência Privada de Emprego', $settings->site_name);
        $this->assertSame(['+258 23 326 246', '+258 84 373 2764'], $settings->phones);
        $this->assertSame('info.ape@acb.co.mz', $settings->email);
    }
}
