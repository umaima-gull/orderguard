@extends('layouts.app')

@section('title', 'Customers')
@section('crumb', 'Customers')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>Customers</h1>
    <p>Know who orders, who returns, and who to watch.</p>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Total customers</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#5bd96f,#1fa24a)"><svg class="ic" aria-hidden="true"><use href="#i-users"/></svg></span></div>
    <div class="kpi-value">{{ $counts['total'] }}</div>
    <div class="kpi-foot">In your store</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Repeat customers</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#c56cf0,#8e2fd0)"><svg class="ic" aria-hidden="true"><use href="#i-refresh"/></svg></span></div>
    <div class="kpi-value">{{ $counts['repeat'] }}</div>
    <div class="kpi-foot">2 or more orders</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">With delivery problems</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ffc15a,#f08a0a)"><svg class="ic" aria-hidden="true"><use href="#i-undo"/></svg></span></div>
    <div class="kpi-value">{{ $counts['problems'] }}</div>
    <div class="kpi-foot">Failed or returned parcels</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Blacklisted</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ff6b7f,#e8112d)"><svg class="ic" aria-hidden="true"><use href="#i-shield-alert"/></svg></span></div>
    <div class="kpi-value">{{ $counts['blacklisted'] }}</div>
    <div class="kpi-foot">Blocked by you</div>
  </article>
</section>

<form method="GET" action="{{ route('customers') }}" class="filters" id="filterForm">
  <input class="select" type="text" name="q" value="{{ request('q') }}" placeholder="Name, phone or city">
  <select class="select" name="filter">
    <option value="">All customers</option>
    <option value="trusted"     @selected(request('filter') === 'trusted')>Trusted (2+ delivered, no returns)</option>
    <option value="problems"    @selected(request('filter') === 'problems')>Has problems</option>
    <option value="blacklisted" @selected(request('filter') === 'blacklisted')>Blacklisted</option>
  </select>
  <button class="btn primary" type="submit">Filter</button>
  <a class="btn" href="{{ route('customers') }}" id="resetBtn">Reset</a>
  <a class="btn" id="exportBtn"
     data-base="{{ route('export', 'customers') }}"
     href="{{ route('export', ['type' => 'customers'] + request()->only('q', 'filter')) }}">Export CSV</a>
</form>

<article class="card" id="customersResults">
  @include('customers._table')
</article>

<script>
(function () {
  const form       = document.getElementById('filterForm');
  const results    = document.getElementById('customersResults');
  const resetBtn   = document.getElementById('resetBtn');
  const exportBtn  = document.getElementById('exportBtn');
  const baseUrl    = form.getAttribute('action');
  const exportBase = exportBtn.dataset.base;
  let controller = null;
  let timer = null;

  function filterParams() {
    const p = new URLSearchParams(new FormData(form));
    // Export ko page number nahi chahiye
    p.delete('page');
    return p.toString();
  }

  function updateExport() {
    const params = filterParams();
    exportBtn.href = exportBase + (params ? '?' + params : '');
  }

  async function loadCustomers(url, push = true) {
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
      alert('Could not load customers. Please try again.');
    }
  }

  function applyFilters() {
    const params = filterParams();
    updateExport();
    loadCustomers(baseUrl + (params ? '?' + params : ''));
  }

  form.querySelectorAll('select').forEach(s => s.addEventListener('change', applyFilters));

  form.querySelector('input[name="q"]').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 400);
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearTimeout(timer);
    applyFilters();
  });

  resetBtn.addEventListener('click', function (e) {
    e.preventDefault();
    clearTimeout(timer);
    form.querySelector('input[name="q"]').value = '';
    form.querySelector('select[name="filter"]').selectedIndex = 0;
    updateExport();
    loadCustomers(baseUrl);
  });

  results.addEventListener('click', function (e) {
    const link = e.target.closest('.pager a');
    if (!link) return;
    e.preventDefault();
    loadCustomers(link.href);
  });

  // Browser back/forward: form ko URL ke mutabiq karo
  window.addEventListener('popstate', function () {
    const p = new URLSearchParams(location.search);
    form.querySelector('input[name="q"]').value = p.get('q') || '';
    form.querySelector('select[name="filter"]').value = p.get('filter') || '';
    updateExport();
    loadCustomers(location.href, false);
  });
})();
</script>

@endsection