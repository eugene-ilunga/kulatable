<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for adding tip to an order via POS API.
 *
 * @api POST /api/application-integration/pos/orders/{id}/tip
 */
class AddTipRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0|max:99999.99',
            'note' => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'amount' => 'tip amount',
            'note' => 'tip note',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Tip amount is required.',
            'amount.numeric' => 'Tip amount must be a valid number.',
            'amount.min' => 'Tip amount cannot be negative.',
            'amount.max' => 'Tip amount is too large.',
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
