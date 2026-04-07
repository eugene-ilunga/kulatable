<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for submitting a new order via POS API.
 *
 * @api POST /api/application-integration/pos/orders
 */
class SubmitOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Items (required)
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|min:1',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.modifiers' => 'nullable|array',
            'items.*.modifiers.*.id' => 'nullable|integer',
            'items.*.modifiers.*.price' => 'nullable|numeric|min:0',
            'items.*.variation_id' => 'nullable|integer|min:1',
            'items.*.note' => 'nullable|string|max:500',

            // Order type
            'order_type' => 'nullable|string|max:50',
            'order_type_id' => 'nullable|integer|min:1',

            // Table (for dine-in)
            'table_id' => 'nullable|integer|min:1',
            'number_of_pax' => 'nullable|integer|min:1|max:100',

            // Customer (optional)
            'customer' => 'nullable|array',
            'customer.name' => 'nullable|string|max:191',
            'customer.phone' => 'nullable|string|max:30',
            'customer.phone_country_code' => 'nullable|string|max:10',
            'customer.email' => 'nullable|email|max:191',
            'customer.address' => 'nullable|string|max:500',

            // Waiter
            'waiter_id' => 'nullable|integer|min:1',

            // Delivery
            'delivery_address' => 'nullable|string|max:500',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_executive_id' => 'nullable|integer|min:1',
            'delivery_time' => 'nullable|date',

            // Discounts
            'discount_type' => 'nullable|string|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_value' => 'nullable|numeric|min:0',

            // Taxes & Charges
            'taxes' => 'nullable|array',
            'taxes.*.id' => 'nullable|integer',
            'taxes.*.amount' => 'nullable|numeric|min:0',
            'extra_charges' => 'nullable|array',
            'extra_charges.*' => 'nullable|integer',

            // Actions
            'actions' => 'nullable|array',
            'actions.*' => 'nullable|string|in:bill,billed,kot,cancel,draft',

            // Notes
            'note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'items' => 'order items',
            'items.*.id' => 'menu item ID',
            'items.*.quantity' => 'item quantity',
            'items.*.price' => 'item price',
            'order_type' => 'order type',
            'customer.name' => 'customer name',
            'customer.phone' => 'customer phone',
            'customer.email' => 'customer email',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required to submit an order.',
            'items.min' => 'At least one item is required to submit an order.',
            'items.*.id.required' => 'Each item must have a valid menu item ID.',
            'discount_type.in' => 'Discount type must be either fixed or percentage.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
