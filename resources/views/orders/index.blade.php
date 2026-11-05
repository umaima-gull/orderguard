@extends('layouts.app')

@section('title', 'Orders')
@section('crumb', 'Orders')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>Orders</h1>
    <p>Review every incoming order and decide before dispatch.</p>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

<form method="GET" action="{{ route('orders') }}" class="filters" id="filterForm">
  <input class="select" type="text" name="q" value="{{ request('q') }}" placeholder="Order number or customer">
  <select class="select" name="risk">
    <option value="">All risk levels</option>
    @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $k => $v)
      <option value="{{ $k }}" @selected(request('risk') === $k)>{{ $v }}</option>
    @endforeach
  </select>
  <select class="select" name="status">
    <option value="">All statuses</option>
    @foreach(['pending','under_review','verification_sent','approved','on_hold','rejected','dispatched','delivered','returned','cancelled'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
    @endforeach
  </select>
  <button class="btn primary" type="submit">Filter</button>
  <a class="btn" href="{{ route('orders') }}" id="resetBtn">Reset</a>
  <a class="btn" href="{{ route('export', 'orders') }}">Export CSV</a>
</form>

<article class="card" id="ordersResults">
  @include('orders._table')
</article>

<script>
(function () {
  const form     = document.getElementById('filterForm');
  const results  = document.getElementById('ordersResults');
  const resetBtn = document.getElementById('resetBtn');
  const baseUrl  = form.getAttribute('action');
  let controller = null;   // purani request cancel karne ke liye
  let timer = null;        // typing delay ke liye

  async function loadOrders(url, push = true) {
    if (controller) controller.abort();   // pehli request abhi chal rahi ho to rok do
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
      if (e.name === 'AbortError') return;   // naya request aa gaya, error nahi hai
      results.style.opacity = '1';
      alert('Could not load orders. Please try again.');
    }
  }

  function applyFilters() {
    const params = new URLSearchParams(new FormData(form)).toString();
    loadOrders(baseUrl + (params ? '?' + params : ''));
  }

  // Dropdown badalte hi filter
  form.querySelectorAll('select').forEach(function (s) {
    s.addEventListener('change', applyFilters);
  });

  // Typing: user ruke (400ms) to filter ho, har letter par nahi
  form.querySelector('input[name="q"]').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 400);
  });

  // Enter dabane par page reload na ho
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
    form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    loadOrders(baseUrl);
  });

  // Previous / Next
  results.addEventListener('click', function (e) {
    const link = e.target.closest('.pager a');
    if (!link) return;
    e.preventDefault();
    loadOrders(link.href);
  });

  // Browser back/forward
  window.addEventListener('popstate', function () {
    loadOrders(location.href, false);
  });
})();
</script>


@endsection