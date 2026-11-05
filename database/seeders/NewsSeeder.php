<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Post;
use App\Support\SeedMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Notícias migradas de ape.acb.co.mz (database/data/ape.json), como foram
 * publicadas. Só cria as que ainda não existem: as edições no painel prevalecem.
 */
class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode((string) file_get_contents(database_path('data/ape.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['news'] as $item) {
            if (Post::withTrashed()->where('slug', $item['slug'])->exists()) {
                continue;
            }

            Post::create([
                'slug' => $item['slug'],
                'title' => ['pt' => $item['title']],
                'excerpt' => ['pt' => $item['excerpt']],
                'content' => ['pt' => $item['content']],
                'cover_image_id' => $item['cover'] ? SeedMedia::fromPublic($item['cover'], $item['title'], $item['title'])->id : null,
                'status' => PageStatus::Published,
                'published_at' => Carbon::parse($item['date'])->setTimezone(config('app.timezone')),
            ]);
        }
    }
}
