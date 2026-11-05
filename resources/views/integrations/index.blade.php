@extends('layouts.app')

@section('title', 'Integrations')
@section('crumb', 'Integrations')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1><em>Integrations</em></h1>
    <p>Connect your store so new orders arrive and get scored automatically.</p>
  </div>
  <div class="welcome-actions">
    <form method="POST" action="{{ route('integrations.sync') }}">@csrf
      <button class="btn" type="submit"><svg class="ic sm" aria-hidden="true"><use href="#i-refresh"/></svg>Sync now</button>
    </form>
  </div>
</section>

@if(session('status'))
  <div class="flash">{{ session('status') }}</div>
@endif
@if($errors->any())
  <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
@endif

<section class="kpis">
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Connection</span></div>
    <div class="kpi-value" style="font-size:22px">{{ ucfirst($store->status) }}</div>
    <div class="kpi-foot">{{ ucfirst($store->platform) }} · {{ $store->domain ?: 'no domain' }}</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Last sync</span></div>
    <div class="kpi-value" style="font-size:22px">{{ $store->last_synced_at?->diffForHumans() ?? 'Never' }}</div>
    <div class="kpi-foot">Sync is simulated for now</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Events processed</span></div>
    <div class="kpi-value">{{ $counts['processed'] ?? 0 }}</div>
    <div class="kpi-foot">{{ $counts['ignored'] ?? 0 }} ignored</div>
  </article>
  <article class="kpi">
    <div class="kpi-head"><span class="kpi-label">Events failed</span></div>
    <div class="kpi-value">{{ $counts['failed'] ?? 0 }}</div>
    <div class="kpi-foot">Use Retry in the log</div>
  </article>
</section>

<section class="detail-grid">

  <div>
    <article class="card" style="margin-bottom:14px">
      <div class="card-head"><div><h3>Store connection</h3><p>Credentials are encrypted and never shown again</p></div></div>
      <form method="POST" action="{{ route('integrations.update') }}">
        @csrf
        <dl class="kv" style="margin-bottom:14px;align-items:center">
          <dt>Platform</dt>
          <dd>
            <select class="select" name="platform">
              @foreach(['shopify' => 'Shopify', 'woocommerce' => 'WooCommerce', 'manual' => 'Manual', 'custom_api' => 'Custom API'] as $k => $v)
                <option value="{{ $k }}" @selected(old('platform', $store->platform) === $k)>{{ $v }}</option>
              @endforeach
            </select>
          </dd>
          <dt>Store domain</dt>
          <dd><input class="select" style="min-width:260px" type="text" name="domain" value="{{ old('domain', $store->domain) }}" placeholder="my-store.myshopify.com"></dd>
          <dt>External store ID</dt>
          <dd><input class="select" type="text" name="external_store_id" value="{{ old('external_store_id', $store->external_store_id) }}"></dd>
          <dt>API key</dt>
          <dd><input class="select" style="min-width:260px" type="text" name="api_key" autocomplete="off" value="{{ old('api_key', $store->api_key) }}"></dd>
          <dt>Access token</dt>
          <dd><input class="select" style="min-width:260px" type="password" name="access_token" autocomplete="off" placeholder="{{ $hasToken ? 'Saved (leave blank to keep)' : 'Not set' }}"></dd>
          <dt>API secret</dt>
          <dd><input class="select" style="min-width:260px" type="password" name="api_secret" autocomplete="off" placeholder="{{ $hasSecret ? 'Saved (leave blank to keep)' : 'Not set' }}"></dd>
        </dl>
        <div class="btn-row">
          <button class="btn primary" type="submit">Save connection</button>
        </div>
      </form>
      <form method="POST" action="{{ route('integrations.disconnect') }}" style="margin-top:10px" onsubmit="return confirm('Disconnect this store?')">
        @csrf<button class="select no" type="submit">Disconnect store</button>
      </form>
    </article>

    <article class="card">
      <div class="card-head"><div><h3>Webhooks</h3><p>Add these in your store so new orders are sent to OrderGuard</p></div></div>

      @foreach(['shopify' => 'Shopify: topic "Order creation", format JSON', 'woocommerce' => 'WooCommerce: topic "Order created"'] as $src => $hint)
        <div class="action-block">
          <b>{{ $hint }}</b>
          <input class="select" style="width:100%" type="text" readonly value="{{ $urls[$src] }}" onclick="this.select()">
        </div>
      @endforeach

      <div class="action-block" style="border-bottom:0">
        <b>Webhook secret (signing key)</b>
        <details class="cred">
          <summary>Show secret</summary>
          <input class="select" style="width:100%;margin-top:8px" type="text" readonly value="{{ $store->webhook_secret }}" onclick="this.select()">
        </details>
        <form method="POST" action="{{ route('integrations.secret') }}" onsubmit="return confirm('Old secret will stop working. Continue?')" style="margin-top:8px">
          @csrf<button class="select" type="submit">Regenerate secret</button>
        </form>
      </div>

      <div class="insight">
        Every request is checked with an HMAC signature using this secret. Shopify and WooCommerce cannot reach <b>localhost</b>.
        To test with a real store, expose your app with a tunnel (for example ngrok) and use that public URL.
      </div>
    </article>
  </div>

  <div>
    <article class="card">
      <div class="card-head"><div><h3>Send test order</h3><p>Creates a sample order through the real webhook flow</p></div></div>
      <p style="font-size:11px;color:#6d666b;margin:0 0 12px">
        A new customer, order and items are created, then the order is scored by your fraud rules.
      </p>
      <div class="btn-row">
        <form method="POST" action="{{ route('integrations.test') }}">@csrf
          <input type="hidden" name="source" value="shopify"><button class="btn primary" type="submit">Test Shopify order</button></form>
        <form method="POST" action="{{ route('integrations.test') }}">@csrf
          <input type="hidden" name="source" value="woocommerce"><button class="btn" type="submit">Test WooCommerce order</button></form>
      </div>
    </article>
  </div>
</section>

<article class="card">
  <div class="card-head">
    <div><h3>Webhook log</h3><p>Latest 25 events</p></div>
    <form method="GET" class="inline-form">
      <select class="select" name="status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach(['received','processing','processed','failed','ignored'] as $s)
          <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>RECEIVED</th><th>SOURCE</th><th>EVENT</th><th>EXTERNAL ID</th><th>STATUS</th><th>TRIES</th><th>ERROR</th><th>ACTION</th></tr></thead>
      <tbody>
      @forelse($events as $e)
        @php $badge = ['processed' => 'low', 'failed' => 'high'][$e->status] ?? 'medium'; @endphp
        <tr>
          <td>{{ $e->received_at?->diffForHumans() }}</td>
          <td>{{ ucfirst($e->source) }}</td>
          <td>{{ $e->event_type }}</td>
          <td>{{ \Illuminate\Support\Str::limit($e->external_id, 18) }}</td>
          <td><span class="badge {{ $badge }}">{{ ucfirst($e->status) }}</span></td>
          <td>{{ $e->attempts }}</td>
          <td style="white-space:normal;max-width:240px;color:#b30d23">{{ $e->error_message }}</td>
          <td>
            @if($e->status === 'failed')
              <form method="POST" action="{{ route('integrations.retry', $e) }}">@csrf<button class="select ok" type="submit">Retry</button></form>
            @else — @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8" style="text-align:center;color:#8d8890;padding:24px">No webhook events yet. Try "Send test order".</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</article>

@endsection