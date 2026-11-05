@extends('layouts.guest')

@section('title', 'Delivery update')

@section('content')
<article class="card">

@if($state === 'open')
  @php
    $reasons = [
      'customer_unavailable' => 'Our courier could not find anyone at your address.',
      'phone_unreachable'    => 'Our courier could not reach you by phone.',
      'address_issue'        => 'The delivery address looked incomplete or incorrect.',
      'customer_postponed'   => 'You asked to receive the parcel later.',
      'customer_refused'     => 'The parcel was not accepted at the door.',
      'area_not_serviceable' => 'The courier could not reach your area.',
    ];
  @endphp

  <h1 class="auth-title">Let us deliver <em>your parcel</em></h1>
  <p class="auth-sub">
    Hi {{ strtok($order->customer_name, ' ') }}, {{ $reasons[$case->exception_type] ?? 'We could not deliver your parcel.' }}
    Tell us how to fix it and we will try again.
  </p>

  <div class="verify-box">
    <b>{{ $order->order_number }} · {{ $order->store->name }}</b>
    @foreach($order->items as $i)
      <div class="factor"><span>{{ $i->name }} × {{ $i->quantity }}</span><b>{{ $order->currency }} {{ number_format($i->total_price) }}</b></div>
    @endforeach
    <div class="factor"><span>Pay on delivery</span><b>{{ $order->currency }} {{ number_format($order->cod_amount) }}</b></div>
    @if($order->shipping_city)<div class="factor"><span>Delivery city</span><b>{{ $order->shipping_city }}</b></div>@endif
  </div>

  @if($errors->any())
    <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('recover.submit', $token) }}">
    @csrf
    <div class="field">
      <label for="corrected_address">Correct delivery address</label>
      <input id="corrected_address" type="text" name="corrected_address" value="{{ old('corrected_address') }}" maxlength="300" placeholder="House, street, area, city">
    </div>
    <div class="field">
      <label for="corrected_phone">Phone number to reach you (optional)</label>
      <input id="corrected_phone" type="text" name="corrected_phone" value="{{ old('corrected_phone') }}" maxlength="20" placeholder="0300 1234567">
    </div>
    <div class="field">
      <label for="reschedule_date">Preferred delivery date</label>
      <input id="reschedule_date" type="date" name="reschedule_date"
             value="{{ old('reschedule_date', now()->addDay()->toDateString()) }}"
             min="{{ now()->toDateString() }}" max="{{ now()->addDays(14)->toDateString() }}">
    </div>
    <button class="btn primary" style="width:100%;justify-content:center" type="submit">Send to {{ $order->store->name }}</button>
  </form>
  <p class="auth-foot">This link works once and expires {{ $case->recovery_token_expires_at->diffForHumans() }}.</p>

@elseif($state === 'done')
  <h1 class="auth-title">Thank <em>you</em></h1>
  <p class="auth-sub">We have sent your details to {{ $order->store->name }}. They will arrange another delivery attempt for order {{ $order->order_number }}.</p>

@elseif($state === 'closed')
  <h1 class="auth-title">All <em>done</em></h1>
  <p class="auth-sub">This delivery case is already closed. Please contact the store if you need help.</p>

@elseif($state === 'expired')
  <h1 class="auth-title">Link <em>expired</em></h1>
  <p class="auth-sub">This link is no longer valid. Please contact the store to arrange your delivery.</p>

@else
  <h1 class="auth-title">Link not <em>found</em></h1>
  <p class="auth-sub">This link is invalid or was already used.</p>
@endif

</article>
@endsection