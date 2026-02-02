<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureLoginResponse();
        $this->configureRegistrationResponse();
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
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }


    /**
     * Get role-based redirect response for authenticated user.
     */
    public static function getRoleBasedRedirect(bool $refreshUser = false): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        if ($refreshUser) {
            // Refresh the user model to ensure relationships are loaded
            $user->refresh();
        }

        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        if ($user->hasRole('SiteAdmin')) {
            $site = $user->site;
            if ($site) {
                return redirect()->intended(route('site.dashboard', ['site' => $site->slug], false));
            }
        }

        // Fallback for other roles or users without site
        return redirect()->intended('/');
    }

    /**
     * Configure custom login response for role-based redirection.
     */
    private function configureLoginResponse(): void
    {
        $this->app->instance(LoginResponse::class, new class implements LoginResponse
        {
            public function toResponse($request): \Illuminate\Http\RedirectResponse
            {
                return FortifyServiceProvider::getRoleBasedRedirect();
            }
        });
    }

    /**
     * Configure custom registration response for role-based redirection.
     */
    private function configureRegistrationResponse(): void
    {
        $this->app->singleton(RegisterResponse::class, function ($app) {
            return new class implements RegisterResponse
            {
                public function toResponse($request): \Illuminate\Http\RedirectResponse
                {
                    return FortifyServiceProvider::getRoleBasedRedirect(true);
                }
            };
        });
    }
}
