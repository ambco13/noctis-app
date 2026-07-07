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
