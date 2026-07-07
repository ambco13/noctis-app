@extends('layouts.admin')

@section('title', __('Véhicules'))

@php use App\Support\Settings; @endphp

@section('content')
<h1 class="ntb-page-title">{{ __('Véhicules') }}</h1>

<table class="nadm-table" style="margin-bottom:26px;">
    <thead>
    <tr>
        <th>{{ __('Ordre') }}</th><th>{{ __('Nom') }}</th><th>{{ __('Prise en charge') }}</th>
        <th>€/km</th><th>€/min</th><th>{{ __('Minimum') }}</th>
        <th>{{ __('Places') }}</th><th>{{ __('Actif') }}</th><th></th>
    </tr>
    </thead>
    <tbody>
    @forelse ($vehicles as $v)
        <tr>
            <td>{{ $v->sort_order }}</td>
            <td><strong>{{ $v->name }}</strong></td>
            <td>{{ Settings::formatPrice($v->base_fare) }}</td>
            <td>{{ number_format($v->price_per_km, 2, ',', ' ') }}</td>
            <td>{{ number_format($v->price_per_min, 2, ',', ' ') }}</td>
            <td>{{ Settings::formatPrice($v->min_price) }}</td>
            <td>{{ $v->capacity }} / 🧳{{ $v->luggage }}</td>
            <td>{{ $v->is_active ? '✔' : '—' }}</td>
            <td style="white-space:nowrap;">
                <a class="nadm-btn nadm-btn--light" href="{{ route('admin.vehicles.edit', $v) }}">{{ __('Éditer') }}</a>
                <form method="post" action="{{ route('admin.vehicles.destroy', $v) }}" class="nadm-inline"
                      onsubmit="return confirm(@json(__('Supprimer ce véhicule ?')));">
                    @csrf @method('DELETE')
                    <button class="nadm-btn nadm-btn--danger" type="submit">✕</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="9">{{ __('Aucun véhicule.') }}</td></tr>
    @endforelse
    </tbody>
</table>

<div class="nadm-form">
    <h2 style="margin-top:0;">{{ $editing ? __('Modifier « :name »', ['name' => $editing->name]) : __('Ajouter un véhicule') }}</h2>

    <form method="post" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.vehicles.update', $editing) : route('admin.vehicles.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <label>{{ __('Nom') }}</label>
        <input type="text" name="name" required value="{{ old('name', $editing->name ?? '') }}">

        <label>{{ __('Description') }}</label>
        <textarea name="description" rows="2">{{ old('description', $editing->description ?? '') }}</textarea>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            <div><label>{{ __('Prise en charge (€)') }}</label>
                <input type="number" step="0.01" min="0" name="base_fare" required value="{{ old('base_fare', $editing->base_fare ?? '5.00') }}"></div>
            <div><label>{{ __('Prix / km (€)') }}</label>
                <input type="number" step="0.01" min="0" name="price_per_km" required value="{{ old('price_per_km', $editing->price_per_km ?? '1.80') }}"></div>
            <div><label>{{ __('Prix / min (€)') }}</label>
                <input type="number" step="0.01" min="0" name="price_per_min" required value="{{ old('price_per_min', $editing->price_per_min ?? '0.40') }}"></div>
            <div><label>{{ __('Prix minimum (€)') }}</label>
                <input type="number" step="0.01" min="0" name="min_price" required value="{{ old('min_price', $editing->min_price ?? '15.00') }}"></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
            <div><label>{{ __('Passagers') }}</label>
                <input type="number" min="1" max="99" name="capacity" required value="{{ old('capacity', $editing->capacity ?? 4) }}"></div>
            <div><label>{{ __('Bagages') }}</label>
                <input type="number" min="0" max="99" name="luggage" required value="{{ old('luggage', $editing->luggage ?? 2) }}"></div>
            <div><label>{{ __("Ordre d'affichage") }}</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $editing->sort_order ?? 0) }}"></div>
        </div>

        <label>{{ __('Image') }}</label>
        @if ($editing?->image_path)
            <div style="margin-bottom:6px;"><img src="{{ asset($editing->image_path) }}" alt="" style="max-height:70px;border-radius:6px;"></div>
        @endif
        <input type="file" name="image" accept="image/*">

        <label style="display:flex;align-items:center;gap:8px;margin-top:16px;">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing->is_active ?? true))>
            {{ __('Véhicule actif (proposé à la réservation)') }}
        </label>

        <div style="margin-top:18px;display:flex;gap:10px;">
            <button class="nadm-btn" type="submit">{{ $editing ? __('Enregistrer') : __('Ajouter') }}</button>
            @if ($editing)
                <a class="nadm-btn nadm-btn--light" href="{{ route('admin.vehicles') }}">{{ __('Annuler') }}</a>
            @endif
        </div>
    </form>
</div>
@endsection