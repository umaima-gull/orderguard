<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Suspended or invited users cannot log in
        if (Auth::attempt($credentials + ['status' => 'active'], $request->boolean('remember'))) {
            $request->session()->regenerate();
            Auth::user()->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

            return redirect()->intended('/');
        }

        return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:120',
            'email'      => 'required|email|max:190|unique:users,email',
            'phone'      => 'nullable|string|max:30',
            'store_name' => 'required|string|max:150',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'phone'    => $data['phone'] ?? null,
                'password' => $data['password'],        // hashed by the model cast
                'role'     => 'merchant_owner',
                'status'   => 'active',
            ]);

            $store = Store::create([
                'owner_id'       => $user->id,
                'name'           => $data['store_name'],
                'platform'       => 'manual',
                'webhook_secret' => Str::random(40),
                'status'         => 'pending',
            ]);

            DB::table('store_user')->insert([
                'store_id' => $store->id, 'user_id' => $user->id, 'role' => 'owner', 'created_at' => now(),
            ]);

            $planId = DB::table('plans')->where('code', 'free_trial')->value('id');
            DB::table('subscriptions')->insert([
                'user_id' => $user->id, 'plan_id' => $planId, 'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'current_period_start' => now()->toDateString(),
                'current_period_end' => now()->addDays(14)->toDateString(),
                'orders_used_in_period' => 0,
            ]);

            // Give the new store its own copy of the default fraud rules
            DB::statement('CALL sp_copy_default_rules(?)', [$store->id]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('status', 'Welcome to OrderGuard! Your 14-day free trial has started.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}