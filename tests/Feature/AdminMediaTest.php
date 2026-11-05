<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function upload(array $files)
    {
        return $this->post('/api/admin/media', ['files' => $files], ['Accept' => 'application/json']);
    }

    public function test_any_panel_user_uploads_images_with_dimensions(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $response = $this->upload([UploadedFile::fake()->image('Foto da Equipa.jpg', 1200, 800)])->assertCreated();

        $media = Media::first();
        $this->assertSame(1200, $media->width);
        $this->assertSame(800, $media->height);
        $this->assertSame('foto-da-equipa', $media->name);
        $this->assertSame('Foto da Equipa', $media->title);
        $this->assertStringStartsWith('media/'.now()->format('Y/m').'/', $media->path);
        Storage::disk('public')->assertExists($media->path);
        $response->assertJsonPath('data.0.is_image', true)->assertJsonPath('data.0.thumbnail_url', fn ($url) => str_contains($url, '/img/'));
    }

    public function test_same_name_gets_a_unique_file_name(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->upload([UploadedFile::fake()->image('logo.png'), UploadedFile::fake()->image('logo.png')])->assertCreated();

        $this->assertEqualsCanonicalizing(['logo', 'logo-2'], Media::pluck('name')->all());
    }

    public function test_svg_dimensions_come_from_the_view_box_and_unsafe_svg_is_refused(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $safe = UploadedFile::fake()->createWithContent('icone.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 32"><rect width="64" height="32"/></svg>');
        $this->upload([$safe])->assertCreated()->assertJsonPath('data.0.width', 64)->assertJsonPath('data.0.height', 32);

        $unsafe = UploadedFile::fake()->createWithContent('mau.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>');
        $this->upload([$unsafe])->assertUnprocessable()->assertJsonValidationErrors('files.0');

        $this->upload([UploadedFile::fake()->create('script.php', 10)])->assertUnprocessable();
        $this->assertSame(1, Media::count());
    }

    public function test_library_lists_searches_and_filters_by_type(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $this->upload([UploadedFile::fake()->image('praia.jpg'), UploadedFile::fake()->create('relatorio.pdf', 20, 'application/pdf')]);

        $this->getJson('/api/admin/media')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('upload.extensions.0', 'jpg');
        $this->getJson('/api/admin/media?search=praia')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'praia');
        $this->getJson('/api/admin/media?filter[type]=document')->assertJsonCount(1, 'data')->assertJsonPath('data.0.ext', 'pdf');

        $id = Media::where('name', 'praia')->value('id');
        $this->getJson('/api/admin/media?ids='.$id)->assertJsonCount(1, 'data');
    }

    public function test_editing_and_deleting_need_the_media_area(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->upload([UploadedFile::fake()->image('foto.jpg')]);
        $media = Media::first();

        $blogOnly = User::factory()->create();
        $blogOnly->assignRole(Role::create(['name' => 'Só blog', 'guard_name' => 'web'])->givePermissionTo('blog'));

        $this->actingAs($blogOnly)->putJson("/api/admin/media/{$media->id}", ['alt' => 'x'])->assertForbidden();
        $this->deleteJson("/api/admin/media/{$media->id}")->assertForbidden();

        $this->actingAs(User::factory()->manager()->create())
            ->putJson("/api/admin/media/{$media->id}", ['alt' => 'Equipa na sede', 'title' => 'Equipa'])
            ->assertOk()
            ->assertJsonPath('data.alt', 'Equipa na sede');

        $this->deleteJson("/api/admin/media/{$media->id}")->assertNoContent();
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_detail_lists_where_the_file_is_used(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->upload([UploadedFile::fake()->image('capa.jpg')]);
        $media = Media::first();
        Post::factory()->create(['title' => ['pt' => 'Artigo com capa'], 'cover_image_id' => $media->id]);

        $this->getJson("/api/admin/media/{$media->id}")
            ->assertOk()
            ->assertJsonPath('usage.0', 'Artigo "Artigo com capa"');
    }

    public function test_bulk_delete(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->upload([UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')]);

        $ids = Media::whereIn('name', ['a', 'b'])->pluck('id')->all();
        $this->postJson('/api/admin/media/bulk-delete', ['ids' => $ids])->assertNoContent();

        $this->assertSame(['c'], Media::pluck('name')->all());
    }

    public function test_link_options_list_site_routes_and_panel_pages(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $this->getJson('/api/admin/links/options')
            ->assertOk()
            ->assertJsonPath('routes.0', ['value' => 'home', 'label' => 'Início'])
            ->assertJsonStructure(['routes', 'pages']);
    }

    public function test_lists_sort_and_ignore_unknown_columns(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'Zé']));
        User::factory()->manager()->create(['name' => 'Ana']);

        $this->getJson('/api/admin/users?sort=-name')->assertJsonPath('data.0.name', 'Zé');
        $this->getJson('/api/admin/users?sort=password')->assertOk()->assertJsonPath('data.0.name', 'Ana');
    }
}
