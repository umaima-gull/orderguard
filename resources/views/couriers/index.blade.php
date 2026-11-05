@extends('layouts.app')

@section('title', 'Courier Partners')
@section('crumb', 'Courier Partners')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>Courier <em>Partners</em></h1>
    <p>Compare delivery performance and manage your courier connections.</p>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif
@if($errors->any())
  <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
@endif

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Active couriers</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#c56cf0,#8e2fd0)"><svg class="ic" aria-hidden="true"><use href="#i-route"/></svg></span></div>
    <div class="kpi-value">{{ $summary['connected'] }}</div>
    <div class="kpi-foot">Connected and turned on</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Delivery rate</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#5bd96f,#1fa24a)"><svg class="ic" aria-hidden="true"><use href="#i-check-circle"/></svg></span></div>
    <div class="kpi-value">{{ $summary['rate'] }}%</div>
    <div class="kpi-foot">Across all shipments</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">RTO rate</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ff6b7f,#e8112d)"><svg class="ic" aria-hidden="true"><use href="#i-undo"/></svg></span></div>
    <div class="kpi-value">{{ $summary['rto'] }}%</div>
    <div class="kpi-foot">Returned to seller</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Best performer</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ffc15a,#f08a0a)"><svg class="ic" aria-hidden="true"><use href="#i-trend"/></svg></span></div>
    <div class="kpi-value" style="font-size:20px">{{ $summary['best']->account->courier->name ?? '—' }}</div>
    <div class="kpi-foot">{{ $summary['best'] ? $summary['best']->rate . '% delivered' : 'No shipments yet' }}</div>
  </article>
</section>

<article class="card" style="margin-bottom:14px">
  <div class="card-head"><div><h3>Connected couriers</h3><p>Performance is calculated from your real shipments</p></div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>COURIER</th><th>SHIPMENTS</th><th>DELIVERY</th><th>RTO</th><th>AVG DAYS</th><th>CAPABILITIES</th><th>STATUS</th><th>ACTIONS</th></tr></thead>
      <tbody>
      @forelse($rows as $r)
        @php $a = $r->account; $cr = $a->courier; @endphp
        <tr>
          <td>
            <b>{{ $cr->name }}</b>
            @if($a->is_default)<span class="badge low" style="margin-left:4px">Default</span>@endif
            <br><span style="color:#87958d">{{ $a->label }}</span>
          </td>
          <td>{{ $r->total }}</td>
          <td style="min-width:110px">
            @if($r->rate !== null)
              <div style="font-size:9px;margin-bottom:4px">{{ $r->rate }}%</div>
              <div class="progress"><i style="width:{{ $r->rate }}%"></i></div>
            @else — @endif
          </td>
          <td class="score {{ $r->rto !== null && $r->rto >= 30 ? 'red' : '' }}">{{ $r->rto !== null ? $r->rto.'%' : '—' }}</td>
          <td>{{ $r->days ?? '—' }}</td>
          <td style="white-space:normal;max-width:200px">
            <span class="tag {{ $cr->supports_api ? 'down' : 'up' }}">API</span>
            <span class="tag {{ $cr->supports_reschedule ? 'down' : 'up' }}">Reschedule</span>
            <span class="tag {{ $cr->supports_address_update ? 'down' : 'up' }}">Address update</span>
          </td>
          <td>
            <span class="badge {{ $a->is_active ? 'low' : 'high' }}">{{ $a->is_active ? 'On' : 'Off' }}</span>
            <br><small style="color:#87958d">
              @if($a->credentials) {{ $a->last_verified_at ? 'Verified '.$a->last_verified_at->diffForHumans() : 'Not verified' }} @else No credentials @endif
            </small>
          </td>
          <td>
            <div class="inline-form" style="flex-wrap:wrap">
              @unless($a->is_default)
                <form method="POST" action="{{ route('courier-partners.default', $a) }}">@csrf<button class="select ok" type="submit">Make default</button></form>
              @endunless
              <form method="POST" action="{{ route('courier-partners.toggle', $a) }}">@csrf<button class="select" type="submit">{{ $a->is_active ? 'Turn off' : 'Turn on' }}</button></form>
              <form method="POST" action="{{ route('courier-partners.test', $a) }}">@csrf<button class="select" type="submit">Test connection</button></form>
              @unless($a->is_default)
                <form method="POST" action="{{ route('courier-partners.disconnect', $a) }}" onsubmit="return confirm('Disconnect {{ $cr->name }}?')">@csrf<button class="select no" type="submit">Disconnect</button></form>
              @endunless
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="8" style="padding-top:0">
            <details class="cred">
              <summary>API credentials {{ $a->credentials ? '(saved, hidden)' : '(not set)' }}</summary>
              <form method="POST" action="{{ route('courier-partners.credentials', $a) }}" class="inline-form" style="flex-wrap:wrap;margin-top:8px">
                @csrf
                <input class="select" type="password" name="api_key" autocomplete="off" placeholder="{{ $a->credentials ? 'API key (leave blank to keep)' : 'API key' }}">
                <input class="select" type="text" name="account_number" autocomplete="off" placeholder="Account number">
                <button class="select ok" type="submit">Save securely</button>
              </form>
              <small style="color:#8d8890">Stored encrypted. Saved values are never shown again.</small>
            </details>
          </td>
        </tr>
      @empty
        <tr><td colspan="8" style="text-align:center;color:#8d8890;padding:24px">No couriers connected yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</article>

@if($available->count())
<article class="card" style="margin-bottom:14px">
  <div class="card-head"><div><h3>Connect a courier</h3><p>Add another delivery partner</p></div></div>
  <form method="POST" action="{{ route('courier-partners.connect') }}" class="inline-form" style="flex-wrap:wrap">
    @csrf
    <select class="select" name="courier_id">
      @foreach($available as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
    </select>
    <input class="select" type="text" name="label" maxlength="100" placeholder="Label (optional), e.g. Lahore account">
    <button class="select ok" type="submit">Connect</button>
  </form>
</article>
@endif

<article class="card">
  <div class="card-head">
    <div><h3>Performance by city</h3><p>Find which courier works best in each city</p></div>
    <form method="GET" class="inline-form">
      <select class="select" name="courier" onchange="this.form.submit()">
        <option value="">All couriers</option>
        @foreach($accounts as $a)
          <option value="{{ $a->courier->name }}" @selected(request('courier') === $a->courier->name)>{{ $a->courier->name }}</option>
        @endforeach
      </select>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>COURIER</th><th>CITY</th><th>SHIPMENTS</th><th>DELIVERED</th><th>RETURNED</th><th>DELIVERY RATE</th><th>RTO RATE</th><th>AVG DAYS</th></tr></thead>
      <tbody>
      @forelse($cities as $c)
        <tr>
          <td><b>{{ $c->courier }}</b></td>
          <td>{{ $c->delivery_city ?: '—' }}</td>
          <td>{{ $c->total_shipments }}</td>
          <td>{{ (int) $c->delivered }}</td>
          <td>{{ (int) $c->returned }}</td>
          <td class="score {{ $c->delivery_rate_pct >= 70 ? 'green' : ($c->delivery_rate_pct >= 40 ? 'yellow' : 'red') }}">{{ $c->delivery_rate_pct }}%</td>
          <td>{{ $c->rto_rate_pct }}%</td>
          <td>{{ $c->avg_delivery_days ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="8" style="text-align:center;color:#8d8890;padding:24px">No shipment data yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</article>

@endsection