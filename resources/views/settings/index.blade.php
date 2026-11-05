@extends('layouts.app')

@section('title', 'Settings')
@section('crumb', 'Settings')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1><em>Settings</em></h1>
    <p>Control how orders are scored and how your store is set up.</p>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif
@if($errors->any())
  <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
@endif

<section class="detail-grid">

  <div>
    {{-- Risk settings --}}
    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Risk settings</h3><p>Decide where low, medium and high risk begin</p></div></div>
      <form method="POST" action="{{ route('settings.risk') }}">
        @csrf
        <dl class="kv" style="margin-bottom:14px;align-items:center">
          <dt>Low risk up to</dt>
          <dd><input class="select num" type="number" name="low_risk_max" value="{{ old('low_risk_max', $store->low_risk_max) }}" min="0" max="98"> <small style="color:#8d8890">score 0 to this value</small></dd>

          <dt>Medium risk up to</dt>
          <dd><input class="select num" type="number" name="medium_risk_max" value="{{ old('medium_risk_max', $store->medium_risk_max) }}" min="1" max="99"> <small style="color:#8d8890">above this value is high risk</small></dd>

          <dt>Auto-hold high risk</dt>
          <dd>
            <select class="select" name="auto_hold_high_risk">
              <option value="0" @selected(!$store->auto_hold_high_risk)>Off: I decide manually</option>
              <option value="1" @selected($store->auto_hold_high_risk)>On: put high-risk orders on hold</option>
            </select>
          </dd>

          <dt>Auto-send verification</dt>
          <dd>
            <select class="select" name="auto_send_verification">
              <option value="0" @selected(!$store->auto_send_verification)>Off</option>
              <option value="1" @selected($store->auto_send_verification)>On (needs WhatsApp/SMS connection)</option>
            </select>
          </dd>
        </dl>

        <label style="font-size:11px;display:flex;align-items:center;gap:6px;margin-bottom:12px">
          <input type="checkbox" name="rescore" value="1" checked style="accent-color:#e8112d">
          Re-score all existing orders after saving
        </label>

        <div class="insight" style="margin-bottom:12px">
          A high score should trigger a review or verification, not an automatic rejection. Auto-hold only pauses an order, you still decide.
        </div>

        <button class="btn primary" type="submit">Save risk settings</button>
      </form>
    </article>

    {{-- Store profile --}}
    <article class="card">
      <div class="card-head"><div><h3>Store profile</h3><p>{{ ucfirst($store->platform) }} · {{ $store->domain ?: 'No domain' }}</p></div></div>
      <form method="POST" action="{{ route('settings.store') }}">
        @csrf
        <dl class="kv" style="margin-bottom:14px;align-items:center">
          <dt>Store name</dt>
          <dd><input class="select" style="min-width:240px" type="text" name="name" value="{{ old('name', $store->name) }}" maxlength="150" required></dd>

          <dt>Country code</dt>
          <dd><input class="select num" type="text" name="country_code" value="{{ old('country_code', $store->country_code) }}" maxlength="2" required> <small style="color:#8d8890">e.g. PK</small></dd>

          <dt>Currency</dt>
          <dd><input class="select num" type="text" name="currency" value="{{ old('currency', $store->currency) }}" maxlength="3" required> <small style="color:#8d8890">e.g. PKR</small></dd>

          <dt>Timezone</dt>
          <dd><input class="select" style="min-width:240px" type="text" name="timezone" value="{{ old('timezone', $store->timezone) }}" required> <small style="color:#8d8890">e.g. Asia/Karachi</small></dd>
        </dl>
        <button class="btn primary" type="submit">Save store profile</button>
      </form>
    </article>
  </div>

  {{-- Plan and usage --}}
  <div>
    <article class="card" style="margin-bottom:14px">
      <div class="card-head">
        <div><h3>Your plan</h3><p>Subscription and monthly usage</p></div>
        @if($sub)<span class="badge low">{{ $sub->plan_name }}</span>@endif
      </div>

      @if($sub)
        <div class="courier-row">
          <div class="courier-meta">
            <b>Orders this month</b>
            <span>{{ number_format($sub->orders_used_in_period) }} of {{ $sub->monthly_order_limit ? number_format($sub->monthly_order_limit) : 'Unlimited' }}</span>
          </div>
          <div class="progress"><i style="width:{{ $usedPct }}%"></i></div>
        </div>

        <dl class="kv" style="margin-top:14px">
          <dt>Status</dt><dd>{{ ucfirst($sub->status) }}</dd>
          <dt>Price</dt><dd>{{ $sub->plan_currency }} {{ number_format($sub->price_monthly, 2) }} / month</dd>
          <dt>Billing period</dt><dd>{{ $sub->current_period_start }} to {{ $sub->current_period_end }}</dd>
          <dt>Courier accounts</dt><dd>{{ $courierCount }} of {{ $sub->max_courier_accounts }}</dd>
          <dt>Team members</dt><dd>{{ $teamCount }} of {{ $sub->max_team_members }}</dd>
          <dt>Stores allowed</dt><dd>{{ $sub->max_stores }}</dd>
          <dt>WhatsApp</dt><dd>{{ $sub->whatsapp_enabled ? 'Included' : 'Not included' }}</dd>
        </dl>
      @else
        <div class="insight">No subscription found for this account.</div>
      @endif
    </article>

    <article class="card">
      <div class="card-head"><div><h3>Current thresholds</h3></div></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <span class="badge low">Low: 0 to {{ $store->low_risk_max }}</span>
        <span class="badge medium">Medium: {{ $store->low_risk_max + 1 }} to {{ $store->medium_risk_max }}</span>
        <span class="badge high">High: {{ $store->medium_risk_max + 1 }} to 100</span>
      </div>
    </article>
  </div>
</section>

@endsection