@php
  $plans = \Illuminate\Support\Facades\DB::table('plans')->where('is_active', 1)->orderBy('price_monthly')->get();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderGuard — Detect risk. Prevent fraud. Recover deliveries.</title>
<meta name="description" content="OrderGuard helps COD online stores score risky orders before dispatch and recover failed deliveries.">
<meta property="og:type" content="website">
<meta property="og:title" content="OrderGuard — Detect risk. Prevent fraud. Recover deliveries.">
<meta property="og:description" content="Score risky COD orders before dispatch and recover failed deliveries.">
<meta property="og:image" content="{{ asset('images/landing/og-image.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/orderguard.css') }}">
<script>document.documentElement.classList.add('js')</script>
</head>
<body class="landing">
@include('partials.icons')

{{-- NAV --}}
<header class="l-nav" id="lnav">
  <a class="l-brand" href="/"><img src="{{ asset('images/logo1.png') }}" alt="" onerror="this.remove()"><span class="brand-name">order<span>guard</span></span></a>
  <nav id="lmenu">
    <a href="#features">Features</a>
    <a href="#demo">Live demo</a>
    <a href="#how">How it works</a>
    <a href="#pricing">Pricing</a>
    <a href="#faq">FAQ</a>
  </nav>
  <div class="l-actions">
    <a class="btn" href="{{ route('login') }}">Log in</a>
    <a class="btn primary" href="{{ route('register') }}">Start free trial</a>
    <button class="l-burger" id="lburger" aria-label="Menu"><svg class="ic" aria-hidden="true"><use href="#i-menu"/></svg></button>
  </div>
</header>

{{-- HERO --}}
<section class="l-hero">
  <div class="l-hero-text">
    <div class="eyebrow">For cash-on-delivery online stores</div>
    <h1>Detect risk. Prevent fraud. <em>Recover deliveries.</em></h1>
    <p>OrderGuard scores every order before dispatch, helps you confirm real customers, and turns failed deliveries into recovered parcels, all from one dashboard.</p>
    <div class="l-cta">
      <a class="btn primary big" href="{{ route('register') }}">Start your 14-day free trial</a>
      <a class="btn big" href="#demo">Try the live demo</a>
    </div>
    <small>No card needed · Set up in minutes</small>
  </div>

  <div class="l-hero-art">
    <img class="l-hero-img" src="{{ asset('images/landing/hero-shield.jpg') }}" alt="Shield protecting a parcel" onerror="this.remove()">
    <div class="float-card fc1">
      <span class="dot green"></span>
      <div><b>#OG-1042</b><small>Risk 18 · Low risk</small></div>
    </div>
    <div class="float-card fc2">
      <span class="dot red"></span>
      <div><b>#OG-1043</b><small>Risk 82 · Review first</small></div>
    </div>
    <div class="float-card fc3">
      <span class="dot purple"></span>
      <div><b>Parcel recovered</b><small>Address corrected</small></div>
    </div>
    <span class="sample-note">Illustrative examples</span>
  </div>
</section>

{{-- WORKS WITH --}}
<section class="l-strip reveal">
  <p>Built around the tools COD stores already use</p>
  <div class="chips">
    @foreach(['Shopify', 'WooCommerce', 'Leopards', 'TCS', 'PostEx', 'Trax', 'M&P', 'BlueEx', 'Call Courier'] as $c)
      <span>{{ $c }}</span>
    @endforeach
  </div>
</section>

{{-- PROBLEM --}}
<section class="l-section" id="problem">
  <h2 class="reveal">Two problems that quietly <em>cost you money</em></h2>
  <div class="l-grid two">
    <div class="l-card tint-red reveal">
      <span class="gicon g-red"><svg class="ic" aria-hidden="true"><use href="#i-shield-alert"/></svg></span>
      <h3>Risky and fake orders</h3>
      <p>Wrong phone numbers, duplicate orders and parcels refused at the door waste packing, courier and return costs.</p>
    </div>
    <div class="l-card tint-orange reveal" style="--d:.12s">
      <span class="gicon g-orange"><svg class="ic" aria-hidden="true"><use href="#i-undo"/></svg></span>
      <h3>Genuine parcels that fail</h3>
      <p>Not every return is fraud. Customers miss calls or give incomplete addresses, and good orders come back to you as RTO.</p>
    </div>
  </div>
</section>

