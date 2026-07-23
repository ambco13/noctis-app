@php
    $upcoming = $bookings['upcoming'] ?? [];
    $past = $bookings['past'] ?? [];
    $cancelled = $bookings['cancelled'] ?? [];
    $isEmpty = $upcoming === [] && $past === [] && $cancelled === [];

    $statusLabels = [
        'confirmed' => __('Confirmée'),
        'completed' => __('Terminée'),
        'cancelled' => __('Annulée'),
        'pending' => __('En attente'),
    ];
@endphp

@php
    // Carte de réservation (utilisée pour les trois sections).
    $card = function (array $b, string $extraClass = '') use ($statusLabels) {
        $status = $b['status'] ?? 'pending';
        $label = $statusLabels[$status] ?? ucfirst($status);
        $date = ! empty($b['ride_date']) ? \DateTime::createFromFormat('Y-m-d', $b['ride_date'])?->format('d/m/Y') : '';
        $when = trim($date.(! empty($b['ride_time']) ? ' · '.$b['ride_time'] : ''));
        ob_start(); ?>
        <div class="ntb-booking-card <?php echo e($extraClass); ?>" role="button" tabindex="0"
            data-booking="<?php echo e(json_encode($b)); ?>">
            <div class="ntb-booking-row1">
                <div class="ntb-booking-meta">
                    <span class="ntb-booking-ref"><?php echo e($b['booking_ref'] ?? ''); ?></span>
                    <span class="ntb-status ntb-status-<?php echo e($status); ?>"><?php echo e($label); ?></span>
                </div>
                <span class="ntb-booking-price"><?php echo e($b['price'] ?? ''); ?></span>
            </div>
            <div class="ntb-booking-row2">
                <div class="ntb-booking-route">
                    <span class="ntb-route-from" title="<?php echo e($b['pickup_address'] ?? ''); ?>"><?php echo e($b['pickup_address'] ?? ''); ?></span>
                    <span class="ntb-route-arrow">→</span>
                    <span class="ntb-route-to" title="<?php echo e($b['dropoff_address'] ?? ''); ?>"><?php echo e($b['dropoff_address'] ?? ''); ?></span>
                </div>
                <div class="ntb-booking-details">
                    <span class="ntb-booking-date"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--ntb-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg> <?php echo e($when); ?></span>
                    <span class="ntb-booking-vehicle"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ntb-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.6-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9L2.1 10.9A3 3 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg> <?php echo e($b['vehicle_name'] ?? ''); ?></span>
                </div>
            </div>
        </div>
        <?php return ob_get_clean();
    };
@endphp

<div class="ntb-bookings-tab">
    <h2>{{ __('Mes réservations') }}</h2>

    @if ($isEmpty)
        <div class="ntb-empty-state">
            <p class="ntb-empty">{{ __("Vous n'avez encore effectué aucune réservation.") }}</p>
            <a class="ntb-btn ntb-btn-primary" href="{{ route('booking.form') }}">
                {{ __('Réserver maintenant') }}
            </a>
        </div>
    @else

        @if ($upcoming !== [])
            <section class="ntb-bookings-section">
                <h3>{{ __('En cours') }}</h3>
                <div class="ntb-bookings-list">
                    @foreach ($upcoming as $b) {!! $card($b) !!} @endforeach
                </div>
            </section>
        @endif

        @if ($past !== [])
            <section class="ntb-bookings-section">
                <h3>{{ __('Terminées') }}</h3>
                <div class="ntb-bookings-list">
                    @foreach ($past as $b) {!! $card($b, 'ntb-booking-past') !!} @endforeach
                </div>
            </section>
        @endif

        @if ($cancelled !== [])
            <section class="ntb-bookings-section">
                <h3>{{ __('Annulées') }}</h3>
                <div class="ntb-bookings-list">
                    @foreach ($cancelled as $b) {!! $card($b, 'ntb-booking-cancelled') !!} @endforeach
                </div>
            </section>
        @endif

    @endif
</div><!-- .ntb-bookings-tab -->

<!-- Modal recap réservation -->
<div id="ntb-booking-modal" class="ntb-bmodal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="ntb-bmodal-ref">
    <div class="ntb-bmodal-backdrop"></div>
    <div class="ntb-bmodal-panel">
        <button type="button" class="ntb-bmodal-close" aria-label="{{ __('Fermer') }}">✕</button>
        <div id="ntb-bmodal-body" class="ntb-summary-body"></div>
    </div>
</div>

<script>
(function () {
    var labels = {
        confirmed: @json(__('Confirmée')),
        completed: @json(__('Terminée')),
        cancelled: @json(__('Annulée')),
        pending: @json(__('En attente'))
    };
    var modal = document.getElementById('ntb-booking-modal');
    var body = document.getElementById('ntb-bmodal-body');

    function formatDate(d) {
        if (!d) return '';
        var p = d.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    function row(label, value) {
        if (!value) return '';
        return '<div class="ntb-summary-row"><span class="ntb-summary-label">' + label + '</span><span class="ntb-summary-value">' + value + '</span></div>';
    }

    function openModal(b) {
        var status = b.status || 'pending';
        var label = labels[status] || status;
        var html = '';
        html += '<div class="ntb-summary-status ntb-summary-status--' + status + '">' + label + '</div>';
        html += row(@json(__('Référence')), b.booking_ref);
        html += row(@json(__('Départ')), b.pickup_address);
        html += row(@json(__('Arrivée')), b.dropoff_address);
        html += row(@json(__('Date')), formatDate(b.ride_date));
        html += row(@json(__('Heure')), b.ride_time);
        html += row(@json(__('Véhicule')), b.vehicle_name);
        if (b.price) {
            html += '<div class="ntb-summary-row ntb-summary-total"><span class="ntb-summary-label">' + @json(__('Total')) + '</span><span class="ntb-summary-value">' + b.price + '</span></div>';
        }
        body.innerHTML = html;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        modal.querySelector('.ntb-bmodal-panel').focus();
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.ntb-booking-card').forEach(function (card) {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function () {
            try { openModal(JSON.parse(card.dataset.booking)); } catch (e) {}
        });
        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
        });
    });

    modal.querySelector('.ntb-bmodal-close').addEventListener('click', closeModal);
    modal.querySelector('.ntb-bmodal-backdrop').addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') closeModal();
    });
})();
</script>
