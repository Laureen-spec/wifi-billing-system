<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');
        
        if ($plan && $plan->custom_plan) {
            return [
                'package_price_yearly' => 'required|numeric|min:0',
                'package_price_monthly' => 'required|numeric|min:0',
                'price_per_user_monthly' => 'nullable|numeric|min:0',
                'price_per_user_yearly' => 'nullable|numeric|min:0',
                'price_per_storage_monthly' => 'nullable|numeric|min:0',
                'price_per_storage_yearly' => 'nullable|numeric|min:0',
                'hotspot_revenue_fee_percent' => 'nullable|numeric|min:0|max:100',
                'router_limit' => 'nullable|integer|min:0',
            ];
        }

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'number_of_users' => 'required',
            'storage_limit' => 'nullable|integer|min:0|max:100',
            'status' => 'boolean',
            'free_plan' => 'boolean',
            'modules' => 'nullable|array',
            'package_price_yearly' => 'required|numeric|min:0',
            'package_price_monthly' => 'required|numeric|min:0',
            'trial' => 'boolean',
            'trial_days' => 'required_if:trial,true|integer|min:0',
            'hotspot_revenue_fee_percent' => 'nullable|numeric|min:0|max:100',
            'router_limit' => 'nullable|integer|min:0',
        ];
    }
}