@extends('layouts.marketing')

@section('title', "Contact — Noctis")

@php
    $contact = config('marketing.contact');
    $lbl = 'font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--ntb-faint)';
    $inp = 'background:none;border:none;outline:none;color:var(--ntb-text);font-size:16px;width:100%;font-family:var(--font-sans);padding:2px 0';
    $rows = [
        ['Address', $contact['address'] . ', ' . $contact['city'], 'pin'],
        ['Phone', $contact['phone'], 'phone'],
        ['Email', $contact['email'], 'mail'],
        ['Hours', $contact['availability'], 'clock'],
    ];
@endphp

@section('content')
    <section style="padding:96px 44px 120px;background:var(--ntb-bg);min-height:70vh">
        <div data-reveal class="g-2" style="max-width:1180px;margin:0 auto;gap:72px;align-items:start">
            <div>
                <p class="eyb" style="margin-bottom:22px">Contact</p>
                <h1 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(38px,6vw,80px);line-height:1;letter-spacing:-.02em;color:var(--ntb-text);margin:0 0 24px">Let's talk.</h1>
                <p style="font-size:17px;color:var(--ntb-muted);line-height:1.7;margin:0 0 40px;max-width:420px">Private chauffeur, sightseeing tour or a bespoke itinerary? Send us your questions and we'll get back to you.</p>

                @if (session('status'))
                    <div style="background:var(--ntb-accent-soft);border:1px solid var(--ntb-accent-line);color:var(--ntb-text);border-radius:14px;padding:14px 18px;font-size:14px;margin-bottom:24px" role="status">{{ session('status') }}</div>
                @endif

                <div style="padding:28px 30px;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:18px;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                    @foreach ($rows as $i => [$k, $v, $ic])
                        <div style="display:flex;justify-content:space-between;gap:16px;padding:13px 0;border-bottom:{{ $i < count($rows) - 1 ? '1px solid var(--ntb-line-soft)' : 'none' }}">
                            <span style="display:inline-flex;align-items:center;gap:11px"><x-mk-icon :name="$ic" :size="18" color="var(--ntb-accent)" /><span style="{{ $lbl }};align-self:center">{{ $k }}</span></span>
                            <span style="font-size:14.5px;color:var(--ntb-text);text-align:right">{{ $v }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="post" action="{{ route('marketing.contact.submit') }}" style="display:flex;flex-direction:column;gap:26px">
                @csrf
                @php($fields = ['name' => 'Your Name', 'email' => 'Email address', 'phone' => 'Phone Number (optional)'])
                @foreach ($fields as $fname => $flabel)
                    <div>
                        <label for="c-{{ $fname }}" style="{{ $lbl }}">{{ $flabel }}</label>
                        <div class="field-line" style="padding:9px 0;margin-top:4px"><input id="c-{{ $fname }}" name="{{ $fname }}" type="{{ $fname === 'email' ? 'email' : 'text' }}" value="{{ old($fname) }}" style="{{ $inp }}" @if($fname !== 'phone') required @endif></div>
                        @error($fname)<div style="color:var(--ntb-danger,#d33);font-size:12.5px;margin-top:5px">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div>
                    <label for="c-message" style="{{ $lbl }}">Message</label>
                    <div class="field-line" style="padding:9px 0;margin-top:4px"><textarea id="c-message" name="message" rows="4" required style="{{ $inp }};resize:none;font-family:inherit">{{ old('message') }}</textarea></div>
                    @error('message')<div style="color:var(--ntb-danger,#d33);font-size:12.5px;margin-top:5px">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="cta" style="margin-top:6px;align-self:flex-start;background:var(--ntb-accent);color:#fff;font-weight:700;font-size:15px;padding:15px 34px;border:none;border-radius:999px;cursor:pointer">Send Message</button>
            </form>
        </div>
    </section>
@endsection
