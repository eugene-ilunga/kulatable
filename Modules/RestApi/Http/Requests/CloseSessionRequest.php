<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for closing a cash register session.
 *
 * @api POST /api/application-integration/pos/cash-register/sessions/{id}/close
 */
class CloseSessionRequest extends FormRequest
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
            'expected_cash' => 'nullable|numeric|min:0|max:999999999.99',
            'counted_cash' => 'required|numeric|min:0|max:999999999.99',
            'closing_note' => 'nullable|string|max:1000',
            'denomination_counts' => 'nullable|array',
            'denomination_counts.*.denomination_id' => 'required_with:denomination_counts|integer|min:1',
            'denomination_counts.*.count' => 'required_with:denomination_counts|integer|min:0',
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'expected_cash' => 'expected cash amount',
            'counted_cash' => 'counted cash amount',
            'denomination_counts' => 'denomination counts',
            'denomination_counts.*.denomination_id' => 'denomination ID',
            'denomination_counts.*.count' => 'denomination count',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'counted_cash.required' => 'The counted cash amount is required to close the session.',
            'counted_cash.numeric' => 'Counted cash must be a valid number.',
            'counted_cash.min' => 'Counted cash cannot be negative.',
            'denomination_counts.*.denomination_id.required_with' => 'Each denomination count must have a denomination ID.',
            'denomination_counts.*.count.required_with' => 'Each denomination must have a count value.',
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