{{-- FEATURES --}}
<section class="l-section" id="features">
  <h2 class="reveal">Everything in <em>one place</em></h2>
  <p class="l-sub reveal">From the moment an order arrives until the parcel is delivered.</p>
  <div class="l-grid three">
    @foreach([
      ['i-shield-alert', 'g-red',    'Order risk score',          'Every order gets a 0 to 100 score from rules you can adjust, so you review the right orders first.'],
      ['i-check-circle', 'g-green',  'Clear reasons',             'See exactly why an order was flagged, like an incomplete address or repeated unconfirmed orders.'],
      ['i-phone',        'g-purple', 'COD verification',          'Send a confirmation request and let the customer confirm or reject with one secure link.'],
      ['i-truck',        'g-orange', 'Shipment tracking',         'Track parcels by courier and spot failed deliveries the moment they happen.'],
      ['i-refresh',      'g-red',    'Recovery cases',            'Every failed delivery becomes a case. Contact the customer, fix the address or reschedule.'],
      ['i-pin',          'g-green',  'Customer recovery link',    'Customers correct their own address or pick a new delivery date, no login needed.'],
      ['i-route',        'g-purple', 'Courier performance',       'Compare delivery and return rates by courier and by city.'],
      ['i-chart',        'g-orange', 'Analytics and reports',     'Track RTO, recovery rate and estimated savings, and export CSV reports.'],
      ['i-users',        'g-red',    'Team access',               'Add managers, agents and view-only members with the right permissions.'],
    ] as $i => [$icon, $grad, $t, $d])
      <div class="l-card feat reveal" style="--d:{{ ($i % 3) * 0.1 }}s">
        <span class="gicon {{ $grad }}"><svg class="ic" aria-hidden="true"><use href="#{{ $icon }}"/></svg></span>
        <h3>{{ $t }}</h3>
        <p>{{ $d }}</p>
      </div>
    @endforeach
  </div>
</section>

{{-- LIVE DEMO --}}
<section class="l-section l-demo-wrap" id="demo">
  <h2 class="reveal">See how a risk score <em>is built</em></h2>
  <p class="l-sub reveal">Tick the signals an order might have. This uses the same default rules OrderGuard applies. It is an illustrative example, not real data.</p>

  <div class="demo reveal">
    <div class="demo-rules" id="demoRules">
      @foreach([
        ['Repeated unconfirmed orders', 25],
        ['Multiple recent failed deliveries', 20],
        ['Incomplete contact information', 15],
        ['Duplicate order pattern', 20],
        ['Blacklisted customer', 40],
        ['Unusually high order value', 10],
        ['Verified successful history', -20],
      ] as [$label, $pts])
        <label class="rule">
          <input type="checkbox" data-pts="{{ $pts }}">
          <span class="box"></span>
          <span class="rname">{{ $label }}</span>
          <b class="pts {{ $pts > 0 ? 'up' : 'down' }}">{{ $pts > 0 ? '+' : '' }}{{ $pts }}</b>
        </label>
      @endforeach
    </div>

    <div class="demo-gauge">
      <svg viewBox="0 0 120 120" class="gauge">
        <circle cx="60" cy="60" r="52" class="g-track"/>
        <circle cx="60" cy="60" r="52" class="g-bar" id="gBar" transform="rotate(-90 60 60)"/>
      </svg>
      <div class="g-center"><strong id="gScore">0</strong><small>out of 100</small></div>
      <span class="badge low" id="gBadge">Low risk</span>
      <p id="gAction">Safe to approve and dispatch.</p>
      <button class="btn" type="button" id="gReset">Reset</button>
    </div>
  </div>
  <p class="l-note reveal">A high score asks you to review or verify. It never rejects an order on its own.</p>
</section>

{{-- HOW IT WORKS --}}
<section class="l-section" id="how">
  <h2 class="reveal">How it <em>works</em></h2>
  <div class="l-steps">
    @foreach([
      ['icon-box.jpg',       'Connect your store',          'Link Shopify or WooCommerce so new orders arrive automatically.'],
      ['icon-risk.jpg',      'Orders are scored',           'Rules check each order and show a risk level with reasons.'],
      ['icon-verify.jpg',    'You decide',                  'Approve, hold or reject. Ask risky customers to confirm first.'],
      ['icon-recovery.jpg',  'Dispatch, track and recover', 'Ship with your courier, follow every parcel, and fix failed deliveries with the customer.'],
      ['icon-analytics.jpg', 'Measure the results',         'See your RTO, recovery rate and estimated savings in analytics.'],
    ] as $i => [$img, $t, $d])
      <div class="l-step reveal" style="--d:{{ $i * 0.08 }}s">
        <div class="step-ico">
          <img src="{{ asset('images/landing/'.$img) }}" alt="" onerror="this.remove()">
          <em>{{ $i + 1 }}</em>
        </div>
        <div><h3>{{ $t }}</h3><p>{{ $d }}</p></div>
      </div>
    @endforeach
  </div>
</section>

