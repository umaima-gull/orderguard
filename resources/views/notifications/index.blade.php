@extends('layouts.app')

@section('title', 'Notifications')
@section('crumb', 'Notifications')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1><em>Notifications</em></h1>
    <p>Customer replies, risky orders and delivery problems.</p>
  </div>
  <div class="welcome-actions">
    <form method="POST" action="{{ route('notifications.readall') }}">@csrf
      <button class="btn" type="submit">Mark all as read</button>
    </form>
  </div>
</section>

<article class="card">
  @forelse($notifications as $n)
    <form method="POST" action="{{ route('notifications.open', $n->id) }}">
      @csrf
      <button type="submit" class="notif-item {{ $n->read_at ? '' : 'unread' }}" style="width:100%">
        <b>{{ $n->subject }}</b>
        <span>{{ $n->body }}</span>
        <small>{{ $n->created_at?->format('M j, g:i A') }} · {{ $n->created_at?->diffForHumans() }}</small>
      </button>
    </form>
  @empty
    <div class="insight">No notifications yet.</div>
  @endforelse

  <div class="pager">
    @if($notifications->previousPageUrl())<a class="btn" href="{{ $notifications->previousPageUrl() }}">← Previous</a>@else<span></span>@endif
    @if($notifications->nextPageUrl())<a class="btn" href="{{ $notifications->nextPageUrl() }}">Next →</a>@endif
  </div>
</article>

@endsection