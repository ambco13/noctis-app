<?php

namespace App\Support;

/**
 * Générateur CSS dynamique du panneau Style (porté du plugin d'origine) :
 * lit les overrides depuis la table settings et produit un bloc <style>
 * qui écrase les valeurs par défaut de ntb-public.css.
 * Les presets ne touchent QUE les couleurs, jamais les rayons.
 */
class Design
{
    private const SETTING_KEY = 'design_settings';

    /**
     * Polices proposées dans l'onglet Style (admin) et l'éditeur de styles
     * par classe — mêmes choix que le plugin d'origine.
     *
     * @var array<string, string> valeur CSS => libellé
     */
    public const FONTS = [
        "'Albert Sans', system-ui, sans-serif" => 'Albert Sans (défaut)',
        "'Inter', system-ui, sans-serif" => 'Inter',
        "'Plus Jakarta Sans', system-ui, sans-serif" => 'Plus Jakarta Sans',
        "'Outfit', system-ui, sans-serif" => 'Outfit',
        "'DM Sans', system-ui, sans-serif" => 'DM Sans',
        'system-ui, sans-serif' => 'Police système',
    ];

    /**
     * Présets de l'onglet Style — mêmes palettes que le plugin d'origine.
     *
     * @var array<string, array<string, string>>
     */
    public const PRESETS = [
        'dark' => ['label' => 'Dark (défaut)', 'accent' => '#3b9fd4', 'bg' => '#1b1f27', 'text' => '#f4f5f8', 'danger' => '#dc4a38'],
        'luxury' => ['label' => 'Luxury Gold', 'accent' => '#c8a96e', 'bg' => '#18160f', 'text' => '#f5f2ec', 'danger' => '#cf4037'],
        'minimal' => ['label' => 'Minimal Light', 'accent' => '#2563eb', 'bg' => '#f8fafc', 'text' => '#0f172a', 'danger' => '#dc2626'],
        'emerald' => ['label' => 'Émeraude', 'accent' => '#10b981', 'bg' => '#0f1a16', 'text' => '#ecfdf5', 'danger' => '#ef4444'],
    ];

    /**
     * Valeurs par défaut — doivent correspondre au bloc .ntb-scope dans ntb-public.css.
     *
     * @var array<string, string>
     */
    private const DEFAULTS = [
        /* Palette */
        '--ntb-bg' => 'oklch(0.185 0.010 240)',
        '--ntb-bg2' => 'oklch(0.165 0.010 240)',
        '--ntb-surf' => 'oklch(0.235 0.012 240)',
        '--ntb-surf2' => 'oklch(0.285 0.013 240)',
        '--ntb-surf-glass' => 'oklch(0.21  0.012 240 / .92)',
        '--ntb-line' => 'oklch(0.345 0.013 240)',
        '--ntb-line-soft' => 'oklch(0.30  0.012 240)',
        '--ntb-field-line' => '#ffffff',
        '--ntb-text' => 'oklch(0.965 0.004 240)',
        '--ntb-muted' => 'oklch(0.74  0.012 240)',
        '--ntb-faint' => 'oklch(0.56  0.012 240)',
        '--ntb-on-accent' => '#fff',
        '--ntb-danger' => 'oklch(0.68  0.16  25)',
        /* Accent */
        '--ntb-accent' => 'oklch(0.72  0.155 220)',
        '--ntb-accent-hi' => 'oklch(0.79  0.155 220)',
        /* Typographie */
        '--ntb-serif' => "'Marcellus', serif",
        '--ntb-sans' => "'Albert Sans', system-ui, sans-serif",
        '--ntb-mono' => "'Spline Sans Mono', ui-monospace, monospace",
        /* Géométrie */
        '--ntb-r' => '9px',
        '--ntb-r-sm' => '9px',
        '--ntb-r-lg' => '9px',
        'ntb_btn_radius' => '999',
        /* Motion */
        '--ntb-dur' => '220ms',
        /* Effet verre */
        'ntb_glass_enabled' => '1',
        'ntb_glass_opacity' => '92',
        '--ntb-glass-blur' => 'blur(8px)',
        /* Mode d'affichage : dark, light ou auto (préférence système).
           'auto' par défaut = comportement historique du site (suit l'OS). */
        'ntb_theme_mode' => 'auto',
        /* Transparence & flou par couleur */
        'ntb_trans_bg' => '0',
        'ntb_trans_surf' => '0',
        'ntb_alpha_surf' => '100',
        'ntb_blur_surf' => '0',
        'ntb_trans_surf2' => '0',
        'ntb_alpha_surf2' => '100',
        'ntb_blur_surf2' => '0',
        'ntb_trans_line' => '0',
        'ntb_alpha_line' => '100',
    ];

