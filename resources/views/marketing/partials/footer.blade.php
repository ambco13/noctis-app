@php($footServices = config('marketing.services'))
@php($footContact = config('marketing.contact'))
<footer style="background:oklch(0.97 0.004 240);color:oklch(0.22 0.02 245);padding:80px 44px 40px;font-family:var(--font-sans)">
    <div style="max-width:1180px;margin:0 auto">
        <div style="font-family:var(--font-serif);font-size:clamp(40px,7vw,88px);letter-spacing:.04em;color:oklch(0.15 0.01 245);line-height:1;margin-bottom:56px">NOCTIS</div>
        <div class="mk-footer-grid" style="display:grid;grid-template-columns:1.6fr 1fr 1fr;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(20,30,50,.12)">
            <p style="font-size:15px;line-height:1.7;max-width:400px;margin:0">Based in Paris, we provide premium airport transfers and long-distance mobility solutions locally and across Europe — for corporate clients, VIP travelers and international passengers seeking discretion, punctuality and operational precision.</p>
            <div>
                <div style="font-size:11.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:oklch(0.22 0.02 245);margin-bottom:16px;opacity:.6">Services</div>
                <div style="display:flex;flex-direction:column;gap:11px">
                    @foreach ($footServices as $slug => $svc)
                        <a href="{{ route('marketing.service', ['slug' => $slug]) }}" style="font-size:14.5px;color:oklch(0.22 0.02 245)">{{ $svc['nav'] }}</a>
                    @endforeach
                </div>
            </div>
            <div>
                <div style="font-size:11.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:oklch(0.22 0.02 245);margin-bottom:16px;opacity:.6">Contact</div>
                <div style="font-size:14.5px;line-height:2">
                    {{ $footContact['address'] }}, {{ $footContact['city'] }}<br>{{ $footContact['phone'] }}<br>{{ $footContact['email'] }}<br>{{ $footContact['availability'] }}
                </div>
            </div>
        </div>
        <div style="padding-top:24px;font-size:12.5px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;opacity:.7">
            <span>© {{ date('Y') }} Noctis. All rights reserved.</span>
            <span style="font-family:var(--font-mono);letter-spacing:.06em">VTC · PARIS &amp; EUROPE</span>
        </div>
    </div>
</footer>
