@extends('layouts.marketing')

@section('title', 'Contact Us — Noctis')

@section('content')
    <section class="ntb-mk-section" style="min-height:70vh">
        <div class="ntb-mk-wrap ntb-mk-wrap--narrow">
            @if (session('status'))
                <div class="ntb-mk-notice" role="status">{{ session('status') }}</div>
            @endif

            <div class="ntb-mk-contact">
                <div>
                    <h1 class="ntb-mk-h1" style="font-size:32px;margin:0 0 20px">Contact Us</h1>
                    <p class="ntb-mk-body" style="line-height:1.7;margin:0 0 24px">Private chauffeur, sightseeing tour? Fill out the form with your questions or comments and we will get back to you.</p>
                    <div class="ntb-mk-contact-info">
                        {{ $contact['address'] }}, {{ $contact['city'] }}<br>
                        Phone: {{ $contact['phone'] }}<br>
                        Email: {{ $contact['email'] }}<br>
                        {{ $contact['availability'] }}
                    </div>
                </div>

                <form class="ntb-mk-form" method="post" action="{{ route('marketing.contact.submit') }}">
                    @csrf
                    <div>
                        <label for="c-name">Your Name</label>
                        <div class="ntb-mk-form-line"><input type="text" id="c-name" name="name" value="{{ old('name') }}" required></div>
                        @error('name')<div class="ntb-mk-field-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="c-email">Email address</label>
                        <div class="ntb-mk-form-line"><input type="email" id="c-email" name="email" value="{{ old('email') }}" required></div>
                        @error('email')<div class="ntb-mk-field-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="c-phone">Phone Number (optional)</label>
                        <div class="ntb-mk-form-line"><input type="text" id="c-phone" name="phone" value="{{ old('phone') }}"></div>
                        @error('phone')<div class="ntb-mk-field-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="c-message">Message</label>
                        <div class="ntb-mk-form-line"><textarea id="c-message" name="message" rows="3" required>{{ old('message') }}</textarea></div>
                        @error('message')<div class="ntb-mk-field-err">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="ntb-mk-btn" style="margin-top:8px">Send Message</button>
                </form>
            </div>
        </div>
    </section>
@endsection
