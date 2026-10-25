<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDeskripsiOrganisasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deskripsi' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'deskripsi.required' => 'Deskripsi wajib diisi',
            'deskripsi.string'   => 'Deskripsi harus berupa teks',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Data tidak valid',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
