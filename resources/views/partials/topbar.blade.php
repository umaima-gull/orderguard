@php
  $u = auth()->user();
  $unread = \App\Models\Notification::mine()->whereNull('read_at')->count();
  $latest = \App\Models\Notification::mine()->orderByDesc('id')->take(6)->get();
@endphp
<header class="topbar">
  <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()" aria-label="Open navigation">
    <svg class="ic" aria-hidden="true"><use href="#i-menu"/></svg>
  </button>
  <div class="crumb">Workspace&nbsp; / &nbsp;<b>@yield('crumb', 'Dashboard')</b></div>
  <div class="top-actions">
    <label class="searchbox">
      <svg class="ic" aria-hidden="true"><use href="#i-search"/></svg>
      <input id="search" placeholder="Search order, customer, tracking ID...">
    </label>

    <div class="notif-wrap">
      <button class="icon-btn" type="button" onclick="toggleNotif(event)" aria-label="Notifications">
        <svg class="ic" aria-hidden="true"><use href="#i-bell"/></svg>
        @if($unread)<span class="notif-count">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
      </button>
      <div class="notif-menu" id="notifMenu">
        <div class="notif-head">
          <b>Notifications</b>
          @if($unread)
            <form method="POST" action="{{ route('notifications.readall') }}">@csrf<button type="submit">Mark all as read</button></form>
          @endif
        </div>
        @forelse($latest as $n)
          <form method="POST" action="{{ route('notifications.open', $n->id) }}">
            @csrf
            <button type="submit" class="notif-item {{ $n->read_at ? '' : 'unread' }}">
              <b>{{ $n->subject }}</b>
              <span>{{ \Illuminate\Support\Str::limit($n->body, 90) }}</span>
              <small>{{ $n->created_at?->diffForHumans() }}</small>
            </button>
          </form>
        @empty
          <div class="notif-empty">You are all caught up!</div>
        @endforelse
        <a class="notif-all" href="{{ route('notifications') }}">View all notifications</a>
      </div>
    </div>

    <button class="icon-btn" onclick="toast('No new messages.')" aria-label="Messages">
      <svg class="ic" aria-hidden="true"><use href="#i-mail"/></svg>
    </button>
    <div class="top-user">
      <div class="avatar">{{ $u->initials() }}@if($u->avatar_url)<img src="{{ asset('images/'.$u->avatar_url) }}" alt="" onerror="this.remove()">@endif</div>
     <a href="{{ route('profile') }}" style="text-decoration:none;color:inherit"><strong>{{ \Illuminate\Support\Str::limit($u->name, 18) }}</strong><small>{{ $u->role === 'merchant_owner' ? 'Store owner' : 'Team member' }}</small></a>
    </div>
  </div>
</header>