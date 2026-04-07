<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for processing order payments via POS API.
 *
 * @api POST /api/application-integration/pos/orders/{id}/pay
 */
class PayOrderRequest extends FormRequest
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
            // Payment details
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,credit_card,debit_card,online,wallet,split',
            'payment_gateway' => 'nullable|string|max:50',
            
            // Transaction reference
            'transaction_id' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            
            // Split payment
            'split_payment' => 'nullable|array',
            'split_payment.*.method' => 'nullable|string|in:cash,credit_card,debit_card,online,wallet',
            'split_payment.*.amount' => 'nullable|numeric|min:0',
            
            // Tip (if included with payment)
            'tip_amount' => 'nullable|numeric|min:0|max:99999.99',
            'tip_note' => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'amount_paid.numeric' => 'Payment amount must be a valid number.',
            'amount_paid.min' => 'Payment amount cannot be negative.',
            'payment_method.in' => 'Invalid payment method. Allowed: cash, credit_card, debit_card, online, wallet, split.',
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
