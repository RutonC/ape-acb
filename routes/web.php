<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MediaImageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ServiceController;
use App\Models\Page;
use App\Support\Localization;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site público
|--------------------------------------------------------------------------
|
| As rotas são registadas uma vez por idioma: português sem prefixo
| (predefinido) e inglês em /en com nomes "en.*" e segmentos traduzidos
| (/en/about-us, /en/contact...). Nas vistas usa-se
| lroute('nome') para gerar o URL no idioma actual.
|
*/

$publicRoutes = function (string $locale) {
    // Segmentos fixos traduzidos por idioma (config/site.php "paths").
    $p = fn (string $path) => Localization::path($path, $locale);

    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get($p('/sobre-nos'), [PageController::class, 'about'])->name('about');
    Route::get($p('/vagas'), [JobController::class, 'index'])->name('jobs');
    Route::get($p('/vagas/{slug}'), [JobController::class, 'show'])->name('jobs.show');
    Route::get($p('/candidatura'), [JobController::class, 'apply'])->name('apply');
    Route::post($p('/candidatura'), [JobController::class, 'store'])->middleware('throttle:forms')->name('apply.store');
    Route::get($p('/empresas'), [CompanyController::class, 'show'])->name('companies');
    Route::post($p('/empresas'), [CompanyController::class, 'store'])->middleware('throttle:forms')->name('companies.store');
    Route::get($p('/parceiros'), [PageController::class, 'partners'])->name('partners');
    Route::get($p('/servicos'), [ServiceController::class, 'services'])->name('services');
    Route::get($p('/servicos/{slug}'), [ServiceController::class, 'service'])->name('services.show');
    Route::get($p('/noticias'), [BlogController::class, 'index'])->name('blog');
    Route::get($p('/noticias/categoria/{slug}'), [BlogController::class, 'category'])->name('blog.category');
    Route::get($p('/noticias/etiqueta/{slug}'), [BlogController::class, 'tag'])->name('blog.tag');
    Route::get($p('/noticias/{slug}'), [BlogController::class, 'show'])->name('blog.show');
    Route::get($p('/contactos'), [ContactController::class, 'show'])->name('contact');
    Route::post($p('/contactos'), [ContactController::class, 'store'])->middleware('throttle:forms')->name('contact.store');
    Route::get($p('/politica-de-privacidade'), [PageController::class, 'privacy'])->name('privacy');
    Route::get($p('/termos-de-utilizacao'), [PageController::class, 'terms'])->name('terms');
};

/*
| Páginas criadas no painel (/{slug}) — registadas no fim de cada grupo para
| não se sobreporem às rotas fixas; os caminhos do sistema ficam excluídos.
*/
$pageSlug = '(?!(?:'.implode('|', Page::RESERVED_SLUGS).')$)[a-z0-9]+(?:-[a-z0-9]+)*';

Route::middleware('locale:pt')->group(function () use ($publicRoutes, $pageSlug) {
    $publicRoutes('pt');
    Route::get('/{slug}', [PageController::class, 'show'])->where('slug', $pageSlug)->name('page');
});

// Inglês: só com SITE_LOCALES a incluir "en" (ver config/site.php).
if (array_key_exists('en', config('site.locales'))) {
Route::prefix('en')->name('en.')->middleware('locale:en')->group(function () use ($publicRoutes, $pageSlug) {
    $publicRoutes('en');

    // Endereços com segmentos em português (/en/contactos) -> equivalente inglês (301).
    Route::get('/{legacy}/{rest?}', function (string $legacy, ?string $rest = null) {
        $path = '/en/'.config("site.paths.en.{$legacy}").($rest ? '/'.$rest : '');
        $query = request()->getQueryString();

        return redirect($path.($query ? '?'.$query : ''), 301);
    })->whereIn('legacy', array_keys(config('site.paths.en')))->where('rest', '.*');

    Route::get('/{slug}', [PageController::class, 'show'])->where('slug', $pageSlug)->name('page');
});
}

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Pré-visualização de páginas (incluindo rascunhos) por endereço assinado.
Route::get('/pre-visualizar/{page}/{locale}', [PageController::class, 'preview'])
    ->whereIn('locale', array_keys(config('site.locales')))
    ->middleware('signed')
    ->withTrashed()
    ->name('pages.preview');

Route::get('/pre-visualizar/artigo/{post}/{locale}', [BlogController::class, 'preview'])
    ->whereIn('locale', array_keys(config('site.locales')))
    ->middleware('signed')
    ->withTrashed()
    ->name('posts.preview');

// Imagens da biblioteca de média redimensionadas (URLs assinados).
Route::get('/'.config('media.glide_path').'/{path}', MediaImageController::class)
    ->where('path', '.*')
    ->name('media.image');

// Painel de administração (aplicação React; a navegação interna é feita no browser).
Route::view('/admin/{any?}', 'admin')->where('any', '.*')->name('admin');
