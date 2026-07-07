{{--
    Mini graphique à barres (30 jours) — série unique, SVG sans dépendance.
    Paramètres : $chartId, $title, $series (date Y-m-d => float), $money (bool).
    Barres fines à sommet arrondi ancrées à la ligne de base, écart 2px,
    grille discrète, tooltip au survol (cible de clic pleine hauteur),
    étiquette directe sur le maximum uniquement, vue tableau repliée.
--}}
@php
    use App\Support\Settings;

    $values = array_values($series);
    $labels = array_keys($series);
    $max = max($values) ?: 0;
    $n = count($values);

    $w = 600; $h = 150; $padTop = 18; $padBottom = 16;
    $plotH = $h - $padTop - $padBottom;
    $slot = $n > 0 ? $w / $n : 0;
    $barW = max(2, $slot - 2); // écart 2px entre barres.
    $r = min(4, $barW / 2);    // sommet arrondi 4px.

    $fmt = fn (float $v) => $money ? Settings::formatPrice($v) : (string) (int) $v;
    $maxIndex = $max > 0 ? array_search($max, $values, true) : null;
    $dateFr = fn (string $d) => \DateTime::createFromFormat('Y-m-d', $d)->format('d/m');
@endphp
<div style="background:#fff;border-radius:10px;padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.07);">
    <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:10px;">{{ $title }}</div>

    @if ($max <= 0)
        <div style="height:{{ $h }}px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:13px;">
            {{ __('Aucune donnée sur la période.') }}
        </div>
    @else
        <div style="position:relative;">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" style="width:100%;height:auto;display:block;" role="img"
                 aria-label="{{ $title }}">
                {{-- Grille discrète : 3 lignes horizontales. --}}
                @foreach ([0.25, 0.5, 0.75] as $g)
                    <line x1="0" y1="{{ $padTop + $plotH * $g }}" x2="{{ $w }}" y2="{{ $padTop + $plotH * $g }}"
                          stroke="#f1f5f9" stroke-width="1"/>
                @endforeach
                <line x1="0" y1="{{ $padTop + $plotH }}" x2="{{ $w }}" y2="{{ $padTop + $plotH }}" stroke="#e5e7eb" stroke-width="1"/>

                @foreach ($values as $i => $v)
                    @php
                        $x = $i * $slot + 1;
                        $bh = $max > 0 ? max($v > 0 ? 2 : 0, $plotH * $v / $max) : 0;
                        $y = $padTop + $plotH - $bh;
                        $rr = min($r, $bh); // pas d'arrondi plus haut que la barre.
                    @endphp
                    @if ($bh > 0)
                        <path d="M{{ $x }},{{ $padTop + $plotH }} L{{ $x }},{{ $y + $rr }} Q{{ $x }},{{ $y }} {{ $x + $rr }},{{ $y }} L{{ $x + $barW - $rr }},{{ $y }} Q{{ $x + $barW }},{{ $y }} {{ $x + $barW }},{{ $y + $rr }} L{{ $x + $barW }},{{ $padTop + $plotH }} Z"
                              fill="#2563eb"/>
                    @endif
                    {{-- Cible de survol pleine hauteur (plus grande que la barre). --}}
                    <rect class="nadm-chart-hit" x="{{ $i * $slot }}" y="0" width="{{ $slot }}" height="{{ $h }}"
                          fill="transparent"
                          data-label="{{ $dateFr($labels[$i]) }}" data-value="{{ $fmt($v) }}"/>
                    {{-- Étiquette directe : maximum uniquement. --}}
                    @if ($i === $maxIndex)
                        <text x="{{ min(max($x + $barW / 2, 24), $w - 24) }}" y="{{ max($y - 5, 11) }}"
                              text-anchor="middle" font-size="11" fill="#374151">{{ $fmt($v) }}</text>
                    @endif
                @endforeach

                {{-- Bornes temporelles. --}}
                <text x="0" y="{{ $h - 3 }}" font-size="10" fill="#9ca3af">{{ $dateFr($labels[0]) }}</text>
                <text x="{{ $w }}" y="{{ $h - 3 }}" text-anchor="end" font-size="10" fill="#9ca3af">{{ $dateFr($labels[$n - 1]) }}</text>
            </svg>
            <div class="nadm-chart-tip" hidden
                 style="position:absolute;pointer-events:none;background:#111827;color:#fff;font-size:12px;padding:4px 8px;border-radius:6px;white-space:nowrap;z-index:5;"></div>
        </div>

        <details style="margin-top:8px;">
            <summary style="font-size:12px;color:#6b7280;cursor:pointer;">{{ __('Voir les données') }}</summary>
            <table class="nadm-table" style="margin-top:8px;">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ $title }}</th></tr></thead>
                <tbody>
                @foreach ($series as $d => $v)
                    @if ($v > 0)
                        <tr><td>{{ $dateFr($d) }}</td><td>{{ $fmt($v) }}</td></tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </details>
    @endif
</div>
