@php
  $n = max(1, count($chart['labels']));
  $peak = max(max($chart['orders'] ?: [0]), max($chart['recovered'] ?: [0]));
  $max = max(4, (int) (ceil($peak / 4) * 4));
  $x0 = 35; $w = 605; $base = 160; $top = 20; $h = $base - $top;
  $slot = $w / $n; $bw = min(36, $slot * 0.6);
  $step = max(1, (int) ceil($n / 7));
  $points = [];
@endphp
<svg class="chart" viewBox="0 0 650 190" preserveAspectRatio="none" role="img" aria-label="Orders and recovery trend">
  <defs>
    <linearGradient id="barLo" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#ffc4cd"/><stop offset="1" stop-color="#ffeef0"/></linearGradient>
  </defs>

  @for($i = 0; $i <= 3; $i++)
    @php $gy = $base - $h * ($i + 1) / 4 + 0; @endphp
    <line class="gridline" x1="{{ $x0 }}" y1="{{ $gy }}" x2="640" y2="{{ $gy }}"/>
    <text x="0" y="{{ $gy + 3 }}">{{ (int) round($max * ($i + 1) / 4) }}</text>
  @endfor
  <line class="gridline" x1="{{ $x0 }}" y1="{{ $base }}" x2="640" y2="{{ $base }}"/>

  @foreach($chart['orders'] as $i => $v)
    @php
      $bh = $v / $max * $h;
      $bx = $x0 + $slot * $i + ($slot - $bw) / 2;
      $cx = $x0 + $slot * $i + $slot / 2;
      $points[] = [$cx, $base - ($chart['recovered'][$i] / $max * $h)];
    @endphp
    @if($v > 0)
      <rect x="{{ round($bx, 1) }}" y="{{ round($base - $bh, 1) }}" width="{{ round($bw, 1) }}" height="{{ round($bh, 1) }}" rx="6" fill="{{ $v === max($chart['orders']) ? '#e8112d' : 'url(#barLo)' }}"><title>{{ $chart['labels'][$i] }}: {{ $v }} orders</title></rect>
    @endif
    @if($i % $step === 0)
      <text x="{{ round($cx - 10, 1) }}" y="181">{{ $chart['labels'][$i] }}</text>
    @endif
  @endforeach

  <path class="line2" d="M{{ collect($points)->map(fn ($p) => round($p[0], 1) . ' ' . round($p[1], 1))->implode(' L') }}"/>
  @foreach($points as $i => $p)
    <circle cx="{{ round($p[0], 1) }}" cy="{{ round($p[1], 1) }}" r="{{ $n > 31 ? 1.6 : 3.2 }}" fill="#fff" stroke="#e8112d" stroke-width="1.8"><title>{{ $chart['labels'][$i] }}: {{ $chart['recovered'][$i] }} recovered</title></circle>
  @endforeach
</svg>