    /** @return array<string, string> */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    /** Overrides sauvegardés (clés connues uniquement). */
    public static function saved(): array
    {
        $raw = json_decode((string) Settings::get(self::SETTING_KEY, ''), true);

        return is_array($raw) ? array_intersect_key($raw, self::DEFAULTS) : [];
    }

    /** Tokens effectifs (defaults + overrides). */
    public static function tokens(): array
    {
        return array_merge(self::DEFAULTS, self::saved());
    }

    /**
     * Sauvegarde les overrides : seules les valeurs différentes du défaut
     * sont conservées (ne conserve que les clés connues).
     */
    public static function save(array $values): void
    {
        $clean = [];
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '' && $value !== self::DEFAULTS[$key]) {
                $clean[$key] = $value;
            }
        }

        Settings::set(self::SETTING_KEY, $clean === [] ? '' : json_encode($clean));
    }

    public static function reset(): void
    {
        Settings::set(self::SETTING_KEY, '');
    }

    /** Mode d'affichage effectif : 'dark', 'light' ou 'auto'. */
    public static function themeMode(): string
    {
        $mode = self::tokens()['ntb_theme_mode'];

        return in_array($mode, ['dark', 'light', 'auto'], true) ? $mode : 'auto';
    }

    /**
     * Bloc <style> complet à injecter dans le layout public :
     * overrides de tokens (+ miroir :root pour Flatpickr), puis styles
     * personnalisés par sélecteur — les règles verrouillées en dernier
     * pour primer sur tout le reste.
     */
    public static function styleBlock(): string
    {
        $out = '';

        $css = self::overridesCss();
        if ($css !== '') {
            $out .= "<style id=\"ntb2-design-overrides\">\n{$css}</style>\n";
        }

        $custom = CustomStyles::generateCss(CustomStyles::get(), false);
        if (trim($custom) !== '') {
            $out .= "<style id=\"ntb2-custom-styles\">\n{$custom}</style>\n";
        }

        $locked = CustomStyles::generateCss(CustomStyles::get(), true);
        if (trim($locked) !== '') {
            $out .= "<style id=\"ntb2-locked-styles\">\n{$locked}</style>\n";
        }

        return $out;
    }

    /**
     * Génère le CSS des overrides. Dérive automatiquement les variantes
     * accent/fond via color-mix() comme le plugin d'origine.
     */
    public static function overridesCss(): string
    {
        $overrides = self::saved();
        if ($overrides === []) {
            return '';
        }

        $glassEnabled = ! isset($overrides['ntb_glass_enabled']) || $overrides['ntb_glass_enabled'] !== '0';
        $glassOpacity = isset($overrides['ntb_glass_opacity']) ? max(0, min(100, (int) $overrides['ntb_glass_opacity'])) : 92;
        $btnRadius = isset($overrides['ntb_btn_radius']) ? max(0, min(999, (int) $overrides['ntb_btn_radius'])) : null;
        $transBg = ($overrides['ntb_trans_bg'] ?? '0') === '1';

        $colorTrans = [
            '--ntb-surf' => [
                'on' => ($overrides['ntb_trans_surf'] ?? '0') === '1',
                'alpha' => max(0, min(100, (int) ($overrides['ntb_alpha_surf'] ?? 100))),
                'blur' => max(0, min(20, (int) ($overrides['ntb_blur_surf'] ?? 0))),
            ],
            '--ntb-surf2' => [
                'on' => ($overrides['ntb_trans_surf2'] ?? '0') === '1',
                'alpha' => max(0, min(100, (int) ($overrides['ntb_alpha_surf2'] ?? 100))),
                'blur' => max(0, min(20, (int) ($overrides['ntb_blur_surf2'] ?? 0))),
            ],
            '--ntb-line' => [
                'on' => ($overrides['ntb_trans_line'] ?? '0') === '1',
                'alpha' => max(0, min(100, (int) ($overrides['ntb_alpha_line'] ?? 100))),
                'blur' => 0,
            ],
        ];

        // Custom properties CSS uniquement (--*).
        $cssVars = array_filter($overrides, fn ($k) => str_starts_with($k, '--'), ARRAY_FILTER_USE_KEY);

        $lines = [];
        foreach ($cssVars as $prop => $value) {
            $lines[] = sprintf("\t%s: %s;", $prop, $value);
        }

        $btnDirect = null;
        if ($btnRadius !== null) {
            $lines[] = sprintf("\t--ntb-btn-radius: %dpx;", $btnRadius);
            $btnDirect = $btnRadius.'px';
        }

        // Variantes d'accent dérivées de --ntb-accent (sauf si déjà surchargées).
        if (isset($cssVars['--ntb-accent'])) {
            $a = $cssVars['--ntb-accent'];
            if (! isset($cssVars['--ntb-accent-hi'])) {
                $lines[] = sprintf("\t--ntb-accent-hi:   color-mix(in oklch, %s, white 20%%);", $a);
            }
            $lines[] = sprintf("\t--ntb-accent-soft: color-mix(in oklch, %s 16%%, transparent);", $a);
            $lines[] = sprintf("\t--ntb-accent-line: color-mix(in oklch, %s 45%%, transparent);", $a);
        }

        // Surfaces/teintes dérivées de --ntb-bg (sauf si déjà surchargées).
        if (isset($cssVars['--ntb-bg'])) {
            $bg = $cssVars['--ntb-bg'];
            $skip = array_keys($cssVars);

            $glassVal = $glassEnabled
                ? sprintf('color-mix(in oklch, %s %d%%, transparent)', $bg, $glassOpacity)
                : sprintf('color-mix(in oklch, %s, white 7%%)', $bg);

            $bgDerived = [
                '--ntb-bg2' => "color-mix(in oklch, {$bg}, black 8%)",
                '--ntb-surf' => "color-mix(in oklch, {$bg}, white 7%)",
                '--ntb-surf2' => "color-mix(in oklch, {$bg}, white 14%)",
                '--ntb-surf-glass' => $glassVal,
                '--ntb-line' => "color-mix(in oklch, {$bg}, white 22%)",
                '--ntb-line-soft' => "color-mix(in oklch, {$bg}, white 17%)",
                '--ntb-muted' => "color-mix(in oklch, {$bg}, white 58%)",
                '--ntb-faint' => "color-mix(in oklch, {$bg}, white 38%)",
            ];
            foreach ($bgDerived as $prop => $val) {
                if (! in_array($prop, $skip, true)) {
                    $lines[] = sprintf("\t%s: %s;", $prop, $val);
                }
            }
        } elseif (! $glassEnabled || $glassOpacity !== 92) {
            if (! isset($cssVars['--ntb-surf-glass'])) {
                $lines[] = $glassEnabled
                    ? sprintf("\t--ntb-surf-glass: oklch(0.21 0.012 240 / %.2f);", $glassOpacity / 100)
                    : "\t--ntb-surf-glass: oklch(0.21 0.012 240);";
            }
        }

        if (! $glassEnabled) {
            $lines[] = "\t--ntb-glass-blur: none;";
        }
        if ($transBg) {
            $lines[] = "\t--ntb-surf-glass: transparent;";
        }

        // Transparence & flou par couleur — émis en dernier pour écraser.
        $bgFor = $cssVars['--ntb-bg'] ?? null;
        $derivePcts = ['--ntb-surf' => 7, '--ntb-surf2' => 14, '--ntb-line' => 22];
        foreach ($colorTrans as $prop => $cfg) {
            if ($cfg['on'] && $cfg['alpha'] < 100) {
                $base = $cssVars[$prop]
                    ?? ($bgFor !== null
                        ? sprintf('color-mix(in oklch, %s, white %d%%)', $bgFor, $derivePcts[$prop])
                        : self::DEFAULTS[$prop]);
                $lines[] = sprintf("\t%s: color-mix(in srgb, %s %d%%, transparent);", $prop, $base, $cfg['alpha']);
            }
            if ($prop !== '--ntb-line' && $cfg['on'] && $cfg['blur'] > 0) {
                $lines[] = sprintf("\t%s-blur: blur(%dpx);", $prop, $cfg['blur']);
            }
        }

        if ($lines === []) {
            return '';
        }

        $block = implode("\n", $lines)."\n";

        // Scope principal + miroir :root (Flatpickr et pickers hors .ntb-scope).
        $css = ".ntb-scope, .ntb-dashboard {\n".$block."}\n";
        $css .= ":root {\n".$block."}\n";
        // Bloc renforcé : la palette du thème clair est émise sous
        // html[data-ntb-theme="light"] .ntb-scope (spécificité supérieure à
        // .ntb-scope) — sans lui, les couleurs admin seraient écrasées dès que
        // le thème clair est actif. Même spécificité + ordre postérieur = win.
        $css .= "html[data-ntb-theme] .ntb-scope, html[data-ntb-theme] .ntb-dashboard {\n".$block."}\n";

        // Règle directe sur les boutons (égalise la spécificité avec le CSS personnalisé).
        if ($btnDirect !== null) {
            $css .= '.ntb-scope .ntb-btn, .ntb-dashboard .ntb-btn { border-radius: '.$btnDirect."; }\n";
        }

        return $css;
    }
}
