@extends('layouts.admin')

@section('title', __('Réservations'))

@php use App\Support\Settings; @endphp

@section('content')
<h1 class="ntb-page-title">{{ __('Réservations') }}</h1>

<form method="get" style="margin-bottom:16px;display:flex;gap:8px;align-items:center;">
    <input type="text" name="s" value="{{ $s }}" placeholder="{{ __('Réf., nom ou email…') }}"
           style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;">
    <select name="status" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;">
        <option value="">{{ __('Tous les statuts') }}</option>
        @foreach (['pending', 'confirmed', 'completed', 'cancelled', 'failed'] as $st)
            <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
        @endforeach
    </select>
    <button class="nadm-btn nadm-btn--light" type="submit">{{ __('Filtrer') }}</button>
</form>

<table class="nadm-table">
    <thead>
    <tr>
        <th>{{ __('Réf.') }}</th><th>{{ __('Client') }}</th><th>{{ __('Trajet') }}</th>
        <th>{{ __('Course') }}</th><th>{{ __('Prix') }}</th><th>{{ __('Paiement') }}</th><th>{{ __('Statut') }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($bookings as $b)
        <tr>
            <td><a href="{{ route('admin.bookings.show', $b) }}">{{ $b->booking_ref }}</a></td>
            <td>{{ $b->full_name }}<br><small>{{ $b->email }}</small></td>
            <td>{{ \Illuminate\Support\Str::limit($b->pickup_address, 28) }} → {{ \Illuminate\Support\Str::limit($b->dropoff_address, 28) }}</td>
            <td>{{ $b->ride_date?->format('d/m/Y') }} {{ $b->ride_time ? substr($b->ride_time, 0, 5) : '' }}</td>
            <td>{{ Settings::formatPrice($b->price) }}</td>
            <td>{{ $b->payment_status }}@if($b->payment_method) ({{ $b->payment_method }})@endif</td>
            <td><span class="nadm-badge nadm-badge--{{ $b->status }}">{{ $b->status }}</span></td>
        </tr>
    @empty
        <tr><td colspan="7">{{ __('Aucune réservation trouvée.') }}</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:14px;">{{ $bookings->links() }}</div>
@endsection