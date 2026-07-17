@php
    use App\Support\Countries;
    use App\Support\Settings;

    // Contrat d'inclusion : $id (id du champ combiné, lu par le JS existant),
    // $name (name du champ), $value (téléphone stocké, ex "+33612…"), $required.
    $value = $value ?? '';
    $required = $required ?? false;
    $defaultDial = $defaultDial ?? (string) Settings::get('default_country_code', '+33');
    $phFallback = __('Numéro de téléphone');

    $countries = Countries::all();
    // Drapeau + exemple par défaut : premier pays partageant l'indicatif par défaut (ex +33 → fr).
    $defaultIso = 'fr';
    $defaultEx = null;
    foreach ($countries as $c) {
        if ($c['dial'] === $defaultDial) { $defaultIso = $c['iso']; $defaultEx = $c['ex']; break; }
    }
@endphp
<div class="ntb-tel" data-ntb-tel data-default-dial="{{ $defaultDial }}" data-ph-fallback="{{ $phFallback }}">
    {{-- Valeur combinée E.164 (« +indicatif + national »), lue par le JS du tunnel/profil. --}}
    <input type="hidden" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @if ($required) data-required @endif>

    <button type="button" class="ntb-tel-btn" data-ntb-tel-btn aria-haspopup="listbox" aria-expanded="false"
        aria-label="{{ __('Choisir l\'indicatif pays') }}">
        <img class="ntb-tel-flag" data-ntb-tel-flag alt="" width="20" height="15"
            src="{{ asset('assets/flags/'.$defaultIso.'.svg') }}">
        <span class="ntb-tel-dial" data-ntb-tel-dial>{{ $defaultDial }}</span>
        <svg class="ntb-tel-caret" width="12" height="12" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
    </button>

    <input type="tel" class="ntb-tel-num" id="{{ $id }}-num" data-ntb-tel-num
        inputmode="tel" autocomplete="tel" placeholder="{{ $defaultEx ?: $phFallback }}">

    <div class="ntb-tel-panel" data-ntb-tel-panel hidden>
        <div class="ntb-tel-search-wrap">
            <svg class="ntb-tel-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" class="ntb-tel-search" data-ntb-tel-search
                placeholder="{{ __('Rechercher un pays…') }}" autocomplete="off" spellcheck="false">
        </div>
        <ul class="ntb-tel-list" data-ntb-tel-list role="listbox">
            @foreach ($countries as $c)
                <li class="ntb-tel-opt" role="option" tabindex="-1"
                    data-iso="{{ $c['iso'] }}" data-dial="{{ $c['dial'] }}" data-ex="{{ $c['ex'] ?? '' }}"
                    data-search="{{ mb_strtolower($c['name']).' '.$c['dial'] }}">
                    <img class="ntb-tel-flag" alt="" loading="lazy" width="20" height="15"
                        src="{{ asset('assets/flags/'.$c['iso'].'.svg') }}">
                    <span class="ntb-tel-opt-name">{{ $c['name'] }}</span>
                    <span class="ntb-tel-opt-dial">{{ $c['dial'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
