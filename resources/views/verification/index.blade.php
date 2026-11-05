@extends('layouts.app')

@section('title', 'COD Verification')
@section('crumb', 'COD Verification')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>COD <em>Verification</em></h1>
    <p>Confirm customer intent before you dispatch cash-on-delivery orders.</p>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Awaiting reply</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ffc15a,#f08a0a)"><svg class="ic" aria-hidden="true"><use href="#i-mail"/></svg></span></div>
    <div class="kpi-value">{{ $counts['pending'] }}</div>
    <div class="kpi-foot">Request sent or required</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">No response</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#c56cf0,#8e2fd0)"><svg class="ic" aria-hidden="true"><use href="#i-phone"/></svg></span></div>
    <div class="kpi-value">{{ $counts['no_response'] }}</div>
    <div class="kpi-foot">Customer did not answer</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Confirmed</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#5bd96f,#1fa24a)"><svg class="ic" aria-hidden="true"><use href="#i-shield-check"/></svg></span></div>
    <div class="kpi-value">{{ $counts['confirmed'] }}</div>
    <div class="kpi-foot">Safe to dispatch</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Rejected</span><span class="kpi-symbol" style="background:linear-gradient(135deg,#ff6b7f,#e8112d)"><svg class="ic" aria-hidden="true"><use href="#i-shield-alert"/></svg></span></div>
    <div class="kpi-value">{{ $counts['rejected'] }}</div>
    <div class="kpi-foot">Customer denied the order</div>
  </article>
</section>

<article class="card" style="margin-bottom:14px">
  <div class="card-head"><div><h3>Needs confirmation</h3><p>Highest risk first. Send a request through the channel you prefer.</p></div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ORDER / CUSTOMER</th><th>PHONE</th><th>RISK</th><th>VERIFICATION</th><th>TRIES</th><th>VALUE</th><th>SEND REQUEST</th></tr></thead>
      <tbody>
      @forelse($needs as $o)
        @php
          $s = $o->risk_score;
          $lvl = $s === null ? '' : ($s <= 30 ? 'low' : ($s <= 60 ? 'medium' : 'high'));
        @endphp
        <tr>
          <td><b class="order-id">{{ $o->order_number }}</b><br><span style="color:#87958d">{{ $o->customer_name }}</span></td>
          <td>{{ $o->customer_phone ?: '—' }}</td>
          <td>
            @if($lvl)<span class="badge {{ $lvl }}">{{ $s }}/100</span>@else<span class="badge" style="background:#f1eef0;color:#8d8890">Not scored</span>@endif
          </td>
          <td>{{ ucfirst(str_replace('_', ' ', $o->verification_status)) }}</td>
          <td>{{ $attempts[$o->id] ?? 0 }}</td>
          <td>₨ {{ number_format($o->total_amount) }}</td>
          <td>
            <form method="POST" action="{{ route('verification.send', $o) }}" class="inline-form">
              @csrf
              <select class="select" name="channel">
                <option value="whatsapp">WhatsApp</option>
                <option value="sms">SMS (OTP)</option>
                <option value="call">Manual call</option>
              </select>
              <button class="select" type="submit">Send</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;color:#8d8890;padding:24px">All orders are verified.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</article>

<article class="card">
  <div class="card-head"><div><h3>Verification log</h3><p>Latest requests. Use "Simulate reply" until WhatsApp/SMS is connected.</p></div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ORDER</th><th>CHANNEL</th><th>METHOD</th><th>STATUS</th><th>SENT</th><th>EXPIRES</th><th>SIMULATE REPLY</th></tr></thead>
      <tbody>
      @forelse($logs as $l)
        @php
          $badge = ['confirmed' => 'low', 'rejected' => 'high', 'expired' => 'medium', 'failed' => 'high'][$l->status] ?? 'medium';
        @endphp
        <tr>
          <td><b class="order-id">{{ $l->order->order_number ?? '—' }}</b></td>
          <td>{{ ucfirst($l->channel) }}</td>
          <td>{{ ucfirst(str_replace('_', ' ', $l->method)) }}</td>
          <td><span class="badge {{ $badge }}">{{ ucfirst($l->status) }}</span></td>
          <td>{{ $l->sent_at?->diffForHumans() ?? '—' }}</td>
          <td>{{ $l->expires_at?->format('M j, g:i A') ?? '—' }}</td>
          <td>
            @if(in_array($l->status, ['pending', 'sent', 'delivered']))
              <form method="POST" action="{{ route('verification.respond', $l) }}" class="inline-form">
                @csrf
                <button class="select ok" name="result" value="confirmed" type="submit">Confirmed</button>
                <button class="select no" name="result" value="rejected" type="submit">Rejected</button>
                <button class="select" name="result" value="no_response" type="submit">No reply</button>
              </form>
            @else
              <span style="color:#8d8890">{{ $l->response_text ?? 'Closed' }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;color:#8d8890;padding:24px">No verification requests yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</article>

@endsection