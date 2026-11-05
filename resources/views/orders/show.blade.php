@extends('layouts.app')

@section('title', $order->order_number)
@section('crumb', 'Orders')

@section('content')

@php
  $risk  = $order->latestRisk;
  $score = $order->risk_score;
  if ($score === null)  { $lvl = '';       $col = '';       $txt = 'Not scored'; }
  elseif ($score <= 30) { $lvl = 'low';    $col = 'green';  $txt = 'Low risk'; }
  elseif ($score <= 60) { $lvl = 'medium'; $col = 'yellow'; $txt = 'Medium risk'; }
  else                  { $lvl = 'high';   $col = 'red';    $txt = 'High risk'; }
  $c = $order->customer;
@endphp

<section class="welcome">
  <div>
    <div class="eyebrow">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</div>
    <h1>Order <em>{{ $order->order_number }}</em></h1>
    <p>Placed {{ $order->placed_at->format('M j, Y · g:i A') }} · {{ $order->placed_at->diffForHumans() }}</p>
  </div>
  <div class="welcome-actions">
    <a class="btn" href="{{ route('orders') }}">← Back to orders</a>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

<section class="detail-grid">

  {{-- LEFT: risk assessment --}}
  <article class="card">
    <div class="card-head">
      <div><h3>Risk assessment</h3><p>Why this order received its score</p></div>
      <form method="POST" action="{{ route('orders.rescore', $order) }}">
        @csrf
        <button class="select" type="submit"><svg class="ic xs" aria-hidden="true"><use href="#i-refresh"/></svg>Recalculate</button>
      </form>
    </div>

    <div style="display:flex;align-items:center;gap:18px;margin-bottom:14px">
      <div class="big-score score {{ $col }}">{{ $score === null ? '—' : $score }}<small style="font-size:14px;color:#8d8890">/100</small></div>
      <div>
        <span class="badge {{ $lvl }}" @if($lvl === '') style="background:#f1eef0;color:#8d8890" @endif>{{ $txt }}</span>
        @if($risk)
          <div style="font-size:11px;color:#6d666b;margin-top:8px">Recommended action: <b>{{ ucfirst($risk->recommended_action) }}</b></div>
        @endif
      </div>
    </div>

    @if($risk && $risk->factors->count())
      @foreach($risk->factors as $f)
        <div class="factor">
          <div>
            <b>{{ $f->label }}</b>
            <small>{{ $f->description }}</small>
          </div>
          <span class="impact {{ $f->score_impact > 0 ? 'up' : 'down' }}">{{ $f->score_impact > 0 ? '+' : '' }}{{ $f->score_impact }}</span>
        </div>
      @endforeach
    @elseif($risk)
      <div class="insight">No risk factors were triggered for this order.</div>
    @else
      <div class="insight">This order has not been scored yet. Click "Recalculate".</div>
    @endif
  </article>

  {{-- RIGHT: decision --}}
  <article class="card">
    <div class="card-head"><div><h3>Your decision</h3><p>Approve, hold or reject before dispatch</p></div></div>

    <form method="POST" action="{{ route('orders.decide', $order) }}" class="decision-form">
      @csrf
      <textarea name="note" rows="3" maxlength="500" placeholder="Optional note (e.g. confirmed on phone)"></textarea>
      <div class="btn-row">
        <button class="btn green" type="submit" name="decision" value="approved">Approve</button>
        <button class="btn amber" type="submit" name="decision" value="held">Hold</button>
        <button class="btn red" type="submit" name="decision" value="rejected">Reject</button>
      </div>
    </form>

    @if($reviews->count())
      <div style="margin-top:18px">
        <div style="font-size:10px;color:#8d8890;font-weight:700;letter-spacing:.8px;margin-bottom:6px">DECISION HISTORY</div>
        @foreach($reviews as $r)
          <div class="factor">
            <div>
              <b>{{ ucfirst($r->decision) }}</b>
              @if($r->note)<small>{{ $r->note }}</small>@endif
            </div>
            <span style="color:#8d8890;font-size:10px">{{ \Carbon\Carbon::parse($r->created_at)->diffForHumans() }}</span>
          </div>
        @endforeach
      </div>
    @endif
  </article>
</section>

<section class="detail-grid">

  {{-- Items + shipment --}}
  <article class="card">
    <div class="card-head"><div><h3>Items</h3><p>{{ $order->payment_method === 'cod' ? 'Cash on delivery' : ucfirst($order->payment_method) }}</p></div></div>
    @foreach($order->items as $item)
      <div class="factor">
        <div class="prod">
          @if($item->product?->image_url)
            <img src="{{ asset('images/products/'.$item->product->image_url) }}" alt="" onerror="this.remove()">
          @endif
          <span>{{ $item->name }} × {{ $item->quantity }}</span>
        </div>
        <b>₨ {{ number_format($item->total_price) }}</b>
      </div>
    @endforeach
    <div class="factor"><b>Total</b><b>₨ {{ number_format($order->total_amount) }}</b></div>

    @if($order->shipments->count())
      <div style="margin-top:14px;font-size:10px;color:#8d8890;font-weight:700;letter-spacing:.8px">SHIPMENTS</div>
      @foreach($order->shipments as $s)
        <div class="factor">
          <div>
            <b>{{ $s->courier->name ?? 'Courier' }} · {{ $s->tracking_number }}</b>
            <small>{{ ucfirst(str_replace('_', ' ', $s->status)) }}@if($s->failure_reason !== 'none') · {{ ucfirst(str_replace('_', ' ', $s->failure_reason)) }}@endif</small>
          </div>
        </div>
      @endforeach
    @endif
  </article>

  {{-- Customer --}}
  <article class="card">
    <div class="card-head"><div><h3>Customer</h3><p>Contact and history</p></div></div>
    <dl class="kv">
      <dt>Name</dt><dd>{{ $order->customer_name }}</dd>
      <dt>Phone</dt><dd>{{ $order->customer_phone ?: '—' }}</dd>
      <dt>Address</dt><dd>{{ $order->shipping_address_line1 ?: 'Missing' }}</dd>
      <dt>City</dt><dd>{{ $order->shipping_city ?: '—' }}</dd>
      <dt>Address complete</dt><dd>{{ $order->is_address_complete ? 'Yes' : 'No' }}</dd>
      <dt>Verification</dt><dd>{{ ucfirst(str_replace('_', ' ', $order->verification_status)) }}</dd>
      @if($c)
        <dt>Total orders</dt><dd>{{ $c->total_orders }}</dd>
        <dt>Delivered</dt><dd>{{ $c->delivered_orders }}</dd>
        <dt>Returned (RTO)</dt><dd>{{ $c->rto_orders }}</dd>
        <dt>Failed deliveries</dt><dd>{{ $c->failed_deliveries }}</dd>
        <dt>Unconfirmed</dt><dd>{{ $c->unconfirmed_orders }}</dd>
        <dt>Blacklisted</dt><dd>{{ $c->is_blacklisted ? 'Yes — '.$c->blacklist_reason : 'No' }}</dd>
      @endif
    </dl>
  </article>
</section>

@endsection