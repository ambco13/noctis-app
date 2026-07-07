@extends('layouts.admin')

@section('title', __('Clients'))

@section('content')
<h1 class="ntb-page-title">{{ __('Clients') }}</h1>

<table class="nadm-table">
    <thead>
    <tr>
        <th>{{ __('Nom') }}</th><th>{{ __('Email') }}</th><th>{{ __('Téléphone') }}</th>
        <th>{{ __('Courses') }}</th><th>{{ __('Inscrit le') }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($customers as $c)
        <tr>
            <td>{{ $c->full_name }}</td>
            <td>{{ $c->email }}</td>
            <td>{{ $c->phone }}</td>
            <td>{{ $c->rides }}</td>
            <td>{{ $c->created_at?->format('d/m/Y') }}</td>
        </tr>
    @empty
        <tr><td colspan="5">{{ __('Aucun client pour le moment.') }}</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:14px;">{{ $customers->links() }}</div>
@endsection