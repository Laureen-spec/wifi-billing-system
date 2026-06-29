<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleRule = auth()->user()->type === 'superadmin' ? 'nullable' : 'required_without:type|exists:roles,id';
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:users,email,NULL,id,created_by,' . creatorId()
            ],
            'mobile_no' => 'nullable|string|regex:/^\+\d{1,3}\d{9,13}$/',
            'password' => 'required|confirmed|min:6',
            'role_id' => $roleRule,
            'type' => 'nullable|exists:roles,id',
            'is_enable_login' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required_without' => __('Role is required.'),
        ];
    }
}
