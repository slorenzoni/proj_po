<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\PedidoDeRedefinicaoNaoAtendido;
use App\Support\RespostaDeLimite;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // "Esqueci a senha" não revela se o e-mail tem conta (SEGURANCA.md, PM3). bind (não
        // singleton): o Fortify cria a resposta passando o status.
        $this->app->bind(FailedPasswordResetLinkRequestResponseContract::class, PedidoDeRedefinicaoNaoAtendido::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        $email = fn (Request $request): string => Str::transliterate(Str::lower(trim((string) $request->input('email'))));

        // SEGURANCA.md, PG1: o limite antigo contava só e-mail + IP, e com a origem acessível sem o
        // Cloudflare o IP podia ser falsificado — cada "IP novo" zerava a conta. O segundo limite é
        // SÓ por e-mail: trocar de IP não adianta. Efeito colateral aceito (o mesmo do T.E.D.): 20
        // senhas erradas numa hora travam aquele e-mail por até 1 hora, mesmo para o dono.
        RateLimiter::for('login', function (Request $request) use ($email) {
            $resposta = RespostaDeLimite::noCampo(Fortify::username(), 'Muitas tentativas de entrar com este e-mail. Tente novamente em :tempo.');

            return [
                Limit::perMinute(5)->by('login-ip:'.$email($request).'|'.$request->ip())->response($resposta),
                Limit::perHour(20)->by('login-email:'.$email($request))->response($resposta),
            ];
        });

        // SEGURANCA.md, PG3: cadastro e "esqueci a senha" mandam e-mail para um endereço digitado
        // por qualquer um — sem limite, o SMTP (o mesmo do T.E.D.) vira relé de spam. Cada limite tem
        // chave própria; o teto geral não depende do IP (vale mesmo com IP falsificado).
        RateLimiter::for('cadastro', function (Request $request) {
            $resposta = RespostaDeLimite::noCampo('email', 'Muitos cadastros em pouco tempo. Tente novamente em :tempo.');

            return [
                Limit::perHour(5)->by('cadastro-ip:'.$request->ip())->response($resposta),
                Limit::perHour(30)->by('cadastro-geral')->response($resposta),
            ];
        });

        // O próprio Laravel já segura 1 e-mail por minuto para o mesmo endereço (config/auth.php).
        RateLimiter::for('recuperacao-senha', function (Request $request) use ($email) {
            $resposta = RespostaDeLimite::noCampo('email', 'Muitos pedidos de redefinição de senha. Tente novamente em :tempo.');

            return [
                Limit::perHour(3)->by('recuperacao-email:'.$email($request))->response($resposta),
                Limit::perHour(10)->by('recuperacao-ip:'.$request->ip())->response($resposta),
                Limit::perHour(50)->by('recuperacao-geral')->response($resposta),
            ];
        });

        // Verificação de e-mail: o Fortify usa o mesmo limitador no reenvio e no clique do link.
        RateLimiter::for('verificacao', function (Request $request) {
            $quem = $request->user()?->id ?: $request->ip();
            $resposta = RespostaDeLimite::noAviso('O e-mail de verificação foi pedido há pouco. Tente novamente em :tempo.');

            return [
                Limit::perMinute(2)->by('verificacao-min:'.$quem)->response($resposta),
                Limit::perHour(6)->by('verificacao-hora:'.$quem)->response($resposta),
            ];
        });
    }
}
