<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_paths_serve_the_react_application(): void
    {
        foreach (['/admin', '/admin/mensagens', '/admin/blog/artigos/3'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<div id="admin"></div>', false)
                ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }
    }

    public function test_admin_is_not_treated_as_a_cms_page_slug(): void
    {
        $this->get('/admin')->assertOk()->assertDontSee('site-header', false);
    }

    public function test_session_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_session_endpoint_returns_the_signed_in_user(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Ana Admin']);

        $this->actingAs($user)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'Ana Admin')
            ->assertJsonMissingPath('data.password');
    }
}
