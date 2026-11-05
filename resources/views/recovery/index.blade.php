@extends('layouts.app')

@section('title', 'RTO Recovery')
@section('crumb', 'RTO Recovery')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>RTO <em>Recovery</em></h1>
    <p>Contact customers and save parcels before they return to you.</p>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Open cases</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#c56cf0,#8e2fd0)"><svg class="ic" aria-hidden="true"><use href="#i-undo"/></svg></span></div>
    <div class="kpi-value">{{ $counts['open'] }}</div>
    <div class="kpi-foot">Waiting for follow-up</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Due today</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ffc15a,#f08a0a)"><svg class="ic" aria-hidden="true"><use href="#i-bell"/></svg></span></div>
    <div class="kpi-value">{{ $counts['due'] }}</div>
    <div class="kpi-foot">Due now or overdue</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Recovered</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#5bd96f,#1fa24a)"><svg class="ic" aria-hidden="true"><use href="#i-check-circle"/></svg></span></div>
    <div class="kpi-value">{{ $counts['recovered'] }}</div>
    <div class="kpi-foot">{{ $counts['rate'] }}% recovery rate</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Revenue recovered</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ff6b7f,#e8112d)"><svg class="ic" aria-hidden="true"><use href="#i-trend"/></svg></span></div>
    <div class="kpi-value">₨ {{ number_format($counts['revenue']) }}</div>
    <div class="kpi-foot">From recovered parcels</div>
  </article>
</section>

<form method="GET" action="{{ route('rto-recovery') }}" class="filters" id="filterForm">
  <select class="select" name="view">
    <option value="open"   @selected($view === 'open')>Open cases</option>
    <option value="closed" @selected($view === 'closed')>Closed cases</option>
    <option value="all"    @selected($view === 'all')>All cases</option>
  </select>
  <select class="select" name="type">
    <option value="">All issues</option>
    @foreach($types as $t)
      <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
    @endforeach
  </select>
  <button class="btn primary" type="submit">Filter</button>
  <a class="btn" href="{{ route('rto-recovery') }}" id="resetBtn">Reset</a>
  <a class="btn" id="exportBtn"
     data-base="{{ route('export', 'recovery') }}"
         href="{{ route('export', 'recovery') }}">Export CSV</a>
</form>

<article class="card" id="recoveryResults">
  @include('recovery._table')
</article>

<script>
(function () {
  const form       = document.getElementById('filterForm');
  const results    = document.getElementById('recoveryResults');
  const resetBtn   = document.getElementById('resetBtn');
  const exportBtn  = document.getElementById('exportBtn');
  const baseUrl    = form.getAttribute('action');
  const exportBase = exportBtn.dataset.base;
  let controller = null;

  function updateExport(params) {
    exportBtn.href = exportBase + (params ? '?' + params : '');
  }

  async function loadCases(url, push = true) {
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
      alert('Could not load cases. Please try again.');
    }
  }

  function applyFilters() {
    const params = new URLSearchParams(new FormData(form)).toString();
    updateExport(params);
    loadCases(baseUrl + (params ? '?' + params : ''));
  }

  form.querySelectorAll('select').forEach(function (s) {
    s.addEventListener('change', applyFilters);
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    applyFilters();
  });

  resetBtn.addEventListener('click', function (e) {
    e.preventDefault();
    form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    updateExport('view=open');
    loadCases(baseUrl);
  });

  results.addEventListener('click', function (e) {
    const link = e.target.closest('.pager a');
    if (!link) return;
    e.preventDefault();
    loadCases(link.href);
  });

  window.addEventListener('popstate', function () {
    loadCases(location.href, false);
  });
})();
</script>

@endsection