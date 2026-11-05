<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

/**
 * Autenticação do painel (Laravel Fortify em modo SPA, rotas em
 * /api/admin/auth): login, verificação em dois passos, recuperação de
 * palavra-passe e alteração do perfil.
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Só entra quem tem conta activa e pelo menos um perfil.
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->string('email')->lower()->value())->first();

            if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
                return null;
            }

            if (! $user->canAccessAdmin()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Esta conta não tem acesso ao painel. Fale com um administrador.',
                ]);
            }

            return $user;
        });

        // A ligação do e-mail de recuperação abre o formulário do painel.
        ResetPassword::createUrlUsing(fn (User $user, string $token): string => url('/admin/repor-palavra-passe').'?'.http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));

        // Quem acabou de entrar não precisa de confirmar a palavra-passe outra vez
        // para configurar a verificação em dois passos. Não se aplica a quem volta
        // pelo cookie "lembrar-me" (aí não houve palavra-passe).
        Event::listen(Login::class, function (): void {
            if (request()->hasSession() && ! auth()->guard('web')->viaRemember()) {
                request()->session()->put('auth.password_confirmed_at', time());
            }
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
