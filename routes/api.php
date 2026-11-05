<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\BlogTaxonomyController;
use App\Http\Controllers\Admin\CompanyRegistrationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\JobCategoryController;
use App\Http\Controllers\Admin\JobOpeningController;
use App\Http\Controllers\Admin\LinkOptionsController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OptionsController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API do painel (/api/admin)
|--------------------------------------------------------------------------
|
| Usada pela aplicação React em /admin. A autenticação é feita pela sessão
| do Laravel através do Sanctum (cookie + CSRF), sem tokens no browser.
| Login, 2FA, recuperação de palavra-passe e perfil: rotas do Fortify em
| /api/admin/auth (config/fortify.php).
|
*/

Route::prefix('admin')->name('api.admin.')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/me', [SessionController::class, 'me'])->name('me');

    // O resto do painel exige a verificação em dois passos quando é obrigatória.
    Route::middleware('admin.2fa')->group(function () {
        // Biblioteca de média (ver e carregar: todo o painel; editar e apagar: área "media").
        Route::post('/media/bulk-delete', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
        Route::apiResource('media', MediaController::class)->parameters(['media' => 'media']);

        // Opções partilhadas pelos formulários.
        Route::get('/links/options', LinkOptionsController::class)->name('links.options');
        Route::get('/options/icons', [OptionsController::class, 'icons'])->name('options.icons');
        Route::get('/options/services', [OptionsController::class, 'services'])->name('options.services');

        // Serviços.
        foreach (['services' => 'service'] as $area => $kind) {
            Route::prefix($area)->name("{$area}.")->middleware("can:{$area}")->group(function () use ($kind) {
                Route::get('/', [ServiceController::class, 'index'])->name('index')->defaults('kind', $kind);
                Route::post('/', [ServiceController::class, 'store'])->name('store')->defaults('kind', $kind);
                Route::post('/reorder', [ServiceController::class, 'reorder'])->name('reorder')->defaults('kind', $kind);
                Route::post('/bulk-delete', [ServiceController::class, 'bulkDestroy'])->name('bulk-destroy')->defaults('kind', $kind);
                Route::get('/{id}', [ServiceController::class, 'show'])->name('show')->defaults('kind', $kind)->whereNumber('id');
                Route::put('/{id}', [ServiceController::class, 'update'])->name('update')->defaults('kind', $kind)->whereNumber('id');
                Route::delete('/{id}', [ServiceController::class, 'destroy'])->name('destroy')->defaults('kind', $kind)->whereNumber('id');
            });
        }

        Route::middleware('can:jobs')->group(function () {
            Route::post('/jobs/reorder', [JobOpeningController::class, 'reorder'])->name('jobs.reorder');
            Route::post('/jobs/bulk-delete', [JobOpeningController::class, 'bulkDestroy'])->name('jobs.bulk-destroy');
            Route::apiResource('jobs', JobOpeningController::class)->parameters(['jobs' => 'job']);

            Route::post('/job-categories/reorder', [JobCategoryController::class, 'reorder'])->name('job-categories.reorder');
            Route::apiResource('job-categories', JobCategoryController::class)->parameters(['job-categories' => 'category'])->except('show');
        });

        Route::middleware('can:pages')->group(function () {
            Route::get('/pages/blocks', [PageController::class, 'blocks'])->name('pages.blocks');
            Route::get('/pages/{id}/preview', [PageController::class, 'preview'])->name('pages.preview')->whereNumber('id');
            Route::post('/pages/{id}/restore', [PageController::class, 'restore'])->name('pages.restore')->whereNumber('id');
            Route::delete('/pages/{id}/force', [PageController::class, 'forceDestroy'])->name('pages.force-destroy')->whereNumber('id');
            Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
            Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
            Route::get('/pages/{id}', [PageController::class, 'show'])->name('pages.show')->whereNumber('id');
            Route::put('/pages/{id}', [PageController::class, 'update'])->name('pages.update')->whereNumber('id');
            Route::delete('/pages/{id}', [PageController::class, 'destroy'])->name('pages.destroy')->whereNumber('id');
        });

        Route::middleware('can:menus')->group(function () {
            Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
            Route::get('/menus/{menu}', [MenuController::class, 'show'])->name('menus.show');
            Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
        });

        Route::middleware('can:blog')->group(function () {
            Route::get('/options/authors', [OptionsController::class, 'authors'])->name('options.authors');

            Route::post('/posts/bulk', [PostController::class, 'bulk'])->name('posts.bulk');
            Route::get('/posts/{id}/preview', [PostController::class, 'preview'])->name('posts.preview')->whereNumber('id');
            Route::post('/posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore')->whereNumber('id');
            Route::delete('/posts/{id}/force', [PostController::class, 'forceDestroy'])->name('posts.force-destroy')->whereNumber('id');
            Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
            Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
            Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show')->whereNumber('id');
            Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update')->whereNumber('id');
            Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy')->whereNumber('id');

            // Categorias e etiquetas do blog.
            Route::post('/blog/categories/reorder', [BlogTaxonomyController::class, 'reorder'])->name('blog.categories.reorder');
            Route::prefix('blog/{taxonomy}')->whereIn('taxonomy', ['categories', 'tags'])->name('blog.taxonomy.')->group(function () {
                Route::get('/', [BlogTaxonomyController::class, 'index'])->name('index');
                Route::post('/', [BlogTaxonomyController::class, 'store'])->name('store');
                Route::put('/{id}', [BlogTaxonomyController::class, 'update'])->name('update')->whereNumber('id');
                Route::delete('/{id}', [BlogTaxonomyController::class, 'destroy'])->name('destroy')->whereNumber('id');
            });
        });

        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::middleware('can:settings')->group(function () {
            Route::get('/settings/general', [SettingsController::class, 'showGeneral'])->name('settings.general');
            Route::put('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
            Route::get('/settings/seo', [SettingsController::class, 'showSeo'])->name('settings.seo');
            Route::put('/settings/seo', [SettingsController::class, 'updateSeo'])->name('settings.seo.update');
        });

        // Caixa de entrada: contadores para o menu e as listas.
        Route::get('/inbox/counts', [InboxController::class, 'counts'])->name('inbox.counts');

        Route::middleware('can:messages')->group(function () {
            Route::post('/messages/bulk-delete', [MessageController::class, 'bulkDestroy'])->name('messages.bulk-destroy');
            Route::post('/messages/bulk-status', [MessageController::class, 'bulkStatus'])->name('messages.bulk-status');
            Route::apiResource('messages', MessageController::class)->except('store');
        });

        Route::middleware('can:applications')->group(function () {
            Route::post('/applications/bulk-delete', [ApplicationController::class, 'bulkDestroy'])->name('applications.bulk-destroy');
            Route::get('/applications/{application}/cv', [ApplicationController::class, 'cv'])->name('applications.cv');
            Route::apiResource('applications', ApplicationController::class)->except('store');
        });

        Route::middleware('can:companies')->group(function () {
            Route::post('/company-registrations/bulk-delete', [CompanyRegistrationController::class, 'bulkDestroy'])->name('company-registrations.bulk-destroy');
            Route::apiResource('company-registrations', CompanyRegistrationController::class)->parameters(['company-registrations' => 'registration'])->except('store');
        });

        Route::middleware('can:team')->group(function () {
            Route::post('/team/reorder', [TeamMemberController::class, 'reorder'])->name('team.reorder');
            Route::apiResource('team', TeamMemberController::class)->parameters(['team' => 'teamMember'])->except('show');
        });

        Route::middleware('can:users')->group(function () {
            Route::post('/users/{user}/reset-two-factor', [UserController::class, 'resetTwoFactor'])->name('users.reset-two-factor');
            Route::apiResource('users', UserController::class);

            Route::get('/permissions', [RoleController::class, 'permissions'])->name('permissions');
            Route::apiResource('roles', RoleController::class);
        });
    });
});
