<div class="table-wrap">
  <table>
    <thead><tr><th>CUSTOMER</th><th>PHONE</th><th>CITY</th><th>ORDERS</th><th>DELIVERED</th><th>RTO</th><th>FAILED</th><th>SUCCESS</th><th>SPENT</th><th>ACTION</th></tr></thead>
    <tbody>
    @forelse($customers as $c)
      @php
        $rate = $c->total_orders ? round($c->delivered_orders / $c->total_orders * 100) : 0;
        $words = array_filter(explode(' ', trim($c->name)));
        $initials = strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
      @endphp
      <tr>
        <td>
          <div class="customer">
            <span class="mini-avatar">{{ $initials }}<img src="{{ asset('images/products/'.strtolower(reset($words)).'.jpg') }}" alt="" onerror="this.remove()"></span>
            <span>
              <b>{{ $c->name }}</b>
              @if($c->is_blacklisted)<span class="badge high" style="margin-left:4px">Blacklisted</span>@endif
            </span>
          </div>
        </td>
        <td>{{ $c->phone ?: '—' }}</td>
        <td>{{ $c->city ?: '—' }}</td>
        <td>{{ $c->total_orders }}</td>
        <td>{{ $c->delivered_orders }}</td>
        <td>{{ $c->rto_orders }}</td>
        <td>{{ $c->failed_deliveries }}</td>
        <td style="min-width:90px">
          @if($c->total_orders)
            <div style="font-size:9px;margin-bottom:4px">{{ $rate }}%</div>
            <div class="progress"><i style="width:{{ $rate }}%"></i></div>
          @else
            —
          @endif
        </td>
        <td>₨ {{ number_format($c->total_spent) }}</td>
        <td><a class="select" href="{{ route('customers.show', $c) }}" style="text-decoration:none">Profile</a></td>
      </tr>
    @empty
      <tr><td colspan="10" style="text-align:center;color:#8d8890;padding:24px">No customers found.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="pager">
  @if($customers->previousPageUrl())<a class="btn" href="{{ $customers->previousPageUrl() }}">← Previous</a>@else<span></span>@endif
  @if($customers->nextPageUrl())<a class="btn" href="{{ $customers->nextPageUrl() }}">Next →</a>@endif
</div>