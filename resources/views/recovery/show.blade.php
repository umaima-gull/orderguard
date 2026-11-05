@extends('layouts.app')

@section('title', $case->case_number)
@section('crumb', 'RTO Recovery')

@section('content')

@php
  $closed = in_array($case->status, ['delivered', 'returned', 'closed_unresolved']);
  $badge = match($case->status) {
    'delivered' => 'low',
    'returned', 'closed_unresolved' => 'high',
    default => 'medium',
  };
  $order = $case->order;
  $icons = [
    'message_sent' => 'i-mail', 'call_made' => 'i-phone', 'address_updated' => 'i-pin',
    'reschedule_requested' => 'i-refresh', 'courier_notified' => 'i-truck',
    'retry_booked' => 'i-truck', 'note' => 'i-dots',
  ];
@endphp

<section class="welcome">
  <div>
    <div class="eyebrow">{{ ucfirst(str_replace('_', ' ', $case->exception_type)) }}</div>
    <h1>Case <em>{{ $case->case_number }}</em></h1>
    <p>{{ $order->order_number }} · {{ $order->customer_name }} · ₨ {{ number_format($case->amount_at_risk) }} at risk</p>
  </div>
  <div class="welcome-actions">
    <a class="btn" href="{{ route('shipments.show', $case->shipment) }}">Shipment</a>
    <a class="btn" href="{{ route('rto-recovery') }}">← Back</a>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif
@if($errors->any())
  <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
@endif

