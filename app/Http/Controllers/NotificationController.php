<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::mine()->orderByDesc('id')->simplePaginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function open(int $id)
    {
        $n = Notification::mine()->findOrFail($id);
        if (!$n->read_at) {
            $n->update(['read_at' => now(), 'status' => 'read']);
        }

        return redirect($n->targetUrl());
    }

    public function readAll()
    {
        Notification::mine()->whereNull('read_at')->update(['read_at' => now(), 'status' => 'read']);

        return back();
    }
}