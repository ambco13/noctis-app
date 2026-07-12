<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de durcissement absents par défaut chez Laravel : aucun ne change
 * de comportement fonctionnel, ils réduisent seulement la surface d'attaque
 * (clickjacking, MIME-sniffing, fuite de referrer, downgrade HTTP).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Anti-clickjacking (X-Frame-Options + frame-ancestors) limité à la
        // production : hors production, on laisse le site s'afficher en iframe
        // pour les outils de test responsive.
        $antiClickjacking = app()->isProduction();

        if ($antiClickjacking) {
            $response->headers->set('X-Frame-Options', 'DENY');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // 'unsafe-inline' reste nécessaire (scripts/styles inline utilisés dans
        // plusieurs vues publiques et admin) : la valeur de cette CSP n'est pas
        // de bloquer l'injection inline, mais d'empêcher un script injecté de
        // CHARGER une ressource externe (exfiltration, payload distant, iframe
        // de phishing) -- seuls les domaines listés ci-dessous sont autorisés.
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://js.stripe.com",
            "style-src 'self' 'unsafe-inline'",
            // static.blacklane.com : image de fond temporaire du hero (page 1),
            // à retirer une fois remplacée par une photo hébergée par nous.
            "img-src 'self' data: https://*.basemaps.cartocdn.com https://res.cloudinary.com https://static.blacklane.com",
            "font-src 'self' data:",
            "connect-src 'self' https://api.stripe.com",
            "frame-src https://js.stripe.com https://hooks.stripe.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        if ($antiClickjacking) {
            $csp[] = "frame-ancestors 'self'";
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
