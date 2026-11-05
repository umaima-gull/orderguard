@extends('layouts.app')

@section('title', $shipment->tracking_number)
@section('crumb', 'Shipments')

@section('content')

@php
  $badge = match($shipment->status) {
    'delivered' => 'low',
    'failed_attempt', 'returned', 'returning', 'cancelled' => 'high',
    default => 'medium',
  };
@endphp

<section class="welcome">
  <div>
    <div class="eyebrow">{{ $shipment->courier->name ?? 'Courier' }}</div>
    <h1>Shipment <em>{{ $shipment->tracking_number }}</em></h1>
    <p>Order {{ $shipment->order->order_number }} · {{ $shipment->order->customer_name }} · {{ $shipment->delivery_city }}</p>
  </div>
  <div class="welcome-actions">
    @if($trackingUrl)<a class="btn" href="{{ $trackingUrl }}" target="_blank" rel="noopener">Courier tracking ↗</a>@endif
    <a class="btn" href="{{ route('shipments') }}">← Back</a>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

@if($errors->any())
  <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
@endif

<section class="detail-grid">

  <article class="card">
    <div class="card-head">
      <div><h3>Tracking timeline</h3><p>Latest event first</p></div>
      <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $shipment->status)) }}</span>
    </div>
    <div class="timeline">
      @forelse($events as $e)
        <div class="tl-item {{ $e->is_exception ? 'bad' : '' }}">
          <b>{{ ucfirst(str_replace('_', ' ', $e->status)) }}</b>
          <small>{{ $e->description }}</small>
          <small>{{ $e->event_time->format('M j, g:i A') }}@if($e->location) · {{ $e->location }}@endif</small>
        </div>
      @empty
        <div class="insight">No tracking events yet.</div>
      @endforelse
    </div>
  </article>

  <div>
    {{-- Recovery case --}}
    @if($shipment->is_exception || $case)
      <article class="card" style="margin-bottom:14px">
        <div class="card-head"><div><h3>Delivery recovery</h3><p>Failed delivery needs follow-up</p></div></div>
        @if($case)
          <dl class="kv">
            <dt>Case</dt><dd>{{ $case->case_number }}</dd>
            <dt>Status</dt><dd>{{ ucfirst(str_replace('_', ' ', $case->status)) }}</dd>
            <dt>Issue</dt><dd>{{ ucfirst(str_replace('_', ' ', $case->exception_type)) }}</dd>
            <dt>Amount at risk</dt><dd>₨ {{ number_format($case->amount_at_risk) }}</dd>
            <dt>Outcome</dt><dd>{{ ucfirst($case->outcome) }}</dd>
          </dl>
        @else
          <p style="font-size:11px;color:#6d666b;margin:0 0 12px">
            Reason: <b>{{ ucfirst(str_replace('_', ' ', $shipment->failure_reason)) }}</b>. Open a recovery case to contact the customer.
          </p>
          <form method="POST" action="{{ route('shipments.case', $shipment) }}">
            @csrf
            <button class="btn primary" type="submit">Open recovery case</button>
          </form>
        @endif
      </article>
    @endif

    {{-- Simulate courier update --}}
    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Update status</h3><p>Simulates a courier update until the courier API is connected</p></div></div>
      <form method="POST" action="{{ route('shipments.status', $shipment) }}" class="decision-form">
        @csrf
        <select class="select" name="status" style="width:100%;margin-bottom:8px">
          @foreach($statuses as $s)
            <option value="{{ $s }}" @selected($shipment->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
          @endforeach
        </select>
        <select class="select" name="failure_reason" style="width:100%;margin-bottom:8px">
          <option value="">Failure reason (only for failed attempt)</option>
          @foreach($reasons as $r)
            <option value="{{ $r }}">{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
          @endforeach
        </select>
        <textarea name="description" rows="2" maxlength="300" placeholder="Optional note"></textarea>
        <button class="btn primary" type="submit">Save update</button>
      </form>
    </article>

    {{-- Details --}}
    <article class="card">
      <div class="card-head"><div><h3>Details</h3></div></div>
      <dl class="kv">
        <dt>Courier</dt><dd>{{ $shipment->courier->name ?? '—' }}</dd>
        <dt>COD amount</dt><dd>₨ {{ number_format($shipment->cod_amount) }}</dd>
        <dt>Collected</dt><dd>₨ {{ number_format($shipment->cod_collected) }}</dd>
        <dt>Attempts</dt><dd>{{ $shipment->attempt_count }}</dd>
        <dt>Dispatched</dt><dd>{{ $shipment->dispatched_at?->format('M j, g:i A') ?? '—' }}</dd>
        <dt>Delivered</dt><dd>{{ $shipment->delivered_at?->format('M j, g:i A') ?? '—' }}</dd>
        <dt>Order</dt><dd><a href="{{ route('orders.show', $shipment->order) }}" style="color:#e8112d">{{ $shipment->order->order_number }}</a></dd>
      </dl>
    </article>
  </div>
</section>

@endsection