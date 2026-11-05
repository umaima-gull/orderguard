<div class="table-wrap">
  <table id="ordersTable">
    <thead>
      <tr><th>ORDER / CUSTOMER</th><th>PRODUCT</th><th>RISK SCORE</th><th>RISK LEVEL</th><th>STATUS</th><th>VALUE</th><th>ACTION</th></tr>
    </thead>
    <tbody>
    @forelse($orders as $o)
    @php
  $score = $o->risk_score;
  [$lvl, $col, $txt] = match($o->risk_level) {
    'low'    => ['low', 'green', 'Low risk'],
    'medium' => ['medium', 'yellow', 'Medium'],
    'high'   => ['high', 'red', 'High risk'],
    default  => ['', '', 'Not scored'],
  };
  $first = $o->items->first();
@endphp
      <tr>
        <td>
          <b class="order-id">{{ $o->order_number }}</b><br>
          <span style="color:#87958d">{{ $o->customer_name }}</span>
        </td>
        <td>
          <div class="prod">
            @if($first?->product?->image_url)
              <img src="{{ asset('images/products/'.$first->product->image_url) }}" alt="" onerror="this.remove()">
            @endif
            <span>{{ $first->name ?? '-' }}</span>
          </div>
        </td>
        <td class="score {{ $col }}">{{ $score === null ? '—' : $score.'/100' }}</td>
        <td><span class="badge {{ $lvl }}" @if($lvl === '') style="background:#f1eef0;color:#8d8890" @endif>{{ $txt }}</span></td>
        <td>{{ ucfirst(str_replace('_', ' ', $o->status)) }}</td>
        <td>₨ {{ number_format($o->total_amount) }}</td>
        <td><a class="select" href="{{ route('orders.show', $o) }}" style="text-decoration:none">Review</a></td>
      </tr>
    @empty
      <tr><td colspan="7" style="text-align:center;color:#8d8890;padding:24px">No orders found.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="pager">
  @if($orders->previousPageUrl())<a class="btn" href="{{ $orders->previousPageUrl() }}">← Previous</a>@else<span></span>@endif
  @if($orders->nextPageUrl())<a class="btn" href="{{ $orders->nextPageUrl() }}">Next →</a>@endif
</div>