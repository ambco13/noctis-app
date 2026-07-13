<?php

namespace App\Support;

/**
 * Styles personnalisés par sélecteur — éditeur libre (porté du plugin d'origine).
 *
 * L'admin cible n'importe quelle classe du tunnel/espace client et surcharge son
 * apparence (couleurs, bordures, radius, espacement, police, ombre…). Le CSS est
 * injecté par Design::styleBlock() après les overrides de tokens ; les règles
 * « verrouillées » sortent dans un bloc séparé, en dernier, pour primer sur tout.
 *
 * Sécurité : chaque sélecteur est confiné sous .ntb-scope et .ntb-dashboard pour
 * ne jamais affecter le reste du site. Noms de propriétés et valeurs sont filtrés.
 */
class CustomStyles
{
    private const SETTING_KEY = 'custom_styles';

    /** Ancien réglage « CSS personnalisé » (textarea brut) — repris comme raw. */
    private const LEGACY_RAW_KEY = 'custom_css';

    /** Conteneurs sous lesquels chaque sélecteur est confiné. */
    private const SCOPES = ['.ntb-scope', '.ntb-dashboard'];

    /**
     * Configuration enregistrée.
     *
     * @return array{rules: list<array{selector: string, props: array<string, string>, locked: bool}>, raw: string}
     */
    public static function get(): array
    {
        $saved = json_decode((string) Settings::get(self::SETTING_KEY, ''), true);
        if (! is_array($saved)) {
            // Migration douce : l'ancien textarea « CSS personnalisé » devient le CSS brut.
            return ['rules' => [], 'raw' => (string) Settings::get(self::LEGACY_RAW_KEY, '')];
        }

        return [
            'rules' => isset($saved['rules']) && is_array($saved['rules']) ? array_values($saved['rules']) : [],
            'raw' => isset($saved['raw']) && is_string($saved['raw']) ? $saved['raw'] : '',
        ];
    }

    /** Sauvegarde une configuration brute (nettoyée ici). */
    public static function save(array $config): void
    {
        Settings::set(self::SETTING_KEY, json_encode(self::sanitize($config)));
        // La nouvelle clé devient la référence : on retire l'ancienne pour
        // qu'un futur get() sans réglage ne ressuscite pas le CSS migré.
        Settings::set(self::LEGACY_RAW_KEY, '');
    }

    public static function reset(): void
    {
        Settings::set(self::SETTING_KEY, '');
        Settings::set(self::LEGACY_RAW_KEY, '');
    }

    /**
     * Nettoie une configuration brute (issue du POST décodé).
     *
     * @return array{rules: list<array{selector: string, props: array<string, string>, locked: bool}>, raw: string}
     */
    public static function sanitize(array $config): array
    {
        $rules = [];
        foreach ((array) ($config['rules'] ?? []) as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $selector = self::cleanSelector((string) ($rule['selector'] ?? ''));
            if ($selector === '') {
                continue;
            }
            $props = [];
            foreach ((array) ($rule['props'] ?? []) as $name => $value) {
                $name = self::cleanPropName((string) $name);
                $value = self::cleanValue((string) $value);
                if ($name !== '' && $value !== '') {
                    $props[$name] = $value;
                }
            }
            if ($props !== []) {
                $rules[] = ['selector' => $selector, 'props' => $props, 'locked' => ! empty($rule['locked'])];
            }
        }

        return ['rules' => $rules, 'raw' => self::cleanRaw((string) ($config['raw'] ?? ''))];
    }

    /**
     * Génère le CSS d'une configuration.
     *
     * @param  bool  $lockedOnly  true = uniquement les règles verrouillées, false = les autres (+ CSS brut).
     */
    public static function generateCss(array $config, bool $lockedOnly): string
    {
        $out = '';
        foreach ((array) ($config['rules'] ?? []) as $rule) {
            if (! is_array($rule) || ! empty($rule['locked']) !== $lockedOnly) {
                continue;
            }
            $selector = self::cleanSelector((string) ($rule['selector'] ?? ''));
            if ($selector === '' || empty($rule['props']) || ! is_array($rule['props'])) {
                continue;
            }
            $decls = '';
            foreach ($rule['props'] as $name => $value) {
                $name = self::cleanPropName((string) $name);
                $value = self::cleanValue((string) $value);
                if ($name !== '' && $value !== '') {
                    $decls .= sprintf("\t%s: %s;\n", $name, $value);
                }
            }
            if ($decls !== '') {
                $out .= self::scopeSelector($selector)." {\n".$decls."}\n";
            }
        }

        // Le CSS brut additionnel n'est jamais verrouillé.
        if (! $lockedOnly) {
            $raw = self::cleanRaw((string) ($config['raw'] ?? ''));
            if (trim($raw) !== '') {
                $out .= $raw."\n";
            }
        }

        return $out;
    }

    /**
     * Confine un sélecteur (potentiellement multiple, séparé par des virgules)
     * sous chaque conteneur. Les sélecteurs déjà confinés restent tels quels.
     */
    private static function scopeSelector(string $selector): string
    {
        $scoped = [];
        foreach (array_filter(array_map('trim', explode(',', $selector))) as $part) {
            foreach (self::SCOPES as $scope) {
                if (str_starts_with($part, $scope)) {
                    $scoped[] = $part;

                    continue 2;
                }
            }
            foreach (self::SCOPES as $scope) {
                $scoped[] = $scope.' '.$part;
            }
        }

        return implode(', ', $scoped);
    }

    /** Retire d'un sélecteur tout caractère hors syntaxe CSS courante. */
    private static function cleanSelector(string $selector): string
    {
        $selector = strip_tags($selector);
        $selector = (string) preg_replace('/[^a-zA-Z0-9 _\-.#:>~+\[\]="\'(),*]/', '', $selector);

        return trim($selector);
    }

    /** Nom de propriété CSS : lettres minuscules et tirets uniquement. */
    private static function cleanPropName(string $name): string
    {
        return (string) preg_replace('/[^a-z\-]/', '', strtolower(trim($name)));
    }

    /** Valeur de déclaration : bloque les vecteurs d'injection. */
    private static function cleanValue(string $value): string
    {
        $value = trim($value);
        if (preg_match('/<|@import|expression\s*\(|javascript:|url\s*\(/i', $value)) {
            return '';
        }

        return trim(str_replace(['{', '}', ';'], '', $value));
    }

    /** CSS brut : autorise les accolades, bloque l'évasion HTML/JS. */
    private static function cleanRaw(string $raw): string
    {
        $raw = str_replace('<', '', $raw);
        $raw = (string) preg_replace('/@import|expression\s*\(|javascript:/i', '', $raw);

        return trim($raw);
    }
}
