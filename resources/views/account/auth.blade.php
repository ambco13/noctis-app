@php
    $authError = (string) request()->query('auth_error', '');
    $isReset = request()->query('reset') === '1';
    $resetToken = (string) request()->query('token', '');
    $resetEmail = (string) request()->query('email', '');

    $notices = [
        'expired' => __('Votre lien a expiré. Demandez-en un nouveau ci-dessous.'),
        'used' => __('Ce lien a déjà été utilisé. Demandez-en un nouveau ci-dessous.'),
        'invalid' => __('Ce lien est invalide. Demandez-en un nouveau ci-dessous.'),
        'session_expired' => __('Votre session a expiré. Veuillez vous reconnecter.'),
    ];
    $notice = $notices[$authError] ?? '';
@endphp

<div class="ntb-auth-section">

@if ($isReset)

    <h2>{{ __('Choisissez un nouveau mot de passe') }}</h2>

    <form id="ntb-reset-form" class="ntb-form">
        <input type="hidden" id="ntb-reset-token" value="{{ $resetToken }}" />
        <input type="hidden" id="ntb-reset-email" value="{{ $resetEmail }}" />
        <div class="ntb-form-group">
            <label for="ntb-reset-pw">{{ __('Nouveau mot de passe') }}</label>
            <div class="ntb-pw-wrap">
                <input type="password" id="ntb-reset-pw" autocomplete="new-password" required minlength="8" placeholder="{{ __('8 caractères minimum') }}" />
                <button type="button" class="ntb-pw-toggle" aria-label="{{ __('Afficher le mot de passe') }}">👁</button>
            </div>
        </div>
        <button type="submit" class="ntb-btn ntb-btn-primary">{{ __('Enregistrer le mot de passe') }}</button>
    </form>
    <div id="ntb-auth-message" class="ntb-message" style="display:none;"></div>

    <script>
    (function () {
        var f = document.getElementById('ntb-reset-form');
        var msg = document.getElementById('ntb-auth-message');
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch(NTB2_DATA.restUrl + '/auth/password/reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': NTB2_DATA.nonce },
                body: JSON.stringify({
                    token: document.getElementById('ntb-reset-token').value,
                    email: document.getElementById('ntb-reset-email').value,
                    password: document.getElementById('ntb-reset-pw').value
                })
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                msg.textContent = res.d.message || '';
                msg.className = 'ntb-message ' + (res.ok ? 'ntb-message-success' : 'ntb-message-error');
                msg.style.display = 'block';
                if (res.ok) {
                    setTimeout(function () { window.location.href = window.location.origin + window.location.pathname; }, 1800);
                }
            });
        });
    })();
    </script>