<section class="detail-grid">

  {{-- LEFT: actions + timeline --}}
  <div>
    @unless($closed)
    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Contact customer</h3><p>Messages are simulated until WhatsApp/SMS is connected</p></div></div>
      <form method="POST" action="{{ route('rto-recovery.act', $case) }}" class="inline-form" style="flex-wrap:wrap">
        @csrf
        <input type="hidden" name="type" value="message">
        <select class="select" name="template">
          <option value="failed_delivery_recovery">Failed delivery: retry or fix address</option>
          <option value="address_correction">Ask for correct address</option>
        </select>
        <select class="select" name="channel">
          <option value="whatsapp">WhatsApp</option>
          <option value="sms">SMS</option>
        </select>
        <button class="select ok" type="submit">Send message</button>
      </form>
    </article>

    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Recovery actions</h3><p>Record what happened with the customer</p></div></div>

      <div class="action-block">
        <b>Log a call</b>
        <form method="POST" action="{{ route('rto-recovery.act', $case) }}" class="inline-form" style="flex-wrap:wrap">
          @csrf
          <input type="hidden" name="type" value="call">
          <select class="select" name="result">
            <option value="success">Customer answered</option>
            <option value="no_answer">No answer</option>
            <option value="failed">Number unreachable</option>
          </select>
          <input class="select" type="text" name="note" maxlength="300" placeholder="Note (optional)">
          <button class="select" type="submit">Save call</button>
        </form>
      </div>

      <div class="action-block">
        <b>Correct address</b>
        <form method="POST" action="{{ route('rto-recovery.act', $case) }}" class="inline-form" style="flex-wrap:wrap">
          @csrf
          <input type="hidden" name="type" value="address">
          <input class="select" style="min-width:240px" type="text" name="corrected_address" maxlength="500" placeholder="Corrected delivery address" required>
          <input class="select" type="text" name="corrected_phone" maxlength="30" placeholder="New phone (optional)">
          <button class="select" type="submit">Update address</button>
        </form>
      </div>

      <div class="action-block">
        <b>Reschedule delivery</b>
        <form method="POST" action="{{ route('rto-recovery.act', $case) }}" class="inline-form">
          @csrf
          <input type="hidden" name="type" value="reschedule">
          <input class="select" type="date" name="reschedule_date" min="{{ now()->toDateString() }}" value="{{ now()->addDay()->toDateString() }}" required>
          <button class="select" type="submit">Request reschedule</button>
        </form>
      </div>

      <div class="action-block">
        <b>Book re-delivery attempt</b>
        <form method="POST" action="{{ route('rto-recovery.act', $case) }}" class="inline-form">
          @csrf
          <input type="hidden" name="type" value="retry">
          <button class="select ok" type="submit">Book retry</button>
          <span style="font-size:10px;color:#8d8890">Shipment becomes active again.</span>
        </form>
      </div>

      <div class="action-block">
        <b>Add note</b>
        <form method="POST" action="{{ route('rto-recovery.act', $case) }}" class="inline-form">
          @csrf
          <input type="hidden" name="type" value="note">
          <input class="select" style="min-width:260px" type="text" name="note" maxlength="500" placeholder="Write a note" required>
          <button class="select" type="submit">Add</button>
        </form>
      </div>

      <div class="action-block" style="border-bottom:0">
        <form method="POST" action="{{ route('rto-recovery.act', $case) }}" onsubmit="return confirm('Close this case as unresolved?')">
          @csrf
          <input type="hidden" name="type" value="close">
          <button class="select no" type="submit">Close as unresolved</button>
        </form>
      </div>
    </article>
    @endunless

    <article class="card">
      <div class="card-head"><div><h3>Activity</h3><p>Everything done on this case</p></div></div>
      <div class="timeline">
        @forelse($case->actions as $a)
          @php
            $d = $a->details ?? [];
            $text = $d['note'] ?? ($d['body'] ?? ($d['corrected_address'] ?? ($d['date'] ?? '')));
          @endphp
          <div class="tl-item {{ in_array($a->result, ['failed', 'no_answer']) ? 'bad' : '' }}">
           <b>{{ ucfirst(str_replace('_', ' ', $a->action_type)) }} · {{ ($d['by'] ?? '') === 'customer' ? 'By customer' : ucfirst($a->channel) }}</b>
            @if($text)<small>{{ \Illuminate\Support\Str::limit($text, 220) }}</small>@endif
            <small>{{ ucfirst(str_replace('_', ' ', $a->result)) }} · {{ $a->created_at?->diffForHumans() }}</small>
          </div>
        @empty
          <div class="insight">No actions yet. Start by contacting the customer.</div>
        @endforelse
      </div>
    </article>
  </div>

  {{-- RIGHT: details --}}
  <div>
    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Case details</h3></div><span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span></div>
      <dl class="kv">
        <dt>Issue</dt><dd>{{ ucfirst(str_replace('_', ' ', $case->exception_type)) }}</dd>
        <dt>Priority</dt><dd>{{ ucfirst($case->priority) }}</dd>
        <dt>Outcome</dt><dd>{{ ucfirst($case->outcome) }}</dd>
        <dt>Amount at risk</dt><dd>₨ {{ number_format($case->amount_at_risk) }}</dd>
        <dt>Recovered</dt><dd>₨ {{ number_format($case->revenue_recovered) }}</dd>
        <dt>Opened</dt><dd>{{ $case->opened_at->format('M j, g:i A') }}</dd>
        <dt>First contact</dt><dd>{{ $case->first_contact_at?->format('M j, g:i A') ?? 'Not yet' }}</dd>
        <dt>Due</dt><dd>{{ $case->due_at?->format('M j, g:i A') ?? '—' }}</dd>
        <dt>Reschedule date</dt><dd>{{ $case->reschedule_date?->format('M j, Y') ?? '—' }}</dd>
        <dt>Corrected address</dt><dd>{{ $case->corrected_address ?: '—' }}</dd>
                <dt>Customer reply</dt><dd>{{ $case->customer_response ?: '—' }}</dd>
      </dl>
    </article>

    <article class="card">
      <div class="card-head"><div><h3>Customer</h3></div></div>
      <dl class="kv">
        <dt>Name</dt><dd>{{ $order->customer_name }}</dd>
        <dt>Phone</dt><dd>{{ $order->customer_phone ?: '—' }}</dd>
        <dt>Address</dt><dd>{{ $order->shipping_address_line1 ?: 'Missing' }}</dd>
        <dt>City</dt><dd>{{ $order->shipping_city ?: '—' }}</dd>
        <dt>Courier</dt><dd>{{ $case->shipment->courier->name ?? '—' }}</dd>
        <dt>Tracking</dt><dd>{{ $case->shipment->tracking_number }}</dd>
        <dt>Order</dt><dd><a href="{{ route('orders.show', $order) }}" style="color:#e8112d">{{ $order->order_number }}</a></dd>
      </dl>
    </article>
  </div>
</section>

@endsection