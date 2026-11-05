<div class="table-wrap">
  <table>
    <thead><tr><th>TRACKING</th><th>ORDER / CUSTOMER</th><th>COURIER</th><th>STATUS</th><th>ISSUE</th><th>ATTEMPTS</th><th>COD</th><th>ACTION</th></tr></thead>
    <tbody>
    @forelse($shipments as $s)
      @php
        $badge = match($s->status) {
          'delivered' => 'low',
          'failed_attempt', 'returned', 'returning', 'cancelled' => 'high',
          default => 'medium',
        };
      @endphp
      <tr>
        <td><b class="order-id">{{ $s->tracking_number }}</b></td>
        <td><b>{{ $s->order->order_number ?? '—' }}</b><br><span style="color:#87958d">{{ $s->order->customer_name ?? '' }}</span></td>
        <td>{{ $s->courier->name ?? '—' }}</td>
        <td><span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $s->status)) }}</span></td>
        <td>{{ $s->failure_reason === 'none' ? '—' : ucfirst(str_replace('_', ' ', $s->failure_reason)) }}</td>
        <td>{{ $s->attempt_count }}</td>
        <td>₨ {{ number_format($s->cod_amount) }}</td>
        <td><a class="select" href="{{ route('shipments.show', $s) }}" style="text-decoration:none">Open</a></td>
      </tr>
    @empty
      <tr><td colspan="8" style="text-align:center;color:#8d8890;padding:24px">No shipments found.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="pager">
  @if($shipments->previousPageUrl())<a class="btn" href="{{ $shipments->previousPageUrl() }}">← Previous</a>@else<span></span>@endif
  @if($shipments->nextPageUrl())<a class="btn" href="{{ $shipments->nextPageUrl() }}">Next →</a>@endif
</div>