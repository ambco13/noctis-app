<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
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
        // trustProxies fait confiance à X-Forwarded-Host (nécessaire derrière le
        // proxy Render pour que $request->secure() fonctionne), mais Render ne
        // filtre pas cet en-tête côté client : sans forceRootUrl, un attaquant
        // peut le falsifier pour empoisonner les liens générés par url()/route()
        // (magic link, réinitialisation de mot de passe) envoyés par email à de
        // vraies victimes. On fige donc l'URL racine sur APP_URL, indépendamment
        // de tout en-tête de la requête entrante.
        URL::forceRootUrl(config('app.url'));

        // Le lien de réinitialisation pointe vers le formulaire de /mon-compte.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return url('/mon-compte').'?reset=1&token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
