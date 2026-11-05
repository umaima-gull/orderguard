@extends('layouts.app')

@section('title', 'Dashboard')
@section('crumb', 'Dashboard')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, <em>{{ strtok(auth()->user()->name, ' ') }}</em></h1>
    <p>Here’s what’s happening with your orders today.</p>
  </div>
  <div class="welcome-actions">
   <a class="btn" href="{{ route('export', 'orders') }}">
  <svg class="ic sm" aria-hidden="true"><use href="#i-download"/></svg>Export report
</a>
    <button class="btn primary" onclick="toast('New order form is a demo action.')">
      <svg class="ic sm" aria-hidden="true"><use href="#i-plus"/></svg>Add new order
    </button>
  </div>
</section>
@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif
<div class="notice">
  <div class="notice-icon"><svg class="ic sm" aria-hidden="true"><use href="#i-shield-alert"/></svg></div>
  <p><b>Attention needed:</b> {{ $flaggedCount }} orders have been flagged for risk review and {{ $rtoDueCount }} RTO cases need follow-up today.</p>
  <a href="{{ route('risk-intelligence') }}" style="color:var(--green);font-size:10px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
    Review now<svg class="ic xs" aria-hidden="true"><use href="#i-arrow-right"/></svg>
  </a>
</div>

{{-- KPI cards --}}
<section class="kpis">
  @foreach($kpis as $kpi)
    <article class="kpi {{ $loop->first ? 'primary' : '' }}">
      <div class="kpi-head">
        <span class="kpi-label">{{ $kpi['label'] }}</span>
        <span class="kpi-symbol"><svg class="ic" aria-hidden="true"><use href="#{{ $kpi['icon'] }}"/></svg></span>
      </div>
      <div class="kpi-value">{{ $kpi['value'] }}</div>
      <div class="kpi-foot"><span class="change">{{ $kpi['change'] }}</span><span class="sep">·</span> {{ $kpi['note'] }}</div>
    </article>
  @endforeach
</section>

<section class="grid-main">
  <article class="card">
    <div class="card-head">
      <div><h3>Order & recovery overview</h3><p>Track order volume against successfully recovered orders</p></div>
      <select class="select" onchange="location='?days='+this.value">
  @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $d => $label)
    <option value="{{ $d }}" @selected($days === $d)>{{ $label }}</option>
  @endforeach
</select>
    </div>
    <div class="chart-summary">
      <span><i class="legend-dot"></i> Orders <b>{{ number_format($chart['orders_total']) }}</b></span>
      <span><i class="legend-dot lime"></i> Recovered <b>{{ $chart['recovered_total'] }}</b></span>
    </div>
   @include('partials.trend-chart')
  </article>

  <article class="card">
    <div class="card-head">
      <div><h3>Fraud risk breakdown</h3><p>Risk levels across analysed orders</p></div>
      <button class="select" onclick="toast('Risk breakdown details are a demo view.')">Details<svg class="ic xs" aria-hidden="true"><use href="#i-arrow-up-right"/></svg></button>
    </div>
    <div class="risk-wrap">
      <div class="donut" style="background: {{ $donut }}"><div class="donut-center"><strong>{{ number_format($totalOrders) }}</strong><small>Orders scored</small></div></div>
      <div class="risk-list">
        @foreach($riskBreakdown as $row)
          <div class="risk-item"><i></i><span>{{ $row['label'] }}</span><b>{{ $row['percent'] }}%</b></div>
        @endforeach
      </div>
    </div>
    <div class="insight"><b>Risk insight:</b> Most flagged orders are linked to repeat failed deliveries and unverified contact details.</div>
  </article>
</section>

<section class="lower-grid">
  <article class="card">
    <div class="card-head">
      <div><h3>Orders requiring attention</h3><p>Review suspicious orders before dispatch</p></div>
      <a class="select" href="{{ route('orders') }}" style="text-decoration:none">View all<svg class="ic xs" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
    </div>
    <div class="table-wrap">
      <table id="ordersTable">
        <thead>
          <tr><th>ORDER / CUSTOMER</th><th>PRODUCT</th><th>RISK SCORE</th><th>RISK LEVEL</th><th>ORDER VALUE</th><th>ACTION</th></tr>
        </thead>
        <tbody>
       @forelse($orders as $order)
 @php
  [$level, $color, $text] = match($order['level']) {
    'low'    => ['low', 'green', 'Low risk'],
    'medium' => ['medium', 'yellow', 'Medium'],
    'high'   => ['high', 'red', 'High risk'],
    default  => ['', '', 'Not scored'],
  };
  $scoreText = $order['score'] === null ? '—' : $order['score'].'/100';
