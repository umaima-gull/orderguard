<div class="table-wrap">
  <table>
    <thead><tr><th>CASE</th><th>ORDER / CUSTOMER</th><th>ISSUE</th><th>STATUS</th><th>PRIORITY</th><th>AT RISK</th><th>DUE</th><th>ACTION</th></tr></thead>
    <tbody>
    @forelse($cases as $c)
      @php
        $badge = match($c->status) {
          'delivered' => 'low',
          'returned', 'closed_unresolved' => 'high',
          default => 'medium',
        };
        $due = $c->due_at;
        $dueText = !$due ? '—' : ($due->isToday() ? 'Today' : ($due->isPast() ? 'Overdue' : $due->format('M j')));
      @endphp
      <tr>
        <td><b class="order-id">{{ $c->case_number }}</b></td>
        <td><b>{{ $c->order->order_number ?? '—' }}</b><br><span style="color:#87958d">{{ $c->order->customer_name ?? '' }}</span></td>
        <td>{{ ucfirst(str_replace('_', ' ', $c->exception_type)) }}</td>
        <td><span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $c->status)) }}</span></td>
        <td>{{ ucfirst($c->priority) }}</td>
        <td>₨ {{ number_format($c->amount_at_risk) }}</td>
        <td>{{ $dueText }}</td>
        <td><a class="select" href="{{ route('rto-recovery.show', $c) }}" style="text-decoration:none">Open</a></td>
      </tr>
    @empty
      <tr><td colspan="8" style="text-align:center;color:#8d8890;padding:24px">No recovery cases found.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
<div class="pager">
  @if($cases->previousPageUrl())<a class="btn" href="{{ $cases->previousPageUrl() }}">← Previous</a>@else<span></span>@endif
  @if($cases->nextPageUrl())<a class="btn" href="{{ $cases->nextPageUrl() }}">Next →</a>@endif
</div>