<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\User;
use Database\Seeders\BlogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    public function test_only_published_and_due_posts_are_listed(): void
    {
        Post::factory()->create(['title' => ['pt' => 'Artigo publicado']]);
        Post::factory()->draft()->create(['title' => ['pt' => 'Artigo rascunho']]);
        $scheduled = Post::factory()->scheduled()->create(['title' => ['pt' => 'Artigo agendado']]);

        $this->get('/noticias')
            ->assertOk()
            ->assertSee('Artigo publicado')
            ->assertDontSee('Artigo rascunho')
            ->assertDontSee('Artigo agendado');

        $this->get($scheduled->url())->assertNotFound();

        $this->travel(8)->days();
        $this->get($scheduled->url())->assertOk()->assertSee('Artigo agendado');
    }

    public function test_post_page_shows_meta_tags_and_share_links(): void
    {
        $category = PostCategory::factory()->create(['slug' => 'seguranca', 'name' => ['pt' => 'Segurança']]);
        $tag = PostTag::factory()->create(['slug' => 'pme', 'name' => ['pt' => 'PME']]);
        $author = User::factory()->create(['name' => 'Gerson Houane']);
        $post = Post::factory()->create([
            'slug' => 'seguranca-pme',
            'title' => ['pt' => 'Segurança para PME'],
            'content' => ['pt' => '<h2>Cópias de segurança</h2><p>Texto do artigo.</p>'],
            'post_category_id' => $category->id,
            'author_id' => $author->id,
            'published_at' => '2026-09-20 10:00:00',
        ]);
        $post->tags()->attach($tag);

        $shareUrl = rawurlencode($post->url());

        $this->get('/noticias/seguranca-pme')
            ->assertOk()
            ->assertSee('Cópias de segurança')
            ->assertSee('20 de Setembro de 2026')
            ->assertSee('Gerson Houane')
            ->assertSee(url('/noticias/categoria/seguranca'), false)
            ->assertSee(url('/noticias/etiqueta/pme'), false)
            ->assertSee("https://www.facebook.com/sharer/sharer.php?u={$shareUrl}", false)
            ->assertSee("https://www.linkedin.com/sharing/share-offsite/?url={$shareUrl}", false)
            ->assertSee('https://wa.me/?text=', false)
            ->assertSeeInOrder(['Notícias', 'Segurança', 'Segurança para PME']);
    }

    public function test_category_tag_and_search_archives(): void
    {
        $category = PostCategory::factory()->create(['slug' => 'gestao']);
        $tag = PostTag::factory()->create(['slug' => 'custos', 'name' => ['pt' => 'custos']]);

        Post::factory()->create(['title' => ['pt' => 'Quanto custa um sistema'], 'post_category_id' => $category->id])->tags()->attach($tag);
        Post::factory()->create(['title' => ['pt' => 'Aplicações offline']]);

        // A barra lateral ("Artigos recentes") mostra todos os artigos; conta-se só a listagem.
        $cards = fn ($response): int => substr_count($response->getContent(), 'class="post-card"');

        $response = $this->get('/noticias/categoria/gestao')->assertOk()->assertSee('Quanto custa um sistema');
        $this->assertSame(1, $cards($response));

        $response = $this->get('/noticias/etiqueta/custos')->assertOk()->assertSee('#custos');
        $this->assertSame(1, $cards($response));

        $response = $this->get('/noticias?q=offline')->assertOk()->assertSee('Pesquisa: offline')->assertSee('Aplicações offline');
        $this->assertSame(1, $cards($response));

        $this->assertSame(2, $cards($this->get('/noticias')));
        $this->get('/noticias?q=inexistente')->assertSee('Nenhuma notícia encontrada para esta pesquisa.');
        $this->get('/noticias/categoria/nao-existe')->assertNotFound();
    }

    public function test_english_version_falls_back_to_portuguese_content(): void
    {
        Post::factory()->create([
            'slug' => 'offline',
            'title' => ['pt' => 'Aplicações offline', 'en' => 'Offline apps'],
            'content' => ['pt' => '<p>Conteúdo em português.</p>'],
        ]);

        $this->get('/en/news/offline')
            ->assertOk()
            ->assertSee('Offline apps')
            ->assertSee('Conteúdo em português.')
            ->assertSee('Share');
    }

    public function test_draft_preview_requires_signature(): void
    {
        $post = Post::factory()->draft()->create(['title' => ['pt' => 'Rascunho secreto']]);

        $this->get($post->previewUrl())->assertOk()->assertSee('Rascunho secreto')->assertSee('noindex', false);
        $this->get(route('posts.preview', ['post' => $post, 'locale' => 'pt']))->assertForbidden();
    }

    public function test_blog_block_lists_latest_posts(): void
    {
        Post::factory()->create(['title' => ['pt' => 'Artigo mais recente'], 'published_at' => now()->subHour()]);

        Page::factory()->withBlocks([
            ['type' => 'blog', 'data' => ['is_active' => true, 'title' => 'Do nosso blog', 'limit' => 3, 'button_label' => 'Ver todos os artigos']],
        ])->create(['slug' => 'novidades']);

        $this->get('/novidades')->assertSee('Do nosso blog')->assertSee('Artigo mais recente')->assertSee(url('/noticias'), false);
    }
}
