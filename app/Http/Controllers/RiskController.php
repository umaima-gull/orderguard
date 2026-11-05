<?php

namespace App\Http\Controllers;

use App\Models\FraudRule;
use App\Models\Order;
use App\Models\Store;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;

class RiskController extends Controller
{
    public function index()
    {
        $store = \App\Support\CurrentStore::get();
        $sid = $store->id;

        $counts = [
            'high'   => Order::where('store_id', $sid)->where('risk_level', 'high')->count(),
            'medium' => Order::where('store_id', $sid)->where('risk_level', 'medium')->count(),
            'low'    => Order::where('store_id', $sid)->where('risk_level', 'low')->count(),
            'none'   => Order::where('store_id', $sid)->whereNull('risk_level')->count(),
        ];

        // Flagged = medium or high risk, highest score first
        $flagged = Order::with('latestRisk.factors')
            ->where('store_id', $sid)
            ->whereIn('risk_level', ['medium', 'high'])
            ->orderByDesc('risk_score')
            ->get();

        $rules = FraudRule::where('store_id', $sid)->orderBy('priority')->get();

        return view('risk.index', compact('store', 'counts', 'flagged', 'rules'));
    }

    public function updateRule(Request $request, FraudRule $rule)
    {
        $store = \App\Support\CurrentStore::get();
        abort_unless($rule->store_id === $store->id, 403);

        $data = $request->validate([
            'score_impact' => 'required|integer|between:-100,100',
            'is_active'    => 'required|in:0,1',
        ]);

        $rule->update($data);

        return redirect()->route('risk-intelligence')
            ->with('status', 'Rule "' . $rule->name . '" updated. Re-score orders to apply it.');
    }

    public function rescoreAll(RiskScoringService $svc)
    {
        $store = \App\Support\CurrentStore::get();
        $orders = Order::where('store_id', $store->id)->get();

        foreach ($orders as $order) {
            $svc->evaluate($order);
        }

        return redirect()->route('risk-intelligence')
            ->with('status', $orders->count() . ' orders re-scored.');
    }
}