<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        $store = \App\Support\CurrentStore::get();

        $sub = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.user_id', $store->owner_id)
            ->orderByDesc('subscriptions.id')
            ->select('subscriptions.*', 'plans.name as plan_name', 'plans.monthly_order_limit',
                     'plans.max_stores', 'plans.max_team_members', 'plans.max_courier_accounts',
                     'plans.whatsapp_enabled', 'plans.price_monthly', 'plans.currency as plan_currency')
            ->first();

        $usedPct = $sub && $sub->monthly_order_limit
            ? min(100, round($sub->orders_used_in_period / $sub->monthly_order_limit * 100))
            : 0;

        $courierCount = DB::table('courier_accounts')->where('store_id', $store->id)->count();
        $teamCount    = DB::table('store_user')->where('store_id', $store->id)->count();

        return view('settings.index', compact('store', 'sub', 'usedPct', 'courierCount', 'teamCount'));
    }

    public function updateStore(Request $request)
    {
        $store = \App\Support\CurrentStore::get();

        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'country_code' => 'required|alpha|size:2',
            'currency'     => 'required|alpha|size:3',
            'timezone'     => 'required|timezone',
        ]);

        $data['country_code'] = strtoupper($data['country_code']);
        $data['currency']     = strtoupper($data['currency']);

        $store->update($data);

        return redirect()->route('settings')->with('status', 'Store profile saved.');
    }

    public function updateRisk(Request $request, RiskScoringService $svc)
    {
        $store = \App\Support\CurrentStore::get();

        $data = $request->validate([
            'low_risk_max'          => 'required|integer|min:0|max:98',
            'medium_risk_max'       => 'required|integer|gt:low_risk_max|max:99',
            'auto_hold_high_risk'   => 'required|in:0,1',
            'auto_send_verification'=> 'required|in:0,1',
        ]);

        $store->update($data);

        $msg = 'Risk settings saved.';
        if ($request->boolean('rescore')) {
            $orders = Order::where('store_id', $store->id)->get();
            foreach ($orders as $order) {
                $svc->evaluate($order);
            }
            $msg .= ' ' . $orders->count() . ' orders re-scored.';
        } else {
            $msg .= ' New thresholds apply to the next scoring.';
        }

        return redirect()->route('settings')->with('status', $msg);
    }
}