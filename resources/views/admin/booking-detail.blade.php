@extends('layouts.admin')

@section('title', $booking->booking_ref)

@php use App\Support\Settings; @endphp

@section('content')
<h1 class="ntb-page-title">{{ __('Réservation') }} {{ $booking->booking_ref }}
    <span class="nadm-badge nadm-badge--{{ $booking->status }}">{{ $booking->status }}</span>
</h1>

<div class="nadm-form">
    <table class="nadm-table">
        <tbody>
        <tr><th>{{ __('Client') }}</th><td>{{ $booking->full_name }} — {{ $booking->email }} — {{ $booking->phone }}</td></tr>
        <tr><th>{{ __('Départ') }}</th><td>{{ $booking->pickup_address }}</td></tr>
        <tr><th>{{ __('Arrivée') }}</th><td>{{ $booking->dropoff_address }}</td></tr>
        <tr><th>{{ __('Course') }}</th><td>{{ $booking->ride_date?->format('d/m/Y') }} {{ $booking->ride_time ? 'à '.substr($booking->ride_time, 0, 5) : '' }}</td></tr>
        <tr><th>{{ __('Véhicule') }}</th><td>{{ $booking->vehicle_name }}</td></tr>
        <tr><th>{{ __('Distance / durée') }}</th><td>{{ $booking->distance_km }} km · {{ round($booking->duration_min) }} min</td></tr>
        <tr><th>{{ __('Prix') }}</th><td><strong>{{ Settings::formatPrice($booking->price) }}</strong> {{ $booking->currency }}</td></tr>
        <tr><th>{{ __('Paiement') }}</th><td>{{ $booking->payment_status }} @if($booking->payment_method)({{ $booking->payment_method }})@endif</td></tr>
        <tr><th>{{ __('Transaction') }}</th><td>{{ $booking->transaction_id ?: '—' }}</td></tr>
        <tr><th>{{ __('Message client') }}</th><td>{{ $booking->customer_message ?: '—' }}</td></tr>
        <tr><th>{{ __('Créée le') }}</th><td>{{ $booking->created_at?->format('d/m/Y H:i') }}</td></tr>
        </tbody>
    </table>

    <div style="margin-top:18px;display:flex;gap:10px;align-items:center;">
        <form method="post" action="{{ route('admin.bookings.status', $booking) }}" class="nadm-inline">
            @csrf
            <select name="status" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;">
                @foreach (['pending', 'confirmed', 'completed', 'cancelled', 'failed'] as $st)
                    <option value="{{ $st }}" @selected($booking->status === $st)>{{ $st }}</option>
                @endforeach
            </select>
            <button class="nadm-btn" type="submit">{{ __('Changer le statut') }}</button>
        </form>

        <form method="post" action="{{ route('admin.bookings.destroy', $booking) }}" class="nadm-inline"
              onsubmit="return confirm(@json(__('Supprimer définitivement cette réservation ?')));">
            @csrf
            @method('DELETE')
            <button class="nadm-btn nadm-btn--danger" type="submit">{{ __('Supprimer') }}</button>
        </form>

        <a class="nadm-btn nadm-btn--light" href="{{ route('admin.bookings') }}">{{ __('Retour') }}</a>
    </div>
</div>
@endsection