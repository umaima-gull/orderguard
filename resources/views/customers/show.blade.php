@extends('layouts.app')

@section('title', $customer->name)
@section('crumb', 'Customers')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">
      @if($customer->is_blacklisted) Blacklisted @elseif($customer->delivered_orders >= 2 && $customer->rto_orders == 0) Trusted customer @else Customer @endif
    </div>
    <h1>{{ $customer->name }}</h1>
    <p>{{ $customer->phone ?: 'No phone' }} · {{ $customer->city ?: 'No city' }}</p>
  </div>
  <div class="welcome-actions">
    <form method="POST" action="{{ route('customers.refresh', $customer) }}">
      @csrf
      <button class="btn" type="submit"><svg class="ic sm" aria-hidden="true"><use href="#i-refresh"/></svg>Refresh stats</button>
    </form>
    <a class="btn" href="{{ route('customers') }}">← Back</a>
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
    <div class="kpi-head"><span class="kpi-label">Total orders</span></div>
    <div class="kpi-value">{{ $customer->total_orders }}</div>
    <div class="kpi-foot">₨ {{ number_format($customer->total_spent) }} spent (delivered)</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Success rate</span></div>
    <div class="kpi-value">{{ $rate }}%</div>
    <div class="kpi-foot">{{ $customer->delivered_orders }} delivered</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Returned (RTO)</span></div>
    <div class="kpi-value">{{ $customer->rto_orders }}</div>
    <div class="kpi-foot">{{ $customer->failed_deliveries }} failed deliveries</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Unconfirmed</span></div>
    <div class="kpi-value">{{ $customer->unconfirmed_orders }}</div>
    <div class="kpi-foot">{{ $customer->cancelled_orders }} cancelled or rejected</div>
  </article>
</section>

<section class="detail-grid">

  <article class="card">
    <div class="card-head"><div><h3>Order history</h3><p>Newest first</p></div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ORDER</th><th>ITEM</th><th>RISK</th><th>STATUS</th><th>SHIPMENT</th><th>VALUE</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
          @php
            $s = $o->risk_score;
            $lvl = $s === null ? '' : ($s <= 30 ? 'low' : ($s <= 60 ? 'medium' : 'high'));
            $ship = $o->shipments->first();
          @endphp
          <tr>
            <td><a href="{{ route('orders.show', $o) }}" class="order-id" style="text-decoration:none">{{ $o->order_number }}</a><br><span style="color:#87958d">{{ $o->placed_at->format('M j, Y') }}</span></td>
            <td>{{ $o->items->first()->name ?? '—' }}</td>
            <td>@if($lvl)<span class="badge {{ $lvl }}">{{ $s }}/100</span>@else —@endif</td>
            <td>{{ ucfirst(str_replace('_', ' ', $o->status)) }}</td>
            <td>
              @if($ship)
                <a href="{{ route('shipments.show', $ship) }}" style="color:#e8112d">{{ ucfirst(str_replace('_', ' ', $ship->status)) }}</a>
                @if($ship->failure_reason !== 'none')<br><small style="color:#87958d">{{ ucfirst(str_replace('_', ' ', $ship->failure_reason)) }}</small>@endif
              @else — @endif
            </td>
            <td>₨ {{ number_format($o->total_amount) }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;color:#8d8890;padding:24px">No orders yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </article>

  <div>
    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Blacklist</h3><p>Blacklisted customers get +40 risk on every new order</p></div></div>

      @if($customer->is_blacklisted)
        <p style="font-size:11px;margin:0 0 12px">Reason: <b>{{ $customer->blacklist_reason }}</b></p>
        <form method="POST" action="{{ route('customers.blacklist', $customer) }}">
          @csrf
          <input type="hidden" name="action" value="remove">
          <button class="btn" type="submit">Remove from blacklist</button>
        </form>
      @else
        <form method="POST" action="{{ route('customers.blacklist', $customer) }}" class="decision-form">
          @csrf
          <input type="hidden" name="action" value="add">
          <textarea name="reason" rows="2" maxlength="255" placeholder="Reason (required), e.g. Refused parcel three times" required></textarea>
          <button class="btn red" type="submit" onclick="return confirm('Blacklist this customer?')">Blacklist customer</button>
        </form>
      @endif
    </article>

    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Contact</h3></div></div>
      <dl class="kv">
        <dt>Phone</dt><dd>{{ $customer->phone ?: '—' }}</dd>
        <dt>Email</dt><dd>{{ $customer->email ?: '—' }}</dd>
        <dt>Address</dt><dd>{{ $customer->address_line1 ?: 'Missing' }}</dd>
        <dt>City</dt><dd>{{ $customer->city ?: '—' }}</dd>
        <dt>First order</dt><dd>{{ $customer->first_order_at?->format('M j, Y') ?? '—' }}</dd>
        <dt>Last order</dt><dd>{{ $customer->last_order_at?->format('M j, Y') ?? '—' }}</dd>
      </dl>
    </article>

    <article class="card">
      <div class="card-head"><div><h3>Internal notes</h3><p>Only your team sees this</p></div></div>
      <form method="POST" action="{{ route('customers.notes', $customer) }}" class="decision-form">
        @csrf
        <textarea name="notes" rows="4" maxlength="2000" placeholder="e.g. Prefers evening delivery">{{ $customer->notes }}</textarea>
        <button class="btn primary" type="submit">Save notes</button>
      </form>
    </article>
  </div>
</section>

@endsection