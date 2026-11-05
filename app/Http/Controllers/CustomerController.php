<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $sid = \App\Support\CurrentStore::get()->id;

        $query = Customer::where('store_id', $sid);

        if ($t = $request->query('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$t}%")
                ->orWhere('phone', 'like', "%{$t}%")
                ->orWhere('city', 'like', "%{$t}%"));
        }

        switch ($request->query('filter')) {
            case 'blacklisted':
                $query->where('is_blacklisted', 1);
                break;
            case 'problems':
                $query->where(fn ($w) => $w->where('failed_deliveries', '>', 0)->orWhere('rto_orders', '>', 0)
                    ->orWhere('unconfirmed_orders', '>', 0));
                break;
            case 'trusted':
                $query->where('delivered_orders', '>=', 2)->where('rto_orders', 0)->where('is_blacklisted', 0);
                break;
        }

        $customers = $query->orderByDesc('total_orders')->orderBy('name')->simplePaginate(10)->withQueryString();

        $counts = [
            'total'       => Customer::where('store_id', $sid)->count(),
            'blacklisted' => Customer::where('store_id', $sid)->where('is_blacklisted', 1)->count(),
            'repeat'      => Customer::where('store_id', $sid)->where('total_orders', '>=', 2)->count(),
            'problems'    => Customer::where('store_id', $sid)
                ->where(fn ($w) => $w->where('failed_deliveries', '>', 0)->orWhere('rto_orders', '>', 0))->count(),
        ];

        
       if ($request->ajax()) {
    return view('customers._table', compact('customers'));
}

        return view('customers.index', compact('customers', 'counts'));
    }

    public function show(Customer $customer)
    {
        $orders = Order::with('items', 'shipments.courier')
            ->where('customer_id', $customer->id)->orderByDesc('placed_at')->get();

        $rate = $customer->total_orders
            ? round($customer->delivered_orders / $customer->total_orders * 100)
            : 0;

        return view('customers.show', compact('customer', 'orders', 'rate'));
    }

    public function blacklist(Request $request, Customer $customer, RiskScoringService $svc)
    {
        $makeBlacklisted = $request->input('action') === 'add';

        $data = $request->validate([
            'action' => 'required|in:add,remove',
            'reason' => $makeBlacklisted ? 'required|string|max:255' : 'nullable',
        ]);

        $customer->update([
            'is_blacklisted'   => $makeBlacklisted ? 1 : 0,
            'blacklist_reason' => $makeBlacklisted ? $data['reason'] : null,
        ]);

        // Re-score this customer's open orders so the change shows up immediately
        $open = Order::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'under_review', 'verification_sent'])->get();
        foreach ($open as $order) {
            $svc->evaluate($order);
        }

        $msg = $makeBlacklisted ? 'Customer blacklisted.' : 'Customer removed from blacklist.';
        if ($open->count()) {
            $msg .= ' ' . $open->count() . ' open order(s) re-scored.';
        }

        return redirect()->route('customers.show', $customer)->with('status', $msg);
    }

    public function notes(Request $request, Customer $customer)
    {
        $data = $request->validate(['notes' => 'nullable|string|max:2000']);
        $customer->update(['notes' => $data['notes']]);

        return redirect()->route('customers.show', $customer)->with('status', 'Notes saved.');
    }

    public function refresh(Customer $customer)
    {
        DB::statement('CALL sp_refresh_customer_stats(?)', [$customer->id]);

        return redirect()->route('customers.show', $customer)->with('status', 'Customer statistics refreshed.');
    }
}