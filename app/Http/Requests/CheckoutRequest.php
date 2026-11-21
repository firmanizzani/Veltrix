<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === Role::Cashier;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => 'required|uuid',
            'items' => 'required|array|min:1|max:100',
            'items.*.branch_product_id' =>
                'required|integer|distinct|exists:branch_products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.expected_price' => 'required|integer|min:0',
            'method' => 'required|in:cash,qris,card,ewallet',
            'tendered' => 'required|integer|min:0|max:100000000',
            'customer_name' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:120',
        ];
    }
}
