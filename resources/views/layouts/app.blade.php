<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>OrderGuard — @yield('title', 'Command Center')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/orderguard.css') }}">
</head>
<body>
 @if(\App\Support\Permissions::role() === 'viewer')
  <div class="flash" style="margin:0;border-radius:0;text-align:center">You have view-only access. Ask the store owner to change your role.</div>
@endif 
@include('partials.icons')

<div class="shell">
  @include('partials.sidebar')
  <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

  <main class="main">
    @include('partials.topbar')
    @yield('content')
    <div class="footer-note">ORDERGUARD · SMARTER ORDERS. FEWER RETURNS. &nbsp; | &nbsp; Dashboard preview with illustrative sample data</div>
  </main>
</div>

<div class="toast" id="toast"></div>
<script src="{{ asset('js/orderguard.js') }}"></script>
@stack('scripts')
</body>

</html>