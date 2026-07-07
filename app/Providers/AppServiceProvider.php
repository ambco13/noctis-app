<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        // Le lien de réinitialisation pointe vers le formulaire de /mon-compte.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return url('/mon-compte').'?reset=1&token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
