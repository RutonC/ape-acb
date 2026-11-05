<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CompanyProfile;
use App\Support\MediaLookup;
use App\Support\Navigation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');

        // Windows: sem a pasta temporária, "artisan serve" recusa todos os ficheiros enviados.
        ServeCommand::$passthroughVariables = [...ServeCommand::$passthroughVariables, 'TEMP', 'TMP'];

        $this->app->scoped(CompanyProfile::class);
        $this->app->scoped(Navigation::class);
        $this->app->scoped(MediaLookup::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dados partilhados pelo layout público (cabeçalho, rodapé, botões flutuantes).
        View::composer([
            'components.layouts.site', 'partials.site.*', 'pages.*', 'blocks.*',
            'services.*', 'jobs.*', 'companies.*', 'blog.*', 'contact.*',
        ], function ($view) {
            $view->with([
                'company' => app(CompanyProfile::class)->toArray(),
                'menu' => app(Navigation::class),
            ]);
        });

        // Atrás de um proxy (ngrok, Cloudflare): endereços com o https e o domínio públicos.
        if (filled($proxies = config('site.trusted_proxies'))) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // O perfil Administrador tem acesso a todas as áreas do painel.
        Gate::before(fn (User $user) => $user->isAdministrator() ? true : null);

        // Formulários públicos (contacto): protecção contra envios em massa.
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinutes(10, 5)->by($request->ip()));
    }
}
