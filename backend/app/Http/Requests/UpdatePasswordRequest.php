<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_lama'       => ['required', 'string'],
            'password_baru'       => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
            'konfirmasi_password' => ['required', 'string', 'same:password_baru'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_lama.required'       => 'Password lama wajib diisi',
            'password_baru.required'       => 'Password baru wajib diisi',
            'password_baru.min'            => 'Password baru minimal 8 karakter',
            'password_baru.regex'          => 'Password baru harus mengandung huruf dan angka',
            'konfirmasi_password.required' => 'Konfirmasi password wajib diisi',
            'konfirmasi_password.same'     => 'Konfirmasi password tidak cocok dengan password baru',
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
