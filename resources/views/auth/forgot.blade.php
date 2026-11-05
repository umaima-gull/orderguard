@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
<article class="card">
  <h1 class="auth-title">Forgot your <em>password?</em></h1>
  <p class="auth-sub">Enter your email and we will send you a reset link.</p>

  @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
    <button class="btn primary" style="width:100%;justify-content:center" type="submit">Send reset link</button>
  </form>
  <p class="auth-foot"><a href="{{ route('login') }}">Back to log in</a></p>
</article>
@endsection