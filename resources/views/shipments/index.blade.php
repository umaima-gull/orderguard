@extends('layouts.app')

@section('title', 'Shipments')
@section('crumb', 'Shipments')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>Shipments</h1>
    <p>Track every parcel and catch failed deliveries early.</p>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">In progress</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#c56cf0,#8e2fd0)"><svg class="ic" aria-hidden="true"><use href="#i-truck"/></svg></span></div>
    <div class="kpi-value">{{ $counts['active'] }}</div>
    <div class="kpi-foot">Booked or on the way</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Delivered</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#5bd96f,#1fa24a)"><svg class="ic" aria-hidden="true"><use href="#i-check-circle"/></svg></span></div>
    <div class="kpi-value">{{ $counts['delivered'] }}</div>
    <div class="kpi-foot">Successfully delivered</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Delivery exceptions</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ffc15a,#f08a0a)"><svg class="ic" aria-hidden="true"><use href="#i-shield-alert"/></svg></span></div>
    <div class="kpi-value">{{ $counts['exceptions'] }}</div>
    <div class="kpi-foot">Need follow-up</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Returned (RTO)</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ff6b7f,#e8112d)"><svg class="ic" aria-hidden="true"><use href="#i-undo"/></svg></span></div>
    <div class="kpi-value">{{ $counts['returned'] }}</div>
    <div class="kpi-foot">Back to seller</div>
  </article>
</section>

@if($ready->count())
<article class="card" style="margin-bottom:14px">
  <div class="card-head"><div><h3>Ready to dispatch</h3><p>Approved orders without a shipment</p></div></div>
  @foreach($ready as $o)
    <div class="factor">
      <div>
        <b>{{ $o->order_number }} · {{ $o->customer_name }}</b>
        <small>{{ $o->shipping_city }} · ₨ {{ number_format($o->total_amount) }}</small>
      </div>
      <form method="POST" action="{{ route('shipments.store') }}" class="inline-form">
        @csrf
        <input type="hidden" name="order_id" value="{{ $o->id }}">
        <select class="select" name="courier_id">
          @foreach($couriers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <button class="select ok" type="submit">Dispatch</button>
      </form>
    </div>
  @endforeach
</article>
@endif

<form method="GET" action="{{ route('shipments') }}" class="filters" id="filterForm">
  <input class="select" type="text" name="q" value="{{ request('q') }}" placeholder="Tracking, order or customer">
  <select class="select" name="status">
    <option value="">All statuses</option>
    @foreach(['booked','picked_up','in_transit','out_for_delivery','delivered','failed_attempt','on_hold','returning','returned','cancelled'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
    @endforeach
  </select>
  <select class="select" name="courier">
    <option value="">All couriers</option>
    @foreach($couriers as $c)
      <option value="{{ $c->id }}" @selected(request('courier') == $c->id)>{{ $c->name }}</option>
    @endforeach
  </select>
  <label style="font-size:11px;display:flex;align-items:center;gap:6px"><input type="checkbox" name="exceptions" value="1" @checked(request('exceptions'))> Exceptions only</label>
  <button class="btn primary" type="submit">Filter</button>
  <a class="btn" href="{{ route('shipments') }}" id="resetBtn">Reset</a>
  <a class="btn" href="{{ route('export', 'shipments') }}">Export CSV</a>
</form>
<article class="card" id="shipmentsResults">
  @include('shipments._table')
</article>

<script>
(function () {
  const form     = document.getElementById('filterForm');
  const results  = document.getElementById('shipmentsResults');
  const resetBtn = document.getElementById('resetBtn');
  const baseUrl  = form.getAttribute('action');
  let controller = null;
  let timer = null;

  async function loadShipments(url, push = true) {
    if (controller) controller.abort();
    controller = new AbortController();

    results.style.opacity = '0.5';
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store',
        signal: controller.signal
      });
      if (!res.ok) throw new Error('Request failed');
      results.innerHTML = await res.text();
      if (push) history.pushState({}, '', url);
      results.style.opacity = '1';
    } catch (e) {
      if (e.name === 'AbortError') return;
      results.style.opacity = '1';
      alert('Could not load shipments. Please try again.');
    }
  }

  function applyFilters() {
    const params = new URLSearchParams(new FormData(form)).toString();
    loadShipments(baseUrl + (params ? '?' + params : ''));
  }

  // Dropdowns + "Exceptions only" checkbox badalte hi filter
  form.querySelectorAll('select, input[type=checkbox]').forEach(function (el) {
    el.addEventListener('change', applyFilters);
  });

  // Typing: ruk kar (400ms) filter
  form.querySelector('input[name="q"]').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 400);
  });

  // Enter dabane par reload na ho
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearTimeout(timer);
    applyFilters();
  });

  // Reset
  resetBtn.addEventListener('click', function (e) {
    e.preventDefault();
    clearTimeout(timer);
    form.querySelectorAll('input[type=text]').forEach(i => i.value = '');
    form.querySelectorAll('input[type=checkbox]').forEach(i => i.checked = false);
    form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    loadShipments(baseUrl);
  });

  // Previous / Next
  results.addEventListener('click', function (e) {
    const link = e.target.closest('.pager a');
    if (!link) return;
    e.preventDefault();
    loadShipments(link.href);
  });

  // Browser back/forward
  window.addEventListener('popstate', function () {
    loadShipments(location.href, false);
  });
})();
</script>


@endsection