{{-- PRICING --}}
<section class="l-section" id="pricing">
  <h2 class="reveal">Simple <em>pricing</em></h2>
  <p class="l-sub reveal">Start free. Upgrade when your order volume grows.</p>
  <div class="l-grid four">
    @foreach($plans as $i => $p)
      @php $features = json_decode($p->features ?? '{}', true) ?: []; @endphp
      <div class="l-card plan {{ $p->code === 'growth' ? 'featured' : '' }} reveal" style="--d:{{ $i * 0.08 }}s">
        @if($p->code === 'growth')<span class="pop">Most popular</span>@endif
        <h3>{{ $p->name }}</h3>
        <div class="price">{{ $p->price_monthly > 0 ? '$'.rtrim(rtrim(number_format($p->price_monthly, 2), '0'), '.') : 'Free' }}<small>{{ $p->price_monthly > 0 ? ' / month' : ' for 14 days' }}</small></div>
        <ul>
          <li>{{ $p->monthly_order_limit ? number_format($p->monthly_order_limit).' orders / month' : 'Unlimited orders' }}</li>
          <li>{{ $p->max_stores }} store{{ $p->max_stores > 1 ? 's' : '' }}</li>
          <li>{{ $p->max_team_members }} team member{{ $p->max_team_members > 1 ? 's' : '' }}</li>
          <li>{{ $p->max_courier_accounts }} courier account{{ $p->max_courier_accounts > 1 ? 's' : '' }}</li>
          <li>Risk scoring</li>
          @if(!empty($features['recovery']))<li>Delivery recovery</li>@endif
          @if($p->whatsapp_enabled)<li>WhatsApp messaging</li>@endif
          @if(!empty($features['priority_support']))<li>Priority support</li>@endif
        </ul>
        <a class="btn {{ $p->code === 'growth' ? 'primary' : '' }}" style="width:100%;justify-content:center" href="{{ route('register') }}">{{ $p->price_monthly > 0 ? 'Start free trial' : 'Get started' }}</a>
      </div>
    @endforeach
  </div>
</section>

{{-- FAQ --}}
<section class="l-section" id="faq">
  <h2 class="reveal">Common <em>questions</em></h2>
  <div class="l-faq">
    @foreach([
      ['Is every returned parcel treated as fraud?', 'No. OrderGuard separates risky order behaviour from normal delivery failures. A genuine customer can miss a delivery, so failed deliveries go to a recovery flow instead.'],
      ['Will a high score reject the order automatically?', 'No. A high score asks you to review or verify. You make the final decision.'],
      ['Which stores can I connect?', 'Shopify and WooCommerce orders are supported through webhooks.'],
      ['Is my data separate from other merchants?', 'Yes. Each store only ever sees its own orders, customers and shipments.'],
      ['Do I need a card for the trial?', 'No. The 14-day trial starts as soon as you register.'],
    ] as $i => [$q, $a])
      <details class="reveal" style="--d:{{ $i * 0.05 }}s"><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
    @endforeach
  </div>
</section>

{{-- FINAL CTA --}}
<section class="l-final-wrap">
  <div class="l-final reveal">
    <h2>Ready to protect your orders?</h2>
    <p>Start your free trial and score your first order in minutes.</p>
    <a class="btn big white" href="{{ route('register') }}">Create your free account</a>
  </div>
</section>

<footer class="l-foot">© {{ date('Y') }} OrderGuard · Smarter orders. Fewer returns. · <a href="{{ route('login') }}">Log in</a></footer>

<script>
(function () {
  // Nav: shadow on scroll + mobile menu
  const nav = document.getElementById('lnav');
  const menu = document.getElementById('lmenu');
  document.getElementById('lburger').addEventListener('click', () => menu.classList.toggle('open'));
  menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => menu.classList.remove('open')));
  const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();

  // Reveal on scroll
  const items = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: .12 });
    items.forEach(el => io.observe(el));
  } else { items.forEach(el => el.classList.add('in')); }

  // Risk demo (same defaults as the product: low up to 30, medium up to 60)
  const boxes = document.querySelectorAll('#demoRules input');
  const bar = document.getElementById('gBar');
  const scoreEl = document.getElementById('gScore');
  const badge = document.getElementById('gBadge');
  const action = document.getElementById('gAction');
  const C = 2 * Math.PI * 52;
  bar.style.strokeDasharray = C;

  let shown = 0;
  function animateTo(target) {
    const start = shown, t0 = performance.now(), dur = 450;
    function tick(now) {
      const p = Math.min(1, (now - t0) / dur);
      shown = Math.round(start + (target - start) * (1 - Math.pow(1 - p, 3)));
      scoreEl.textContent = shown;
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function update() {
    let total = 0;
    boxes.forEach(b => { if (b.checked) total += parseInt(b.dataset.pts, 10); });
    const score = Math.max(0, Math.min(100, total));

    bar.style.strokeDashoffset = C * (1 - score / 100);
    animateTo(score);

    let level = 'low', text = 'Low risk', msg = 'Safe to approve and dispatch.', color = '#2fbf4a';
    if (score > 60)      { level = 'high';   text = 'High risk'; msg = 'Review this order or ask the customer to confirm before dispatch.'; color = '#e8112d'; }
    else if (score > 30) { level = 'medium'; text = 'Medium risk'; msg = 'Send a confirmation request before dispatching.'; color = '#f5a524'; }

    bar.style.stroke = color;
    badge.className = 'badge ' + level;
    badge.textContent = text;
    action.textContent = msg;
  }

  boxes.forEach(b => b.addEventListener('change', update));
  document.getElementById('gReset').addEventListener('click', () => { boxes.forEach(b => b.checked = false); update(); });
  update();
})();
</script>

</body>
</html>