@endphp
  <tr>
    <td>
      <div class="customer">
        <span class="mini-avatar">{{ $order['initials'] }}<img src="{{ asset('images/products/'.$order['avatar']) }}" alt="{{ $order['customer'] }}" onerror="this.remove()"></span>
        <span><b class="order-id">{{ $order['id'] }}</b><br><span style="color:#87958d">{{ $order['customer'] }}</span></span>
      </div>
    </td>
    <td>
      <div class="prod">
        @if(!empty($order['image']))
          <img src="{{ asset('images/products/'.$order['image']) }}" alt="{{ $order['product'] }}" onerror="this.remove()">
        @endif
        <span>{{ $order['product'] }}</span>
      </div>
    </td>
    <td class="score {{ $color }}">{{ $scoreText }}</td>
    <td><span class="badge {{ $level }}" @if($level === '') style="background:#f1eef0;color:#8d8890" @endif>{{ $text }}</span></td>
    <td>₨ {{ number_format($order['value']) }}</td>
   <td><a class="select" href="{{ route('orders.show', $order['db_id']) }}" style="text-decoration:none">Review</a></td>
  </tr>
@empty
  <tr><td colspan="6" style="text-align:center;color:#8d8890;padding:24px">Abhi koi order nahi hai.</td></tr>
@endforelse
        </tbody>
      </table>
    </div>
  </article>

  <article class="card">
    <div class="card-head">
      <div><h3>RTO recovery queue</h3><p>Cases that need a follow-up</p></div>
      <a class="select" href="{{ route('rto-recovery') }}" style="text-decoration:none">See queue<svg class="ic xs" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
    </div>
    <div class="recovery-list">
   @forelse($recoveries as $r)
  <div class="recovery-item">
    <div class="rec-icon">
      @if(!empty($r['image']))
        <img src="{{ asset('images/products/'.$r['image']) }}" alt="" onerror="this.remove()">
      @endif
      <svg class="ic" aria-hidden="true"><use href="#{{ $r['icon'] }}"/></svg>
    </div>
    <div class="rec-info"><b>{{ $r['title'] }}</b><small>{{ $r['order'] }} · {{ $r['status'] }}</small></div>
    <div class="rec-value">₨ {{ number_format($r['amount']) }}<small>{{ $r['due'] }}</small></div>
  </div>
@empty
  <div style="text-align:center;color:#8d8890;padding:24px;font-size:11px">Koi open recovery case nahi.</div>
@endforelse
    </div>
  </article>
</section>

<section class="bottom-grid">
  <article class="card">
    <div class="card-head">
      <div><h3>Courier scorecard</h3><p>Delivery success rate by partner</p></div>
      <span style="font-size:9px;color:#87958d">This month</span>
    </div>
    @foreach($couriers as $c)
      <div class="courier-row">
        <div class="courier-meta"><b>{{ $c['name'] }}</b><span>{{ $c['rate'] }}%</span></div>
        <div class="progress"><i style="width:{{ $c['rate'] }}%"></i></div>
      </div>
    @endforeach
  </article>

  <article class="card">
    <div class="card-head">
      <div><h3>Latest activity</h3><p>Recent actions across your store</p></div>
      <span style="font-size:9px;color:#87958d">Live feed</span>
    </div>
    @foreach($activities as $a)
      <div class="activity">
        <i class="activity-pin" @if(!empty($a['color'])) style="background:{{ $a['color'] }}" @endif></i>
        <p><b>{{ $a['bold'] }}</b> {{ $a['text'] }}<small>{{ $a['time'] }}</small></p>
      </div>
    @endforeach
  </article>

  <article class="card">
    <div class="card-head"><div><h3>Quick actions</h3><p>Jump into your daily workflow</p></div></div>
    <div class="quick-actions">
      <a class="quick" href="{{ route('risk-intelligence') }}" style="text-decoration:none"><svg class="ic" aria-hidden="true"><use href="#i-shield-alert"/></svg><b>Review risky orders</b><small>Check flagged orders</small></a>
      <a class="quick" href="{{ route('cod-verification') }}" style="text-decoration:none"><svg class="ic" aria-hidden="true"><use href="#i-shield-check"/></svg><b>Verify COD</b><small>Confirm customers</small></a>
      <a class="quick" href="{{ route('rto-recovery') }}" style="text-decoration:none"><svg class="ic" aria-hidden="true"><use href="#i-undo"/></svg><b>Recover RTO</b><small>Manage follow-ups</small></a>
      <a class="quick" href="{{ route('analytics') }}" style="text-decoration:none"><svg class="ic" aria-hidden="true"><use href="#i-chart"/></svg><b>View analytics</b><small>Explore performance</small></a>
    </div>
  </article>
</section>

@endsection