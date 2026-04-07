<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for updating order details (table, customer, waiter, etc.) via POS API.
 *
 * @api PUT /api/application-integration/pos/orders/{id}
 */
class UpdateOrderRequest extends FormRequest
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
            // Table assignment (dine-in only)
            'table_id' => 'nullable|integer|min:0',

            // Customer assignment
            'customer_id' => 'nullable|integer|min:0',

            // Waiter assignment
            'waiter_id' => 'nullable|integer|min:0',

            // Items update
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|integer',
            'items.*.menu_item_id' => 'nullable|integer',
            'items.*.quantity' => 'nullable|numeric|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.note' => 'nullable|string|max:500',

            // Status and actions
            'status' => 'nullable|string|in:draft,placed,confirmed,preparing,ready,served,delivered,billed,paid,canceled,kot',
            'order_status' => 'nullable|string|in:placed,confirmed,preparing,ready,served,delivered,completed,canceled',
            'actions' => 'nullable|array',
            'actions.*' => 'nullable|string|in:cancel,bill,billed,kot,draft',

            // Discount
            'discount_type' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_value' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'table_id.integer' => 'Table ID must be an integer.',
            'table_id.min' => 'Table ID must be 0 or greater.',
            'table_id.dine_in_only' => 'Table assignment is only allowed for dine-in orders.',
            'customer_id.integer' => 'Customer ID must be an integer.',
            'customer_id.min' => 'Customer ID must be 0 or greater.',
            'waiter_id.integer' => 'Waiter ID must be an integer.',
            'waiter_id.min' => 'Waiter ID must be 0 or greater.',
            'items.array' => 'Items must be an array.',
            'items.*.quantity.numeric' => 'Item quantity must be a number.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
            'items.*.price.numeric' => 'Item price must be a number.',
            'status.in' => 'Invalid status value. Allowed: draft, placed, confirmed, preparing, ready, served, delivered, billed, paid, canceled, kot.',
            'order_status.in' => 'Invalid order_status value. Allowed: placed, confirmed, preparing, ready, served, delivered, completed, canceled.',
            'actions.*.in' => 'Invalid action. Allowed: cancel, bill, billed, kot, draft.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Only validate table_id for dine-in orders
            if ($this->has('table_id') && $this->get('table_id') !== null) {
                $orderId = $this->route('id');
                $order = \App\Models\Order::find($orderId);

                if ($order) {
                    $orderType = $order->order_type ?? 'dine_in';
                    $normalizedType = strtolower(str_replace([' ', '-'], '_', $orderType));

                    // Only dine_in and dinein orders can have table assignments
                    if ($normalizedType !== 'dine_in' && $normalizedType !== 'dinein') {
                        $validator->errors()->add('table_id', $this->messages()['table_id.dine_in_only']);
                    }
                }
            }
        });

        return $validator;
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
