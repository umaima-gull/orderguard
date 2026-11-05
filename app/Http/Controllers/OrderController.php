<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReview;
use App\Models\Store;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
   public function index(Request $request)
{
    $store = \App\Support\CurrentStore::get();

    $query = Order::with('items.product')->where('store_id', $store->id);

    if ($risk = $request->query('risk'))     $query->where('risk_level', $risk);
    if ($status = $request->query('status')) $query->where('status', $status);
    if ($term = $request->query('q')) {
        $query->where(fn ($w) => $w->where('order_number', 'like', "%{$term}%")
                                   ->orWhere('customer_name', 'like', "%{$term}%"));
    }

    $orders = $query->orderByDesc('placed_at')->simplePaginate(10)->withQueryString();

    // AJAX request ho to sirf table wala partial wapas bhejo
    if ($request->ajax()) {
        return view('orders._table', compact('orders'));
    }

    return view('orders.index', compact('orders'));
}

    public function show(Order $order)
    {
        $order->load('customer', 'items.product', 'latestRisk.factors', 'shipments.courier');
        $reviews = OrderReview::where('order_id', $order->id)->orderByDesc('created_at')->get();

        return view('orders.show', compact('order', 'reviews'));
    }

    public function decide(Request $request, Order $order)
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,held,rejected',
            'note'     => 'nullable|string|max:500',
        ]);

        $map = [
            'approved' => 'approved',
            'held'     => 'on_hold',
            'rejected' => 'rejected',
        ];

        $order->update([
            'status'            => $map[$data['decision']],
            'merchant_decision' => $data['decision'],
            'approved_at'       => $data['decision'] === 'approved' ? now() : null,
        ]);

        OrderReview::create([
            'order_id'  => $order->id,
            'store_id'  => $order->store_id,
            'user_id'   => auth()->id(),
            'decision'  => $data['decision'],
            'note'      => $data['note'] ?? null,
        ]);

        return redirect()->route('orders.show', $order)
            ->with('status', 'Order ' . $order->order_number . ' marked as ' . $data['decision'] . '.');
    }

    public function rescore(Order $order, RiskScoringService $svc)
    {
        $svc->evaluate($order);

        return redirect()->route('orders.show', $order)->with('status', 'Risk score recalculated.');
    }
}