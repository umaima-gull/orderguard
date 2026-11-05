@extends('layouts.app')

@section('title', 'Analytics')
@section('crumb', 'Analytics')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">Last {{ $days }} days</div>
    <h1><em>Analytics</em></h1>
    <p>Measure risk, delivery, recovery and savings.</p>
  </div>
  <div class="welcome-actions">
    <select class="select" onchange="location='?days='+this.value+'&cost={{ $cost }}'">
      @foreach([7, 30, 90] as $d)
        <option value="{{ $d }}" @selected($days === $d)>Last {{ $d }} days</option>
      @endforeach
    </select>
  </div>
</section>

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Orders analysed</span><span class="kpi-symbol"><svg class="ic" aria-hidden="true"><use href="#i-pkg"/></svg></span></div>
    <div class="kpi-value">{{ number_format($totalOrders) }}</div>
    <div class="kpi-foot">{{ $risk['high'] }} high risk</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Delivery rate</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#5bd96f,#1fa24a)"><svg class="ic" aria-hidden="true"><use href="#i-truck"/></svg></span></div>
    <div class="kpi-value">{{ $deliveryRate }}%</div>
    <div class="kpi-foot">{{ $shipTotal }} shipments</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">RTO rate</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ff6b7f,#e8112d)"><svg class="ic" aria-hidden="true"><use href="#i-undo"/></svg></span></div>
    <div class="kpi-value">{{ $rtoRate }}%</div>
    <div class="kpi-foot">Returned to seller</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Recovery rate</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#c56cf0,#8e2fd0)"><svg class="ic" aria-hidden="true"><use href="#i-refresh"/></svg></span></div>
    <div class="kpi-value">{{ $recoveryRate }}%</div>
    <div class="kpi-foot">{{ $recovered }} of {{ $casesTotal }} cases</div>
  </article>
</section>

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Verification rate</span></div>
    <div class="kpi-value">{{ $verifyRate }}%</div>
    <div class="kpi-foot">of {{ $asked }} orders asked to confirm</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Flag accuracy</span></div>
    <div class="kpi-value">{{ $accuracy }}%</div>
    <div class="kpi-foot">{{ $flaggedFailed }} of {{ $flaggedShipped }} flagged parcels failed</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Revenue recovered</span></div>
    <div class="kpi-value">₨ {{ number_format($revenue) }}</div>
    <div class="kpi-foot">Avg resolve time: {{ $avgHours !== null ? $avgHours.' h' : '—' }}</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Estimated savings</span></div>
    <div class="kpi-value">₨ {{ number_format($savings) }}</div>
    <div class="kpi-foot">{{ $prevented }} prevented + {{ $recovered }} recovered</div>
  </article>
</section>

<section class="grid-main">
  <article class="card">
    <div class="card-head"><div><h3>Orders and recovery</h3><p>Bars: orders per day. Dashed line: recovered parcels</p></div></div>
    <div class="chart-summary">
      <span><i class="legend-dot"></i> Orders <b>{{ $chart['orders_total'] }}</b></span>
      <span><i class="legend-dot lime"></i> Recovered <b>{{ $chart['recovered_total'] }}</b></span>
    </div>
    @include('partials.trend-chart')
  </article>

  <article class="card">
    <div class="card-head"><div><h3>Risk breakdown</h3><p>Orders by risk level</p></div></div>
    @foreach(['low' => ['Low risk', '#2fbf4a'], 'medium' => ['Medium', '#f5a524'], 'high' => ['High risk', '#e8112d'], 'none' => ['Not scored', '#b04be0']] as $k => [$label, $color])
      @php $p = $totalOrders ? round($risk[$k] / $totalOrders * 100) : 0; @endphp
      <div class="courier-row">
        <div class="courier-meta"><b>{{ $label }}</b><span>{{ $risk[$k] }} ({{ $p }}%)</span></div>
        <div class="progress"><i style="width:{{ $p }}%;background:{{ $color }}"></i></div>
      </div>
    @endforeach
  </article>
</section>

<section class="grid-main">
  <article class="card">
    <div class="card-head"><div><h3>Courier comparison</h3><p>Same period</p></div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>COURIER</th><th>SHIPMENTS</th><th>DELIVERED</th><th>RETURNED</th><th>DELIVERY RATE</th></tr></thead>
        <tbody>
        @forelse($couriers as $c)
          @php $r = $c->total ? round($c->delivered / $c->total * 100) : 0; @endphp
          <tr>
            <td><b>{{ $c->name }}</b></td><td>{{ $c->total }}</td><td>{{ (int) $c->delivered }}</td><td>{{ (int) $c->returned }}</td>
            <td style="min-width:110px"><div style="font-size:9px;margin-bottom:4px">{{ $r }}%</div><div class="progress"><i style="width:{{ $r }}%"></i></div></td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:#8d8890;padding:24px">No shipments in this period.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </article>

  <article class="card">
    <div class="card-head"><div><h3>Why deliveries fail</h3><p>Failed attempts by reason</p></div></div>
    @php $maxR = max(1, $reasons->max('c') ?? 1); @endphp
    @forelse($reasons as $r)
      <div class="courier-row">
        <div class="courier-meta"><b>{{ ucfirst(str_replace('_', ' ', $r->failure_reason)) }}</b><span>{{ $r->c }}</span></div>
        <div class="progress"><i style="width:{{ $r->c / $maxR * 100 }}%"></i></div>
      </div>
    @empty
      <div class="insight">No failed deliveries in this period.</div>
    @endforelse
  </article>
</section>

<article class="card">
  <div class="card-head"><div><h3>Savings assumption</h3><p>This is an estimate, not a measured result. Measure real savings during pilot testing.</p></div></div>
  <form method="GET" class="inline-form" style="flex-wrap:wrap">
    <input type="hidden" name="days" value="{{ $days }}">
    <span style="font-size:11px">Cost of one returned parcel (₨):</span>
    <input class="select num" type="number" name="cost" value="{{ $cost }}" min="0" step="50">
    <button class="select ok" type="submit">Recalculate</button>
    <span style="font-size:10px;color:#8d8890">Savings = (high-risk orders stopped + parcels recovered) × this cost</span>
  </form>
</article>

@endsection