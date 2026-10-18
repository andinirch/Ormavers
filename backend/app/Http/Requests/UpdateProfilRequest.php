<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama'           => ['required', 'string', 'max:150'],
            'nomor_whatsapp' => ['nullable', 'string', 'regex:/^08[0-9]{8,13}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'        => 'Nama wajib diisi',
            'nama.string'          => 'Nama harus berupa teks',
            'nama.max'             => 'Nama maksimal 150 karakter',
            'nomor_whatsapp.regex' => 'Nomor WhatsApp harus diawali 08 dan terdiri dari 10-15 digit angka',
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
