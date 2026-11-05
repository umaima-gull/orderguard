<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function index()
    {
        $store = CurrentStore::get();

        $members = DB::table('store_user')
            ->join('users', 'users.id', '=', 'store_user.user_id')
            ->where('store_user.store_id', $store->id)->whereNull('users.deleted_at')
            ->select('users.id', 'users.name', 'users.email', 'users.status', 'users.last_login_at', 'store_user.role')
            ->orderByRaw("field(store_user.role,'owner','manager','agent','viewer')")->get();

        $limit = DB::table('subscriptions')->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.user_id', $store->owner_id)->orderByDesc('subscriptions.id')
            ->value('plans.max_team_members');

        return view('team.index', compact('store', 'members', 'limit'));
    }

    public function store(Request $request)
    {
        $store = CurrentStore::get();

        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:190|unique:users,email',
            'role'     => 'required|in:manager,agent,viewer',
            'password' => 'required|string|min:8',
        ]);

        $limit = DB::table('subscriptions')->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.user_id', $store->owner_id)->orderByDesc('subscriptions.id')
            ->value('plans.max_team_members');
        $count = DB::table('store_user')->where('store_id', $store->id)->count();

        if ($limit && $count >= $limit) {
            return back()->withErrors(['email' => "Your plan allows {$limit} team member(s). Upgrade to add more."]);
        }

        DB::transaction(function () use ($data, $store) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                'role' => 'team_member', 'status' => 'active',
            ]);
            DB::table('store_user')->insert([
                'store_id' => $store->id, 'user_id' => $user->id, 'role' => $data['role'], 'created_at' => now(),
            ]);
        });

        return redirect()->route('team')->with('status', $data['name'] . ' added as ' . $data['role'] . '. Share the password with them securely.');
    }

    public function role(Request $request, User $user)
    {
        $store = CurrentStore::get();
        $this->guard($store, $user);

        $role = $request->validate(['role' => 'required|in:manager,agent,viewer'])['role'];
        DB::table('store_user')->where('store_id', $store->id)->where('user_id', $user->id)->update(['role' => $role]);

        return redirect()->route('team')->with('status', 'Role updated.');
    }

    public function toggle(User $user)
    {
        $store = CurrentStore::get();
        $this->guard($store, $user);

        $user->update(['status' => $user->status === 'active' ? 'suspended' : 'active']);

        return redirect()->route('team')->with('status', $user->name . ($user->status === 'active' ? ' re-activated.' : ' suspended.'));
    }

    public function remove(User $user)
    {
        $store = CurrentStore::get();
        $this->guard($store, $user);

        DB::table('store_user')->where('store_id', $store->id)->where('user_id', $user->id)->delete();
        $user->delete();   // soft delete

        return redirect()->route('team')->with('status', 'Member removed.');
    }

    /** Only members of this store, never the owner, never yourself. */
    private function guard($store, User $user): void
    {
        $member = DB::table('store_user')->where('store_id', $store->id)->where('user_id', $user->id)->first();
        abort_unless($member, 404);
        abort_if($member->role === 'owner' || $user->id === auth()->id(), 403, 'This member cannot be changed.');
    }
}