@else

    <h2>{{ __('Mon compte') }}</h2>

    @if ($notice)
        <div class="ntb-message ntb-message-error" style="display:block;">{{ $notice }}</div>
    @endif

    <!-- ÉTAPE 1 : saisie de l'email -->
    <div id="ntb-step-email">
        <form id="ntb-email-check-form" class="ntb-form">
            <div class="ntb-form-group">
                <label for="ntb-check-email">{{ __('Adresse email') }}</label>
                <input type="email" id="ntb-check-email" autocomplete="email" required placeholder="vous@exemple.fr" autofocus />
            </div>
            <button type="submit" class="ntb-btn ntb-btn-primary">{{ __('Continuer') }}</button>
        </form>
    </div>

    <!-- ÉTAPE 2a : compte existant → connexion -->
    <div id="ntb-step-login" style="display:none;">
        <p class="ntb-auth-email-recap">
            <span id="ntb-recap-email-login"></span>
            <a href="#" id="ntb-change-email-login" class="ntb-auth-change">{{ __('Modifier') }}</a>
        </p>

        <form id="ntb-login-form" class="ntb-form">
            <input type="hidden" id="ntb-login-email" />
            <div class="ntb-form-group">
                <label for="ntb-login-pw">{{ __('Mot de passe') }}</label>
                <div class="ntb-pw-wrap">
                    <input type="password" id="ntb-login-pw" autocomplete="current-password" />
                    <button type="button" class="ntb-pw-toggle" aria-label="{{ __('Afficher le mot de passe') }}">👁</button>
                </div>
            </div>
            <button type="submit" class="ntb-btn ntb-btn-primary">{{ __('Se connecter') }}</button>
            <p class="ntb-auth-help">
                <a href="#" id="ntb-forgot-link">{{ __('Mot de passe oublié ?') }}</a>
            </p>
        </form>

        <form id="ntb-forgot-form" class="ntb-form" style="display:none;">
            <p class="ntb-auth-help">{{ __('Un lien de réinitialisation sera envoyé à votre adresse.') }}</p>
            <button type="submit" class="ntb-btn ntb-btn-secondary">{{ __('Envoyer le lien de réinitialisation') }}</button>
        </form>

        <div class="ntb-auth-sep"><span>{{ __('ou') }}</span></div>

        <form id="ntb-magic-form" class="ntb-form">
            <button type="submit" class="ntb-btn ntb-btn-secondary">{{ __('Recevoir un lien de connexion') }}</button>
            <p class="ntb-auth-help">{{ __('Connexion sans mot de passe par email.') }}</p>
        </form>
    </div>

    <!-- ÉTAPE 2b : pas de compte → inscription -->
    <div id="ntb-step-register" style="display:none;">
        <p class="ntb-auth-email-recap">
            <span id="ntb-recap-email-register"></span>
            <a href="#" id="ntb-change-email-register" class="ntb-auth-change">{{ __('Modifier') }}</a>
        </p>

        <form id="ntb-register-form" class="ntb-form">
            <input type="hidden" id="ntb-reg-email" />
            <div class="ntb-form-group">
                <label for="ntb-reg-firstname">{{ __('Prénom') }}</label>
                <input type="text" id="ntb-reg-firstname" autocomplete="given-name" required />
            </div>
            <div class="ntb-form-group">
                <label for="ntb-reg-lastname">{{ __('Nom') }}</label>
                <input type="text" id="ntb-reg-lastname" autocomplete="family-name" required />
            </div>
            <div class="ntb-form-group">
                <label for="ntb-reg-pw">{{ __('Mot de passe (facultatif)') }}</label>
                <div class="ntb-pw-wrap">
                    <input type="password" id="ntb-reg-pw" autocomplete="new-password" minlength="8" placeholder="{{ __('8 caractères minimum') }}" />
                    <button type="button" class="ntb-pw-toggle" aria-label="{{ __('Afficher le mot de passe') }}">👁</button>
                </div>
            </div>
            <p class="ntb-auth-help">{{ __('Vous recevrez aussi un lien de connexion par email pour activer votre compte.') }}</p>
            <button type="submit" class="ntb-btn ntb-btn-primary">{{ __('Créer mon compte') }}</button>
        </form>
    </div>

    <div id="ntb-auth-message" class="ntb-message" style="display:none;"></div>

    <script>
    (function () {
        var msg = document.getElementById('ntb-auth-message');
        var redirectTo = window.location.origin + window.location.pathname;
        var currentEmail = '';

        function showMsg(text, ok) {
            msg.textContent = text || '';
            msg.className = 'ntb-message ' + (ok ? 'ntb-message-success' : 'ntb-message-error');
            msg.style.display = 'block';
        }
        function hideMsg() { msg.style.display = 'none'; }

        function post(path, body) {
            return fetch(NTB2_DATA.restUrl + path, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': NTB2_DATA.nonce },
                body: JSON.stringify(body)
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); });
        }

        function showStep(step) {
            document.getElementById('ntb-step-email').style.display = step === 'email' ? '' : 'none';
            document.getElementById('ntb-step-login').style.display = step === 'login' ? '' : 'none';
            document.getElementById('ntb-step-register').style.display = step === 'register' ? '' : 'none';
            hideMsg();
        }

        function goBack() {
            showStep('email');
            document.getElementById('ntb-login-pw').value = '';
            document.getElementById('ntb-forgot-form').style.display = 'none';
        }

        // Étape 1 : vérification email.
        document.getElementById('ntb-email-check-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var email = document.getElementById('ntb-check-email').value.trim();
            if (!email) return;
            currentEmail = email;

            post('/auth/check', { email: email }).then(function (res) {
                if (!res.ok) { showMsg(res.d.message, false); return; }
                if (res.d.exists) {
                    document.getElementById('ntb-recap-email-login').textContent = email;
                    document.getElementById('ntb-login-email').value = email;
                    showStep('login');
                    setTimeout(function () { document.getElementById('ntb-login-pw').focus(); }, 50);
                } else {
                    document.getElementById('ntb-recap-email-register').textContent = email;
                    document.getElementById('ntb-reg-email').value = email;
                    showStep('register');
                    setTimeout(function () { document.getElementById('ntb-reg-firstname').focus(); }, 50);
                }
            });
        });

        // Boutons "Modifier" (retour à l'email).
        document.getElementById('ntb-change-email-login').addEventListener('click', function (e) { e.preventDefault(); goBack(); });
        document.getElementById('ntb-change-email-register').addEventListener('click', function (e) { e.preventDefault(); goBack(); });

        // Connexion par mot de passe.
        document.getElementById('ntb-login-form').addEventListener('submit', function (e) {
            e.preventDefault();
            post('/auth/login', {
                email: document.getElementById('ntb-login-email').value,
                password: document.getElementById('ntb-login-pw').value
            }).then(function (res) {
                if (res.ok) { window.location.href = redirectTo; } else { showMsg(res.d.message, false); }
            });
        });

        // Mot de passe oublié — toggle.
        document.getElementById('ntb-forgot-link').addEventListener('click', function (e) {
            e.preventDefault();
            var f = document.getElementById('ntb-forgot-form');
            f.style.display = (f.style.display === 'none') ? 'block' : 'none';
        });

        // Mot de passe oublié — envoi (utilise currentEmail).
        document.getElementById('ntb-forgot-form').addEventListener('submit', function (e) {
            e.preventDefault();
            post('/auth/password/forgot', { email: currentEmail })
                .then(function (res) { showMsg(res.d.message, res.ok); });
        });

        // Lien magique (utilise currentEmail).
        document.getElementById('ntb-magic-form').addEventListener('submit', function (e) {
            e.preventDefault();
            post('/auth/magic-link/request', { email: currentEmail, redirect_to: redirectTo })
                .then(function (res) { showMsg(res.d.message, res.ok); });
        });

        // Inscription.
        document.getElementById('ntb-register-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var pw = document.getElementById('ntb-reg-pw').value;
            post('/auth/register', {
                email: document.getElementById('ntb-reg-email').value,
                first_name: document.getElementById('ntb-reg-firstname').value,
                last_name: document.getElementById('ntb-reg-lastname').value,
                password: pw || undefined
            }).then(function (res) {
                showMsg(res.d.message, res.ok);
                if (res.ok) {
                    document.getElementById('ntb-register-form').reset();
                    document.getElementById('ntb-reg-email').value = currentEmail;
                }
            });
        });

        // Boutons "voir le mot de passe".
        document.querySelectorAll('.ntb-pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.previousElementSibling;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? '🙈' : '👁';
            });
        });
    })();
    </script>

@endif

</div>