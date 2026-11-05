@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
<article class="card">
  <h1 class="auth-title">Welcome <em>back</em></h1>
  <p class="auth-sub">Log in to review risky orders and recover deliveries.</p>

  @if($errors->any())
    <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
  @endif
  @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
  <form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="field">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required autocomplete="current-password">
    </div>
    <label class="check">    <p style="text-align:right;margin:-6px 0 12px;font-size:11px"><a href="{{ route('password.request') }}" style="color:#e8112d;text-decoration:none">Forgot password?</a></p><input type="checkbox" name="remember" value="1"> Keep me logged in</label>
    <button class="btn primary" style="width:100%;justify-content:center" type="submit">Log in</button>
  </form>

  <p class="auth-foot">New to OrderGuard? <a href="{{ route('register') }}">Create an account</a></p>
</article>
@endsection