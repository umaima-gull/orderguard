<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\CourierAccount;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CourierController extends Controller
{
    public function index(Request $request)
    {
        $store = \App\Support\CurrentStore::get();
        $sid = $store->id;

        $accounts = CourierAccount::with('courier')->where('store_id', $sid)
            ->orderByDesc('is_default')->orderBy('id')->get();

        $stats = DB::table('shipments')->where('store_id', $sid)
            ->selectRaw('courier_id, count(*) as total,
                sum(status = "delivered") as delivered,
                sum(status in ("returning","returned")) as returned,
                avg(case when delivered_at is not null and dispatched_at is not null
                    then timestampdiff(HOUR, dispatched_at, delivered_at) / 24 end) as days')
            ->groupBy('courier_id')->get()->keyBy('courier_id');

        $rows = $accounts->map(function ($a) use ($stats) {
            $s = $stats->get($a->courier_id);
            $total = $s->total ?? 0;
            return (object) [
                'account'   => $a,
                'total'     => $total,
                'delivered' => $s->delivered ?? 0,
                'rate'      => $total ? round($s->delivered / $total * 100, 1) : null,
                'rto'       => $total ? round($s->returned / $total * 100, 1) : null,
                'days'      => $s && $s->days !== null ? round($s->days, 1) : null,
            ];
        });

        $all = $stats->sum('total');
        $summary = [
            'connected' => $accounts->where('is_active', true)->count(),
            'rate'      => $all ? round($stats->sum('delivered') / $all * 100, 1) : 0,
            'rto'       => $all ? round($stats->sum('returned') / $all * 100, 1) : 0,
            'best'      => $rows->whereNotNull('rate')->sortByDesc('rate')->first(),
        ];

        $available = Courier::where('is_active', 1)
            ->whereNotIn('id', $accounts->pluck('courier_id'))->get();

        $cityQuery = DB::table('v_courier_performance')->where('store_id', $sid);
        if ($c = $request->query('courier')) {
            $cityQuery->where('courier', $c);
        }
        $cities = $cityQuery->orderBy('courier')->orderByDesc('total_shipments')->limit(40)->get();

        return view('couriers.index', compact('rows', 'summary', 'available', 'cities', 'accounts'));
    }

    public function connect(Request $request)
    {
        $store = \App\Support\CurrentStore::get();
        $data = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'label'      => 'nullable|string|max:100',
        ]);

        $label = $data['label'] ?: 'Default';
        $exists = CourierAccount::where('store_id', $store->id)
            ->where('courier_id', $data['courier_id'])->where('label', $label)->exists();
        if ($exists) {
            return back()->withErrors(['courier_id' => 'This courier is already connected.']);
        }

        $first = !CourierAccount::where('store_id', $store->id)->exists();
        CourierAccount::create([
            'store_id'   => $store->id,
            'courier_id' => $data['courier_id'],
            'label'      => $label,
            'is_default' => $first,
            'is_active'  => 1,
        ]);

        return redirect()->route('courier-partners')->with('status', 'Courier connected.');
    }

    public function makeDefault(CourierAccount $account)
    {
        $this->own($account);
        CourierAccount::where('store_id', $account->store_id)->update(['is_default' => 0]);
        $account->update(['is_default' => 1, 'is_active' => 1]);

        return redirect()->route('courier-partners')->with('status', $account->courier->name . ' is now the default courier.');
    }

    public function toggle(CourierAccount $account)
    {
        $this->own($account);
        if ($account->is_default && $account->is_active) {
            return back()->withErrors(['courier_id' => 'Choose another default courier before turning this one off.']);
        }
        $account->update(['is_active' => !$account->is_active]);

        return redirect()->route('courier-partners')
            ->with('status', $account->courier->name . ($account->is_active ? ' turned on.' : ' turned off.'));
    }

    public function credentials(Request $request, CourierAccount $account)
    {
        $this->own($account);
        $data = $request->validate([
            'api_key'        => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
        ]);

        $current = [];
        if ($account->credentials) {
            try { $current = json_decode(Crypt::decryptString($account->credentials), true) ?: []; }
            catch (\Throwable $e) { $current = []; }
        }
        foreach (['api_key', 'account_number'] as $k) {
            if (!empty($data[$k])) $current[$k] = $data[$k];   // blank = keep the old value
        }

        if (!$current) {
            return back()->withErrors(['api_key' => 'Enter at least one credential.']);
        }

        $account->update([
            'credentials'      => Crypt::encryptString(json_encode($current)),
            'last_verified_at' => null,
        ]);

        return redirect()->route('courier-partners')->with('status', 'Credentials saved securely. Run "Test connection" to verify.');
    }

    public function test(CourierAccount $account)
    {
        $this->own($account);
        $account->load('courier');

        if (!$account->courier->supports_api) {
            return back()->withErrors(['api_key' => $account->courier->name . ' has no API. Book shipments manually.']);
        }
        if (!$account->credentials) {
            return back()->withErrors(['api_key' => 'Save credentials first.']);
        }

        // Simulated: a real API call will be made here later
        $account->update(['last_verified_at' => now()]);

        return redirect()->route('courier-partners')->with('status', $account->courier->name . ' connection verified (simulated).');
    }

    public function disconnect(CourierAccount $account)
    {
        $this->own($account);
        if ($account->is_default) {
            return back()->withErrors(['courier_id' => 'Set another courier as default before disconnecting this one.']);
        }
        $name = $account->courier->name;
        $account->delete();

        return redirect()->route('courier-partners')->with('status', $name . ' disconnected.');
    }

    private function own(CourierAccount $account): void
    {
        abort_unless($account->store_id === \App\Support\CurrentStore::get()->id, 403);
    }
}