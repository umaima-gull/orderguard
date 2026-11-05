@extends('layouts.guest')

@section('title', 'Confirm your order')

@section('content')
<article class="card">

@if($state === 'open')
  <h1 class="auth-title">Confirm your <em>order</em></h1>
  <p class="auth-sub">
    Hi {{ strtok($order->customer_name, ' ') }}, {{ $order->store->name }} wants to be sure this order is yours before dispatching it.
  </p>

  <div class="verify-box">
    <b>{{ $order->order_number }}</b>
    @foreach($order->items as $i)
      <div class="factor"><span>{{ $i->name }} × {{ $i->quantity }}</span><b>{{ $order->currency }} {{ number_format($i->total_price) }}</b></div>
    @endforeach
    <div class="factor"><span>Pay on delivery</span><b>{{ $order->currency }} {{ number_format($order->cod_amount) }}</b></div>
    @if($order->shipping_city)<div class="factor"><span>Delivery city</span><b>{{ $order->shipping_city }}</b></div>@endif
  </div>

  <form method="POST" action="{{ route('verify.respond', $token) }}">
    @csrf
    <div class="btn-row">
      <button class="btn green" style="flex:1;justify-content:center" type="submit" name="answer" value="confirmed">Yes, confirm my order</button>
      <button class="btn red" style="flex:1;justify-content:center" type="submit" name="answer" value="rejected" onclick="return confirm('Cancel this order?')">No, this is not mine</button>
    </div>
  </form>
  <p class="auth-foot">This link works once and expires {{ $log->expires_at?->diffForHumans() }}.</p>

@elseif($state === 'confirmed')
  <h1 class="auth-title">Thank <em>you</em></h1>
  <p class="auth-sub">Your order {{ $order->order_number }} is confirmed. {{ $order->store->name }} will dispatch it soon.</p>

@elseif($state === 'rejected')
  <h1 class="auth-title">Order <em>cancelled</em></h1>
  <p class="auth-sub">We have told {{ $order->store->name }} that order {{ $order->order_number }} is not yours. Nothing will be sent.</p>

@elseif($state === 'expired')
  <h1 class="auth-title">Link <em>expired</em></h1>
  <p class="auth-sub">This confirmation link is no longer valid. Please contact the store to confirm your order.</p>

@else
  <h1 class="auth-title">Link not <em>found</em></h1>
  <p class="auth-sub">This link is invalid or was already used.</p>
@endif

</article>
@endsection