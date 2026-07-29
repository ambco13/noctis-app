@extends('layouts.booking-hero')

@section('title', __('Réservation'))

@section('content')
    @include('partials.booking-hero-form', ['dateLocale' => 'fr'])
@endsection
