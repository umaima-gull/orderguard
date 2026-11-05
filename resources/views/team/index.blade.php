@extends('layouts.app')

@section('title', 'Team')
@section('crumb', 'Team')

@section('content')

<section class="welcome">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1><em>Team</em></h1>
    <p>{{ $members->count() }} of {{ $limit ?? 'unlimited' }} members used on your plan.</p>
  </div>
</section>

@if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
@if($errors->any())<div class="flash" style="background:#ffe6ea;border-color:#fcdbe1;color:#b30d23">{{ $errors->first() }}</div>@endif

<article class="card" style="margin-bottom:14px">
  <div class="card-head"><div><h3>Add a team member</h3><p>They log in with this email and password</p></div></div>
  <form method="POST" action="{{ route('team.store') }}" class="inline-form" style="flex-wrap:wrap">
    @csrf
    <input class="select" type="text" name="name" placeholder="Full name" value="{{ old('name') }}" required>
    <input class="select" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
    <input class="select" type="text" name="password" placeholder="Temporary password (min 8)" minlength="8" required>
    <select class="select" name="role">
      <option value="agent">Agent</option>
      <option value="manager">Manager</option>
      <option value="viewer">Viewer</option>
    </select>
    <button class="select ok" type="submit">Add member</button>
  </form>
</article>

<article class="card" style="margin-bottom:14px">
  <div class="card-head"><div><h3>Members</h3></div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>NAME</th><th>EMAIL</th><th>ROLE</th><th>STATUS</th><th>LAST LOGIN</th><th>ACTIONS</th></tr></thead>
      <tbody>
      @foreach($members as $m)
        <tr>
          <td><b>{{ $m->name }}</b>@if($m->id === auth()->id()) <span class="badge low">You</span>@endif</td>
          <td>{{ $m->email }}</td>
          <td>
            @if($m->role === 'owner' || $m->id === auth()->id())
              {{ ucfirst($m->role) }}
            @else
              <form method="POST" action="{{ route('team.role', $m->id) }}" class="inline-form">
                @csrf
                <select class="select" name="role" onchange="this.form.submit()">
                  @foreach(['manager','agent','viewer'] as $r)<option value="{{ $r }}" @selected($m->role === $r)>{{ ucfirst($r) }}</option>@endforeach
                </select>
              </form>
            @endif
          </td>
          <td><span class="badge {{ $m->status === 'active' ? 'low' : 'high' }}">{{ ucfirst($m->status) }}</span></td>
          <td>{{ $m->last_login_at ? \Illuminate\Support\Carbon::parse($m->last_login_at)->diffForHumans() : 'Never' }}</td>
          <td>
            @if($m->role !== 'owner' && $m->id !== auth()->id())
              <div class="inline-form">
                <form method="POST" action="{{ route('team.toggle', $m->id) }}">@csrf<button class="select" type="submit">{{ $m->status === 'active' ? 'Suspend' : 'Activate' }}</button></form>
                <form method="POST" action="{{ route('team.remove', $m->id) }}" onsubmit="return confirm('Remove {{ $m->name }}?')">@csrf<button class="select no" type="submit">Remove</button></form>
              </div>
            @else — @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</article>

<article class="card">
  <div class="card-head"><div><h3>What each role can do</h3></div></div>
  <div class="table-wrap"><table>
    <thead><tr><th>ROLE</th><th>ACCESS</th></tr></thead>
    <tbody>
      <tr><td><b>Owner</b></td><td>Everything, including team, settings, integrations and courier credentials</td></tr>
      <tr><td><b>Manager</b></td><td>Orders, verification, shipments, recovery, risk rules, blacklist, courier setup</td></tr>
      <tr><td><b>Agent</b></td><td>Approve/hold/reject orders, send verification, update shipments, handle recovery cases</td></tr>
      <tr><td><b>Viewer</b></td><td>View everything, change nothing</td></tr>
    </tbody>
  </table></div>
</article>

@endsection