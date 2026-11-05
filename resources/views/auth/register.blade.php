@extends('layouts.guest')

@section('title', 'Create account')

@section('content')
<article class="card">
  <h1 class="auth-title">Create your <em>account</em></h1>
  <p class="auth-sub">14-day free trial. No card needed.</p>

  @if($errors->any())
    <div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="field">
      <label for="name">Your name</label>
      <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus maxlength="120">
    </div>
    <div class="field">
      <label for="store_name">Store name</label>
      <input id="store_name" type="text" name="store_name" value="{{ old('store_name') }}" required maxlength="150">
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
    </div>
    <div class="field">
      <label for="phone">Phone (optional)</label>
      <input id="phone" type="text" name="phone" value="{{ old('phone') }}" maxlength="30">
    </div>
    <div class="field">
      <label for="password">Password (min 8 characters)</label>
      <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="field">
      <label for="password_confirmation">Confirm password</label>
      <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
    </div>
    <button class="btn primary" style="width:100%;justify-content:center" type="submit">Create account</button>
  </form>

  <p class="auth-foot">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</article>
@endsection