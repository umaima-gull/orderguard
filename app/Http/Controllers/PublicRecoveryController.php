<?php

namespace App\Http\Controllers;

use App\Models\RecoveryAction;
use App\Models\RecoveryCase;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicRecoveryController extends Controller
{
    private const CLOSED = ['delivered', 'returned', 'closed_unresolved'];

    public function show(string $token)
    {
        [$case, $state] = $this->resolve($token);

        return $this->page($case, $state, $token);
    }

    public function submit(Request $request, string $token)
    {
        [$case, $state] = $this->resolve($token);
        if ($state !== 'open') {
            return $this->page($case, $state, $token);
        }

        $data = $request->validate([
            'corrected_address' => 'nullable|string|min:10|max:300',
            'corrected_phone'   => ['nullable', 'regex:/^[0-9+\s\-]{10,20}$/'],
            'reschedule_date'   => 'nullable|date|after_or_equal:today|before_or_equal:' . now()->addDays(14)->toDateString(),
        ], [
            'corrected_address.min' => 'Please enter the full address (at least 10 characters).',
            'corrected_phone.regex' => 'Please enter a valid phone number.',
            'reschedule_date.before_or_equal' => 'Please choose a date within the next 14 days.',
        ]);

        $address = trim($data['corrected_address'] ?? '');
        $phone   = trim($data['corrected_phone'] ?? '');
        $date    = $data['reschedule_date'] ?? null;

        if ($address === '' && !$date) {
            return back()->withErrors(['corrected_address' => 'Please correct your address or choose a delivery date.'])->withInput();
        }

        $order = $case->order;

        DB::transaction(function () use ($case, $order, $address, $phone, $date) {
            $parts = [];

            if ($address !== '') {
                $orderUpdate = ['shipping_address_line1' => Str::limit($address, 250, ''), 'is_address_complete' => 1];
                if ($phone !== '') {
                    $digits = preg_replace('/\D/', '', $phone);
                    if (str_starts_with($digits, '0')) $digits = '92' . substr($digits, 1);
                    $orderUpdate['customer_phone'] = $phone;
                    $orderUpdate['customer_phone_normalized'] = substr($digits, 0, 20);
                }
                $order->update($orderUpdate);

                RecoveryAction::create([
                    'recovery_case_id' => $case->id, 'store_id' => $case->store_id,
                    'action_type' => 'address_updated', 'channel' => 'system', 'performed_by' => null,
                    'result' => 'success',
                    'details' => ['by' => 'customer', 'corrected_address' => $address, 'corrected_phone' => $phone ?: null],
                ]);
                $parts[] = 'corrected the address';
            }

            if ($date) {
                RecoveryAction::create([
                    'recovery_case_id' => $case->id, 'store_id' => $case->store_id,
                    'action_type' => 'reschedule_requested', 'channel' => 'system', 'performed_by' => null,
                    'result' => 'success',
                    'details' => ['by' => 'customer', 'date' => $date],
                ]);
                $parts[] = 'asked for delivery on ' . $date;
            }

            $case->update([
                'corrected_address'         => $address !== '' ? $address : $case->corrected_address,
                'corrected_phone'           => $phone !== '' ? $phone : $case->corrected_phone,
                'reschedule_date'           => $date ?: $case->reschedule_date,
                'status'                    => $date ? 'reschedule_requested' : 'address_corrected',
                'customer_response'         => Str::limit('Customer ' . implode(' and ', $parts) . '.', 490, ''),
                'first_contact_at'          => $case->first_contact_at ?? now(),
                'due_at'                    => now(),
                'recovery_token_hash'       => null,      // link works only once
                'recovery_token_expires_at' => null,
            ]);

            Notifier::merchant(
                $order->store,
                'Customer replied to recovery',
                $order->customer_name . ' ' . implode(' and ', $parts) . ' for ' . $order->order_number . '. Book a retry.',
                $order->id,
                $case->id
            );
        });

        return $this->page($case, 'done', $token);
    }

    /** Returns [case|null, state]: open | closed | expired | invalid */
    private function resolve(string $token): array
    {
        $case = RecoveryCase::with('order.store', 'order.items')
            ->where('recovery_token_hash', hash('sha256', $token))->first();

        if (!$case) {
            return [null, 'invalid'];
        }
        if (in_array($case->status, self::CLOSED, true)) {
            return [$case, 'closed'];
        }
        if (!$case->recovery_token_expires_at || $case->recovery_token_expires_at->isPast()) {
            return [$case, 'expired'];
        }

        return [$case, 'open'];
    }

    private function page(?RecoveryCase $case, string $state, string $token)
    {
        return view('recover.show', [
            'case'  => $case,
            'order' => $case?->order,
            'state' => $state,
            'token' => $token,
        ]);
    }
}