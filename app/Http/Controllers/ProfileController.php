<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'   => 'required|string|max:120',
            'phone'  => 'nullable|string|max:30',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $update = ['name' => $data['name'], 'phone' => $data['phone'] ?? null];

        if ($request->hasFile('avatar')) {
            $dir = public_path('images/avatars');
            File::ensureDirectoryExists($dir);

            // Remove the previous uploaded avatar (never the shared images)
            if ($user->avatar_url && str_starts_with($user->avatar_url, 'avatars/')) {
                File::delete(public_path('images/' . $user->avatar_url));
            }

            $file = $request->file('avatar');
            $name = Str::random(24) . '.' . $file->extension();
            $file->move($dir, $name);
            $update['avatar_url'] = 'avatars/' . $name;
        }

        $user->update($update);

        return redirect()->route('profile')->with('status', 'Profile updated.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:8|confirmed|different:current_password',
        ]);

        auth()->user()->update(['password' => $data['password']]);   // hashed by the model cast
        $request->session()->regenerate();

        return redirect()->route('profile')->with('status', 'Password changed.');
    }
}