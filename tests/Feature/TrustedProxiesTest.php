<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    use RefreshDatabase;

    private const NGROK = [
        'X-Forwarded-For' => '203.0.113.10',
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'acb.ngrok-free.app',
        'X-Forwarded-Port' => '443',
    ];

    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function test_proxy_headers_are_ignored_by_default(): void
    {
        $this->withHeaders(self::NGROK)->get('/')
            ->assertOk()
            ->assertDontSee('https://acb.ngrok-free.app', false);
    }

    public function test_trusted_proxy_makes_links_and_assets_use_the_public_https_address(): void
    {
        config(['site.trusted_proxies' => '*']);
        (new AppServiceProvider($this->app))->boot();

        $this->withHeaders(self::NGROK)->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://acb.ngrok-free.app">', false)
            ->assertSee('https://acb.ngrok-free.app/images/og-default.jpg', false)
            ->assertDontSee('http://acb.ngrok-free.app', false);
    }
}
