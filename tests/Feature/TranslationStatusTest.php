<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Support\TranslationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_fields_are_those_filled_in_portuguese_only(): void
    {
        $post = Post::factory()->create([
            'title' => ['pt' => 'Título', 'en' => 'Title'],
            'meta_title' => null,
        ]);

        $this->assertSame(['excerpt', 'content'], TranslationStatus::missingFields($post));
        $this->assertSame('Resumo, Conteúdo', TranslationStatus::labels(['excerpt', 'content']));

        $post->setTranslation('excerpt', 'en', 'Excerpt')->setTranslation('content', 'en', '<p>Text</p>')->save();

        $this->assertTrue(TranslationStatus::isComplete($post->refresh()));
    }
}
