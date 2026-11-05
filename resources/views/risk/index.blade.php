@extends('layouts.app')

@section('title', 'Risk Intelligence')
@section('crumb', 'Risk Intelligence')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>Risk <em>Intelligence</em></h1>
    <p>Orders flagged by your fraud rules, and the rules behind them.</p>
  </div>
  <div class="welcome-actions">
    <form method="POST" action="{{ route('risk.rescore') }}">
      @csrf
      <button class="btn primary" type="submit"><svg class="ic sm" aria-hidden="true"><use href="#i-refresh"/></svg>Re-score all orders</button>
    </form>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

{{-- Summary cards --}}
<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">High risk</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ff6b7f,#e8112d)"><svg class="ic" aria-hidden="true"><use href="#i-shield-alert"/></svg></span></div>
    <div class="kpi-value">{{ $counts['high'] }}</div>
    <div class="kpi-foot">Score above {{ $store->medium_risk_max }}</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Medium risk</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ffc15a,#f08a0a)"><svg class="ic" aria-hidden="true"><use href="#i-shield-alert"/></svg></span></div>
    <div class="kpi-value">{{ $counts['medium'] }}</div>
    <div class="kpi-foot">Score {{ $store->low_risk_max + 1 }} to {{ $store->medium_risk_max }}</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Low risk</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#5bd96f,#1fa24a)"><svg class="ic" aria-hidden="true"><use href="#i-shield-check"/></svg></span></div>
    <div class="kpi-value">{{ $counts['low'] }}</div>
    <div class="kpi-foot">Score 0 to {{ $store->low_risk_max }}</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Not scored</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#c56cf0,#8e2fd0)"><svg class="ic" aria-hidden="true"><use href="#i-pkg"/></svg></span></div>
    <div class="kpi-value">{{ $counts['none'] }}</div>
    <div class="kpi-foot">Waiting for evaluation</div>
  </article>
</section>

{{-- Flagged orders --}}
<article class="card" style="margin-bottom:14px">
  <div class="card-head">
    <div><h3>Flagged orders</h3><p>Medium and high risk orders, highest score first</p></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ORDER / CUSTOMER</th><th>SCORE</th><th>LEVEL</th><th>TOP REASONS</th><th>VALUE</th><th>ACTION</th></tr></thead>
      <tbody>
      @forelse($flagged as $o)
        @php $lvl = $o->risk_level; @endphp
        <tr>
          <td><b class="order-id">{{ $o->order_number }}</b><br><span style="color:#87958d">{{ $o->customer_name }}</span></td>
          <td class="score {{ $lvl === 'high' ? 'red' : 'yellow' }}">{{ $o->risk_score }}/100</td>
          <td><span class="badge {{ $lvl }}">{{ $lvl === 'high' ? 'High risk' : 'Medium' }}</span></td>
          <td style="white-space:normal;max-width:340px">
            @foreach($o->latestRisk?->factors->sortByDesc('score_impact')->take(3) ?? [] as $f)
              <span class="tag {{ $f->score_impact > 0 ? 'up' : 'down' }}">{{ $f->label }} ({{ $f->score_impact > 0 ? '+' : '' }}{{ $f->score_impact }})</span>
            @endforeach
          </td>
          <td>₨ {{ number_format($o->total_amount) }}</td>
          <td><a class="select" href="{{ route('orders.show', $o) }}" style="text-decoration:none">Review</a></td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:#8d8890;padding:24px">No flagged orders. Great news!</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</article>

{{-- Rules --}}
<article class="card">
  <div class="card-head">
    <div><h3>Fraud rules</h3><p>Positive score adds risk, negative score reduces it. Changes apply on the next scoring.</p></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>RULE</th><th>DESCRIPTION</th><th>SCORE IMPACT</th><th>ACTIVE</th><th></th></tr></thead>
      <tbody>
      @foreach($rules as $rule)
        <tr>
          <form method="POST" action="{{ route('risk.rule.update', $rule) }}">
            @csrf
            <td><b>{{ $rule->name }}</b></td>
            <td style="white-space:normal;color:#6d666b;max-width:340px">{{ $rule->description }}</td>
            <td><input class="select num" type="number" name="score_impact" value="{{ $rule->score_impact }}" min="-100" max="100"></td>
            <td>
              <select class="select" name="is_active">
                <option value="1" @selected($rule->is_active)>On</option>
                <option value="0" @selected(!$rule->is_active)>Off</option>
              </select>
            </td>
            <td><button class="select" type="submit">Save</button></td>
          </form>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</article>

@endsection