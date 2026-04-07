<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for opening a new cash register session.
 *
 * @api POST /api/application-integration/pos/cash-register/sessions/open
 */
class OpenSessionRequest extends FormRequest
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
            'cash_register_id' => 'required|integer|min:1',
            'opening_float' => 'nullable|numeric|min:0|max:999999999.99',
            'note' => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'cash_register_id' => 'cash register',
            'opening_float' => 'opening float amount',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'cash_register_id.required' => 'A cash register must be selected to open a session.',
            'opening_float.numeric' => 'Opening float must be a valid number.',
            'opening_float.min' => 'Opening float cannot be negative.',
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
