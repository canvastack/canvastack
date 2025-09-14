<?php

namespace Canvastack\Canvastack\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * FormRequest untuk trait Action di CanvaStack.
 * Mendukung validasi dynamic dari $validations.
 */
class ActionFormRequest extends FormRequest
{
    protected array $customRules = [];

    /**
     * Set custom rules dari trait Action.
     */
    public function setRules(array $rules): void
    {
        $this->customRules = $rules;
    }

    /**
     * Tentukan apakah user diizinkan membuat request ini.
     */
    public function authorize(): bool
    {
        return true; // Asumsikan authorized; bisa di-extend dengan policy
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request.
     * Rules diambil dari trait Action's $validations (dynamic).
     */
    public function rules(): array
    {
        return $this->customRules;
    }

    /**
     * Dapatkan pesan kustom validasi untuk aturan validator.
     */
    public function messages(): array
    {
        return []; // Custom messages bisa ditambahkan
    }

    /**
     * Handle gagal validasi dengan redirect custom.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->back()->withErrors($validator, 'validation')->withInput()
        );
    }
}