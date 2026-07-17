@php
    $user = auth()->user();
    $hasPassword = $user->password !== null;
@endphp

<div class="ntb-profile-tab">
    <h2 class="ntb-section-title">{{ __('Mon profil') }}</h2>

    <form id="ntb-profile-form" class="ntb-profile-form">
        <div class="ntb-form-group">
            <label for="ntb-profile-email">{{ __('Adresse email') }}</label>
            <input type="email" id="ntb-profile-email" value="{{ $user->email }}" disabled />
            <small>{{ __('Le changement d\'adresse est confirmé par email.') }}
                <a href="#" id="ntb-email-change-link">{{ __('Changer d\'adresse') }}</a></small>
        </div>

        <div class="ntb-form-group" id="ntb-email-change-group" style="display:none;">
            <label for="ntb-new-email">{{ __('Nouvelle adresse email') }}</label>
            <input type="email" id="ntb-new-email" autocomplete="email" placeholder="nouvelle@adresse.fr" />
            <button type="button" id="ntb-email-change-send" class="ntb-btn ntb-btn-secondary" style="margin-top:8px;">
                {{ __('Envoyer le lien de confirmation') }}
            </button>
        </div>

        <div class="ntb-form-group">
            <label for="ntb-profile-firstname">{{ __('Prénom') }}</label>
            <input type="text" id="ntb-profile-firstname" name="first_name" value="{{ $user->first_name }}" />
        </div>

        <div class="ntb-form-group">
            <label for="ntb-profile-lastname">{{ __('Nom') }}</label>
            <input type="text" id="ntb-profile-lastname" name="last_name" value="{{ $user->last_name }}" />
        </div>

        <div class="ntb-form-group">
            <label for="ntb-profile-phone-num">{{ __('Téléphone') }}</label>
            @include('partials.phone-input', ['id' => 'ntb-profile-phone', 'name' => 'phone', 'value' => $user->phone])
        </div>

        <div class="ntb-form-actions ntb-form-actions--sep">
            <button type="submit" class="ntb-btn ntb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h10l4 4v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><circle cx="12" cy="14" r="2"/><path d="M8 4v4h6V4"/></svg>
                {{ __('Enregistrer') }}
            </button>
            <button type="button" id="ntb-delete-account" class="ntb-btn ntb-btn-ghost-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                {{ __('Supprimer mon compte') }}
            </button>
        </div>
    </form>

    <div id="ntb-profile-message" class="ntb-message" style="display: none;"></div>

    <div class="ntb-password-section">
        <h2 class="ntb-pw-section-title">{{ __('Mot de passe') }}</h2>

        @if ($hasPassword)
        <div class="ntb-pw-status">
            <span class="ntb-pw-status-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m9 12 2 2 4-4"/></svg>
                {{ __('Mot de passe défini') }}
            </span>
            <button type="button" id="ntb-pw-change-toggle" class="ntb-btn-link">{{ __('Modifier') }}</button>
        </div>
        <form id="ntb-password-form" class="ntb-profile-form" style="display:none;">
        @else
        <p class="ntb-help">{{ __('Définissez un mot de passe pour vous connecter sans lien magique.') }}</p>
        <form id="ntb-password-form" class="ntb-profile-form">
        @endif
            <div class="ntb-form-group">
                <label for="ntb-new-password">{{ $hasPassword ? __('Nouveau mot de passe') : __('Mot de passe') }}</label>
                <div class="ntb-pw-wrap">
                    <input type="password" id="ntb-new-password" autocomplete="new-password" minlength="8" placeholder="{{ __('8 caractères minimum') }}" />
                    <button type="button" class="ntb-pw-toggle" aria-label="{{ __('Afficher le mot de passe') }}">👁</button>
                </div>
            </div>
            <div class="ntb-form-actions">
                <button type="submit" class="ntb-btn ntb-btn-primary">{{ __('Enregistrer le mot de passe') }}</button>
                @if ($hasPassword)
                <button type="button" id="ntb-pw-change-cancel" class="ntb-btn ntb-btn-secondary">{{ __('Annuler') }}</button>
                @endif
            </div>
        </form>
        <div id="ntb-password-message" class="ntb-message" style="display: none;"></div>
    </div>

    <div class="ntb-export-section" style="margin-top:24px;">
        <a class="ntb-btn-link" href="{{ url('/api/v1/customer/me/export') }}">{{ __('Télécharger mes données (RGPD)') }}</a>
    </div>

    <div id="ntb-delete-modal" class="ntb-modal" style="display: none;">
        <div class="ntb-modal-content">
            <h3>{{ __('Supprimer votre compte') }}</h3>
            <p>{{ __('Êtes-vous sûr ? Cette action est irréversible.') }}</p>
            <div class="ntb-modal-actions">
                <button type="button" id="ntb-delete-confirm" class="ntb-btn ntb-btn-danger">{{ __('Supprimer') }}</button>
                <button type="button" id="ntb-delete-cancel" class="ntb-btn ntb-btn-secondary">{{ __('Annuler') }}</button>
            </div>
        </div>
    </div>

    <script>
        function ntbApi(path, method, body) {
            return fetch(NTB2_DATA.restUrl + path, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': NTB2_DATA.nonce },
                body: body ? JSON.stringify(body) : undefined
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); });
        }

        function ntbShow(el, res) {
            el.textContent = res.d.message || '';
            el.className = 'ntb-message ' + (res.ok ? 'ntb-message-success' : 'ntb-message-error');
            el.style.display = 'block';
        }

        // Toggle affichage formulaire mot de passe.
        var pwToggleBtn = document.getElementById('ntb-pw-change-toggle');
        var pwCancelBtn = document.getElementById('ntb-pw-change-cancel');
        var pwForm = document.getElementById('ntb-password-form');
        var pwStatusEl = document.querySelector('.ntb-pw-status');
        if (pwToggleBtn) {
            pwToggleBtn.addEventListener('click', function () {
                pwForm.style.display = 'block';
                pwStatusEl.style.display = 'none';
            });
        }
        if (pwCancelBtn) {
            pwCancelBtn.addEventListener('click', function () {
                pwForm.style.display = 'none';
                pwStatusEl.style.display = 'flex';
                document.getElementById('ntb-new-password').value = '';
                document.getElementById('ntb-password-message').style.display = 'none';
            });
        }

        document.querySelectorAll('.ntb-pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.previousElementSibling;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? '🙈' : '👁';
            });
        });

        document.getElementById('ntb-password-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var pwField = document.getElementById('ntb-new-password');
            ntbApi('/customer/me/password', 'POST', { password: pwField.value }).then(function (res) {
                ntbShow(document.getElementById('ntb-password-message'), res);
                if (res.ok) pwField.value = '';
            });
        });

        document.getElementById('ntb-profile-form').addEventListener('submit', function (e) {
            e.preventDefault();
            ntbApi('/customer/me', 'PATCH', {
                first_name: document.getElementById('ntb-profile-firstname').value,
                last_name: document.getElementById('ntb-profile-lastname').value,
                phone: document.getElementById('ntb-profile-phone').value
            }).then(function (res) { ntbShow(document.getElementById('ntb-profile-message'), res); });
        });

        // Changement d'email (confirmé par magic link vers la nouvelle adresse).
        document.getElementById('ntb-email-change-link').addEventListener('click', function (e) {
            e.preventDefault();
            var g = document.getElementById('ntb-email-change-group');
            g.style.display = (g.style.display === 'none') ? 'block' : 'none';
        });
        document.getElementById('ntb-email-change-send').addEventListener('click', function () {
            ntbApi('/customer/me/email', 'POST', { email: document.getElementById('ntb-new-email').value })
                .then(function (res) { ntbShow(document.getElementById('ntb-profile-message'), res); });
        });

        document.getElementById('ntb-delete-account').addEventListener('click', function () {
            document.getElementById('ntb-delete-modal').style.display = 'block';
        });
        document.getElementById('ntb-delete-cancel').addEventListener('click', function () {
            document.getElementById('ntb-delete-modal').style.display = 'none';
        });
        document.getElementById('ntb-delete-confirm').addEventListener('click', function () {
            ntbApi('/customer/me/delete', 'POST').then(function (res) {
                if (res.ok) { window.location.href = NTB2_DATA.homeUrl; }
                else {
                    document.getElementById('ntb-delete-modal').style.display = 'none';
                    ntbShow(document.getElementById('ntb-profile-message'), res);
                }
            });
        });
    </script>
</div>