<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBlogTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'slug' => 'transformacao-digital',
            'title' => ['pt' => 'Transformação digital'],
            'excerpt' => ['pt' => 'Por onde começar.'],
            'content' => ['pt' => '<p>Texto do artigo.</p>', 'en' => '<p></p>'],
            'status' => 'draft',
            'published_at' => null,
            'tags' => [],
        ], $overrides);
    }

    public function test_admin_creates_edits_and_schedules_posts(): void
    {
        $author = User::factory()->manager()->create();
        $this->actingAs($author);
        $category = PostCategory::factory()->create();
        $tag = PostTag::factory()->create();

        $id = $this->postJson('/api/admin/posts', $this->payload([
            'post_category_id' => $category->id,
            'tags' => [$tag->id],
            'author_id' => $author->id,
        ]))->assertCreated()
            ->assertJsonPath('data.state', 'draft')
            ->assertJsonPath('data.tags', [$tag->id])
            ->assertJsonPath('data.content.en', null)
            ->json('data.id');

        // Agendado: publicado com data futura não aparece no site.
        $this->putJson("/api/admin/posts/{$id}", $this->payload(['status' => 'published', 'published_at' => now()->addDays(2)->format('Y-m-d\TH:i')]))
            ->assertOk()
            ->assertJsonPath('data.state', 'scheduled')
            ->assertJsonPath('data.is_visible', false)
            ->assertJsonPath('data.tags', []);
        $this->assertFalse(Post::published()->whereKey($id)->exists());

        // Publicar sem data usa o momento actual.
        $this->putJson("/api/admin/posts/{$id}", $this->payload(['status' => 'published']))
            ->assertJsonPath('data.state', 'published')
            ->assertJsonPath('data.is_visible', true);
        $this->assertNotNull(Post::find($id)->published_at);
    }

    public function test_validation_of_slug_and_required_fields(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Post::factory()->create(['slug' => 'existente']);

        $this->postJson('/api/admin/posts', $this->payload(['slug' => 'existente']))->assertJsonValidationErrors('slug');
        $this->postJson('/api/admin/posts', $this->payload(['slug' => 'categoria']))->assertJsonValidationErrors('slug');
        $this->postJson('/api/admin/posts', $this->payload(['content' => ['pt' => '']]))->assertJsonValidationErrors('content.pt');
        $this->postJson('/api/admin/posts', $this->payload(['status' => 'arquivado']))->assertJsonValidationErrors('status');
    }

    public function test_list_filters_by_state_and_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $category = PostCategory::factory()->create();
        Post::factory()->create(['status' => PageStatus::Draft, 'post_category_id' => $category->id]);
        Post::factory()->create(['status' => PageStatus::Published, 'published_at' => now()->subDay()]);
        Post::factory()->create(['status' => PageStatus::Published, 'published_at' => now()->addDay()]);

        $this->getJson('/api/admin/posts')->assertJsonCount(3, 'data');
        $this->getJson('/api/admin/posts?filter[state]=draft')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/posts?filter[state]=scheduled')->assertJsonCount(1, 'data')->assertJsonPath('data.0.state', 'scheduled');
        $this->getJson('/api/admin/posts?filter[state]=published')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/posts?filter[category]='.$category->id)->assertJsonCount(1, 'data');
    }

    public function test_trash_restore_and_force_delete(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        [$a, $b] = Post::factory()->count(2)->create();

        $this->deleteJson("/api/admin/posts/{$a->id}")->assertNoContent();
        $this->assertSoftDeleted($a);
        $this->getJson('/api/admin/posts')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/posts?filter[trashed]=only')->assertJsonCount(1, 'data')->assertJsonPath('data.0.state', 'trashed');

        $this->postJson("/api/admin/posts/{$a->id}/restore")->assertOk();
        $this->assertNotSoftDeleted($a);

        $this->postJson('/api/admin/posts/bulk', ['action' => 'trash', 'ids' => [$a->id, $b->id]])->assertNoContent();
        $this->postJson('/api/admin/posts/bulk', ['action' => 'restore', 'ids' => [$b->id]])->assertNoContent();
        $this->deleteJson("/api/admin/posts/{$a->id}/force")->assertNoContent();

        $this->assertModelMissing($a);
        $this->assertNotSoftDeleted($b);
    }

    public function test_preview_gives_a_signed_url_that_shows_drafts(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $post = Post::factory()->create(['status' => PageStatus::Draft, 'title' => ['pt' => 'Rascunho secreto']]);

        $url = $this->getJson("/api/admin/posts/{$post->id}/preview?locale=pt")->assertOk()->json('url');

        $this->get($url)->assertOk()->assertSee('Rascunho secreto');
        $this->get(route('posts.preview', ['post' => $post->id, 'locale' => 'pt']))->assertForbidden();
    }

    public function test_categories_and_tags(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $cat = $this->postJson('/api/admin/blog/categories', ['slug' => 'gestao', 'name' => ['pt' => 'Gestão', 'en' => 'Management']])
            ->assertCreated()
            ->assertJsonPath('data.posts_count', 0)
            ->json('data.id');
        $this->postJson('/api/admin/blog/categories', ['slug' => 'gestao', 'name' => ['pt' => 'Outra']])->assertJsonValidationErrors('slug');
        $this->putJson("/api/admin/blog/categories/{$cat}", ['slug' => 'gestao-empresarial', 'name' => ['pt' => 'Gestão empresarial']])
            ->assertOk()
            ->assertJsonPath('data.name.en', null);

        // Etiqueta criada só com o nome (a partir do formulário do artigo).
        $tag = $this->postJson('/api/admin/blog/tags', ['name' => ['pt' => 'Transformação Digital']])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'transformacao-digital')
            ->json('data.id');

        Post::factory()->create(['post_category_id' => $cat])->tags()->attach($tag);
        $this->getJson('/api/admin/blog/categories')->assertJsonPath('data.0.posts_count', 1);
        $this->getJson('/api/admin/blog/tags')->assertJsonPath('data.0.posts_count', 1);

        $this->deleteJson("/api/admin/blog/tags/{$tag}")->assertNoContent();
        $this->deleteJson("/api/admin/blog/categories/{$cat}")->assertNoContent();
        $this->assertNull(Post::first()->post_category_id);
        $this->getJson('/api/admin/blog/outra-coisa')->assertNotFound();
    }

    public function test_blog_needs_its_area(): void
    {
        $this->seed(RolesSeeder::class);
        $role = Role::create(['name' => 'Só equipa', 'guard_name' => 'web'])->givePermissionTo('team');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->getJson('/api/admin/posts')->assertForbidden();
        $this->getJson('/api/admin/blog/categories')->assertForbidden();
        $this->getJson('/api/admin/options/authors')->assertForbidden();
    }
}
