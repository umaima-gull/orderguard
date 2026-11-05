@php
  $u = auth()->user();
  $cs = \App\Support\CurrentStore::get();

  $open = ['pending', 'under_review', 'verification_sent'];
  $badge = [
    'orders' => \App\Models\Order::whereIn('status', $open)->count(),
    'risk'   => \App\Models\Order::whereIn('risk_level', ['medium', 'high'])
                  ->whereIn('status', array_merge($open, ['on_hold']))->count(),
    'rto'    => \App\Models\RecoveryCase::whereNotIn('status', ['delivered', 'returned', 'closed_unresolved'])->count(),
  ];

  $sub = \Illuminate\Support\Facades\DB::table('subscriptions')
    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
    ->where('subscriptions.user_id', $cs->owner_id)->orderByDesc('subscriptions.id')
    ->select('subscriptions.orders_used_in_period as used', 'plans.monthly_order_limit as lim', 'plans.name')->first();
  $pct = $sub && $sub->lim ? min(100, round($sub->used / $sub->lim * 100)) : 0;
  $isOwner = \App\Support\Permissions::can('admin');
  $menu = [
    
    'WORKSPACE' => [
      ['route'=>'dashboard',          'label'=>'Dashboard',         'icon'=>'i-dash'],
      ['route'=>'orders',             'label'=>'Orders',            'icon'=>'i-pkg', 'count'=>$badge['orders']],
      ['route'=>'risk-intelligence',  'label'=>'Risk intelligence', 'icon'=>'i-shield-alert', 'count'=>$badge['risk']],
      ['route'=>'cod-verification',   'label'=>'COD verification',  'icon'=>'i-shield-check'],
    ],
    'FULFILMENT' => [
      ['route'=>'shipments',          'label'=>'Shipments',         'icon'=>'i-truck'],
      ['route'=>'rto-recovery',       'label'=>'RTO recovery',      'icon'=>'i-undo', 'count'=>$badge['rto']],
      ['route'=>'customers',          'label'=>'Customers',         'icon'=>'i-users'],
      ['route'=>'courier-partners',   'label'=>'Courier partners',  'icon'=>'i-route'],
    ],
    'INSIGHTS & CONTROL' => [
      ['route'=>'analytics',          'label'=>'Analytics',         'icon'=>'i-chart'],
      ['route'=>'integrations',       'label'=>'Integrations',      'icon'=>'i-plug'],
      ['route'=>'settings',           'label'=>'Settings',          'icon'=>'i-gear'],
            ['route'=>'team',               'label'=>'Team',              'icon'=>'i-users'],
    ],
  ];
@endphp

<aside class="sidebar" id="sidebar">
  <button class="collapse-btn" id="collapseBtn" aria-label="Toggle sidebar" aria-expanded="true" title="Collapse sidebar">
    <svg class="ic" aria-hidden="true"><use href="#i-chev-left"/></svg>
  </button>

  <div class="brand">
    <div class="brand-mark">
      <img src="{{ asset('images/logo1.png') }}" alt="OrderGuard logo" onerror="this.remove()">
    </div>
    <div class="brand-name">order<span>guard</span></div>
  </div>

  <div class="side-scroll">
    <div class="workspace">
      <i class="store-dot"></i>
      <div><small>WORKSPACE</small><strong>{{ \Illuminate\Support\Str::limit($cs->name, 22) }}</strong></div>
      <svg class="ic" aria-hidden="true"><use href="#i-chev-down"/></svg>
    </div>

    @foreach($menu as $caption => $items)
      <div class="nav-caption">{{ $caption }}</div>
      <nav class="nav">
        @foreach($items as $item)
        @continue(in_array($item['route'], ['team', 'integrations', 'settings']) && !$isOwner)
          <a href="{{ route($item['route']) }}"
             class="{{ request()->routeIs($item['route'], $item['route'].'.*') ? 'active' : '' }}"
             title="{{ $item['label'] }}">
            <svg class="ic" aria-hidden="true"><use href="#{{ $item['icon'] }}"/></svg>
            <span class="label">{{ $item['label'] }}</span>
            @if(!empty($item['count']))<span class="nav-count">{{ $item['count'] }}</span>@endif
          </a>
        @endforeach
      </nav>
    @endforeach

    @if($sub)
    <div class="plan-card">
      <div class="plan-top">Your monthly usage <span class="plan-pill">{{ strtoupper($sub->name) }}</span></div>
      <p>{{ number_format($sub->used) }} of {{ $sub->lim ? number_format($sub->lim) : 'unlimited' }} orders analysed this month.</p>
      <div class="plan-line"><i style="width:{{ $pct }}%"></i></div>
    </div>
    @endif
  </div>

  <div class="user-side">
    <div class="avatar">{{ $u->initials() }}@if($u->avatar_url)<img src="{{ asset('images/'.$u->avatar_url) }}" alt="" onerror="this.remove()">@endif</div>
    <a href="{{ route('profile') }}" style="text-decoration:none;color:inherit"><strong>{{ \Illuminate\Support\Str::limit($u->name, 18) }}</strong><small>{{ $u->role === 'merchant_owner' ? 'Store owner' : 'Team member' }}</small></a>
    <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
      @csrf
      <button class="logout-btn" type="submit">Log out</button>
    </form>
  </div>
</aside>