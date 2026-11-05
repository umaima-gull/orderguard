@extends('layouts.app')

@section('title', 'Profile')
@section('crumb', 'Profile')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</div>
    <h1>My <em>profile</em></h1>
    <p>{{ $user->email }}</p>
  </div>
</section>

@if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
@if($errors->any())<div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>@endif

<section class="detail-grid">

  <article class="card">
    <div class="card-head"><div><h3>Personal details</h3><p>Email cannot be changed here</p></div></div>
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
      @csrf
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
        <div class="avatar" style="width:64px;height:64px;font-size:18px">{{ $user->initials() }}@if($user->avatar_url)<img src="{{ asset('images/'.$user->avatar_url) }}" alt="" onerror="this.remove()">@endif</div>
        <div class="field" style="margin:0;flex:1">
          <label for="avatar">Profile picture (JPG, PNG or WebP, max 2 MB)</label>
          <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp">
        </div>
      </div>
      <div class="field"><label for="name">Name</label><input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="120" required></div>
      <div class="field"><label for="phone">Phone</label><input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30"></div>
      <button class="btn primary" type="submit">Save profile</button>
    </form>
  </article>

  <article class="card">
    <div class="card-head"><div><h3>Change password</h3><p>Use at least 8 characters</p></div></div>
    <form method="POST" action="{{ route('profile.password') }}">
      @csrf
      <div class="field"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" required autocomplete="current-password"></div>
      <div class="field"><label for="password">New password</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
      <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
      <button class="btn primary" type="submit">Change password</button>
    </form>
  </article>

</section>

@endsection