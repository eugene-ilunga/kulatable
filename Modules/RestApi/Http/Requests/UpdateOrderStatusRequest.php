<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for updating order status via POS API.
 *
 * @api PUT/PATCH /api/application-integration/pos/orders/{id}/status
 */
class UpdateOrderStatusRequest extends FormRequest
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
            'status' => 'nullable|string|in:draft,placed,confirmed,preparing,ready,served,delivered,billed,paid,canceled,kot',
            'order_status' => 'nullable|string|in:placed,confirmed,preparing,ready,served,delivered,completed,canceled',
            'cancel_reason_id' => 'nullable|integer|min:1',
            'cancel_reason_text' => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status value. Allowed: draft, placed, confirmed, preparing, ready, served, delivered, billed, paid, canceled, kot.',
            'order_status.in' => 'Invalid order_status value. Allowed: placed, confirmed, preparing, ready, served, delivered, completed, canceled.',
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
