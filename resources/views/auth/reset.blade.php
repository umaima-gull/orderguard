@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
<article class="card">
  <h1 class="auth-title">Choose a new <em>password</em></h1>
  <p class="auth-sub">This link works once and expires in 60 minutes.</p>

  @if($errors->any())<div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $email) }}" required></div>
    <div class="field"><label for="password">New password</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
    <button class="btn primary" style="width:100%;justify-content:center" type="submit">Reset password</button>
  </form>
</article>
@endsection