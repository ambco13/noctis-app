<?php

use App\Exceptions\BookingException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Webhook signé par Stripe : la vérification HMAC remplace le CSRF.
        $middleware->validateCsrfTokens(except: [
            'api/v1/webhook/stripe',
        ]);

        // Pas de route "login" classique : les invités passent par /mon-compte.
        $middleware->redirectGuestsTo(fn () => route('account'));

        // Le site est servi derrière Cloudflare (amirvtc.freedev.app) : sans
        // ça, $request->ip() renvoie l'IP du proxy pour tout le monde, ce qui
        // casse le rate limiting par IP (login, magic-link, password-forgot…)
        // et la détection HTTPS. Plages IPv4/IPv6 officielles Cloudflare —
        // à vérifier/mettre à jour sur https://www.cloudflare.com/ips/.
        $middleware->trustProxies(
            at: [
                '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22',
                '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
                '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22',
                '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
                '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
                '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
                '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29',
                '2c0f:f248::/32',
            ],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Erreurs métier (véhicule introuvable, trajet non calculable…) → 422 avec message utilisateur.
        $exceptions->render(function (BookingException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    })->create();
