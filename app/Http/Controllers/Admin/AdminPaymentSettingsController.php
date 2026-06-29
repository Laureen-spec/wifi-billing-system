<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminPaymentSettingsController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user();

        $settings = AdminPaymentSetting::firstOrCreate(
            ['admin_id' => $admin->id],
            [
                'mode' => 'system_payment',
                'gateway' => 'mpesa',
                'fee_handling' => 'add_to_checkout',
                'is_active' => true,
            ]
        );

        return Inertia::render('Admin/Settings/PaymentSettings', [
            'settings' => $settings,
            'platformFee' => 2.5,
            'paymentLogs' => [],
        ]);
    }

    public function update(Request $request)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'mode' => ['required', Rule::in(['system_payment', 'own_payment'])],
            'gateway' => ['nullable', Rule::in(['mpesa'])],

            'method_type' => [
                Rule::requiredIf($request->mode === 'own_payment'),
                'nullable',
                Rule::in(['till', 'paybill', 'phone']),
            ],

            'till_number' => [
                Rule::requiredIf($request->mode === 'own_payment' && $request->method_type === 'till'),
                'nullable',
                'string',
                'max:30',
            ],

            'paybill_number' => [
                Rule::requiredIf($request->mode === 'own_payment' && $request->method_type === 'paybill'),
                'nullable',
                'string',
                'max:30',
            ],

            'account_number' => [
                Rule::requiredIf($request->mode === 'own_payment' && $request->method_type === 'paybill'),
                'nullable',
                'string',
                'max:80',
            ],

            'phone_number' => [
                Rule::requiredIf($request->mode === 'own_payment' && $request->method_type === 'phone'),
                'nullable',
                'string',
                'max:30',
            ],

            'fee_handling' => ['required', Rule::in(['add_to_checkout', 'deduct_from_revenue'])],
        ]);

        if ($validated['mode'] === 'system_payment') {
            $validated['method_type'] = null;
            $validated['till_number'] = null;
            $validated['paybill_number'] = null;
            $validated['account_number'] = null;
            $validated['phone_number'] = null;
        }

        $validated['gateway'] = 'mpesa';
        $validated['is_active'] = true;

        AdminPaymentSetting::updateOrCreate(
            ['admin_id' => $admin->id],
            $validated
        );

        return back()->with('success', 'Payment settings updated successfully.');
    }
}
