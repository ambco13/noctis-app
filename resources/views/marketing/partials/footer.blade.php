@php($footServices = config('marketing.services'))
@php($footContact = config('marketing.contact'))
<footer class="ntb-mk-footer">
    <div class="ntb-mk-footer-grid">
        <div>
            <div class="ntb-mk-footer-brand">NOCTIS</div>
            <p>Based in Paris, we provide premium airport transfers and long-distance mobility solutions locally and across Europe — for corporate clients, VIP travelers and international passengers seeking discretion, punctuality and operational precision.</p>
        </div>
        <div>
            <div class="ntb-mk-footer-head">Services</div>
            <div class="ntb-mk-footer-links">
                @foreach ($footServices as $slug => $svc)
                    <a href="{{ route('marketing.service', ['slug' => $slug]) }}">{{ $svc['nav'] }}</a>
                @endforeach
            </div>
        </div>
        <div>
            <div class="ntb-mk-footer-head">Contact</div>
            <div class="ntb-mk-footer-contact">
                {{ $footContact['address'] }}, {{ $footContact['city'] }}<br>
                {{ $footContact['phone'] }}<br>
                {{ $footContact['email'] }}<br>
                {{ $footContact['availability'] }}
            </div>
        </div>
    </div>
    <div class="ntb-mk-footer-bottom">
        <span>© {{ date('Y') }} Noctis. Tous droits réservés.</span>
        <span class="mono">VTC · Paris &amp; Europe</span>
    </div>
</footer>
