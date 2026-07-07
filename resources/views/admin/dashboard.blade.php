@extends('layouts.admin')

@section('title', __('Tableau de bord'))

@php use App\Support\Settings; @endphp

@section('content')
<h1 class="ntb-page-title">{{ __('NOCTIS Taxi — Tableau de bord') }}</h1>

<div class="ntb-section-label">{{ __("Chiffre d'affaires") }}</div>
<div class="ntb-cards ntb-revenue-grid">
    <div class="ntb-card ntb-card--revenue">
        <div class="ntb-card-value">{{ Settings::formatPrice($revenue_today) }}</div>
        <div class="ntb-card-label">{{ __("Aujourd'hui") }}</div>
    </div>
    <div class="ntb-card ntb-card--revenue">
        <div class="ntb-card-value">{{ Settings::formatPrice($revenue_week) }}</div>
        <div class="ntb-card-label">{{ __('7 derniers jours') }}</div>
    </div>
    <div class="ntb-card ntb-card--revenue">
        <div class="ntb-card-value">{{ Settings::formatPrice($revenue_month) }}</div>
        <div class="ntb-card-label">{{ __('Ce mois-ci') }}</div>
    </div>
    <div class="ntb-card ntb-card--accent ntb-card--revenue">
        <div class="ntb-card-value">{{ Settings::formatPrice($revenue_total) }}</div>
        <div class="ntb-card-label">{{ __('CA total') }}</div>
    </div>
</div>

<div class="ntb-section-label">{{ __('Réservations & activité') }}</div>
<div class="ntb-cards ntb-stats-grid">
    <a class="ntb-card ntb-card--link" href="{{ route('admin.bookings') }}">
        <div class="ntb-card-value">{{ $counts['total'] ?? 0 }}</div>
        <div class="ntb-card-label">{{ __('Total réservations') }}</div>
    </a>
    <a class="ntb-card ntb-card--link" href="{{ route('admin.bookings', ['status' => 'confirmed']) }}">
        <div class="ntb-card-value">{{ $counts['confirmed'] ?? 0 }}</div>
        <div class="ntb-card-label">{{ __('Confirmées') }}</div>
    </a>
    <a class="ntb-card ntb-card--link" href="{{ route('admin.bookings', ['status' => 'pending']) }}">
        <div class="ntb-card-value">{{ $counts['pending'] ?? 0 }}</div>
        <div class="ntb-card-label">{{ __('En attente') }}</div>
    </a>
    <a class="ntb-card ntb-card--link" href="{{ route('admin.vehicles') }}">
        <div class="ntb-card-value">{{ $count_vehicles }}</div>
        <div class="ntb-card-label">{{ __('Véhicules actifs') }}</div>
    </a>
    <a class="ntb-card ntb-card--link" href="{{ route('admin.customers') }}">
        <div class="ntb-card-value">{{ $count_customers }}</div>
        <div class="ntb-card-label">{{ __('Clients') }}</div>
    </a>
</div>

<div class="ntb-section-label">{{ __('30 derniers jours') }}</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;">
    @include('admin._bar_chart', ['title' => __('Revenus confirmés'), 'series' => $chart_revenue, 'money' => true])
    @include('admin._bar_chart', ['title' => __('Nouvelles réservations'), 'series' => $chart_bookings, 'money' => false])
</div>
<script>
    // Tooltip partagé des graphiques : suit la cible pleine hauteur survolée.
    document.querySelectorAll('.nadm-chart-hit').forEach(function (hit) {
        var wrap = hit.closest('div[style*="position:relative"]');
        var tip = wrap ? wrap.querySelector('.nadm-chart-tip') : null;
        if (! tip) return;
        hit.addEventListener('mousemove', function (e) {
            tip.hidden = false;
            tip.textContent = hit.dataset.label + ' — ' + hit.dataset.value;
            var box = wrap.getBoundingClientRect();
            tip.style.left = Math.min(e.clientX - box.left + 12, box.width - tip.offsetWidth - 4) + 'px';
            tip.style.top = (e.clientY - box.top - 30) + 'px';
        });
        hit.addEventListener('mouseleave', function () { tip.hidden = true; });
    });
</script>

<div class="ntb-section-label">{{ __('Dernières réservations') }}</div>
<table class="nadm-table">
    <thead>
    <tr>
        <th>{{ __('Réf.') }}</th><th>{{ __('Client') }}</th><th>{{ __('Trajet') }}</th>
        <th>{{ __('Date') }}</th><th>{{ __('Prix') }}</th><th>{{ __('Statut') }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($recent as $b)
        <tr>
            <td><a href="{{ route('admin.bookings.show', $b) }}">{{ $b->booking_ref }}</a></td>
            <td>{{ $b->full_name }}</td>
            <td>{{ \Illuminate\Support\Str::limit($b->pickup_address, 25) }} → {{ \Illuminate\Support\Str::limit($b->dropoff_address, 25) }}</td>
            <td>{{ $b->ride_date?->format('d/m/Y') }} {{ $b->ride_time ? substr($b->ride_time, 0, 5) : '' }}</td>
            <td>{{ Settings::formatPrice($b->price) }}</td>
            <td><span class="nadm-badge nadm-badge--{{ $b->status }}">{{ $b->status }}</span></td>
        </tr>
    @empty
        <tr><td colspan="6">{{ __('Aucune réservation pour le moment.') }}</td></tr>
    @endforelse
    </tbody>
</table>
@endsection