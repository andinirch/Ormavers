<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TambahAdministratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama'     => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', 'unique:pengguna,username'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'     => 'Nama wajib diisi',
            'nama.string'       => 'Nama harus berupa teks',
            'nama.max'          => 'Nama maksimal 150 karakter',
            'username.required' => 'Username wajib diisi',
            'username.string'   => 'Username harus berupa teks',
            'username.max'      => 'Username maksimal 100 karakter',
            'username.unique'   => 'Username sudah digunakan',
            'password.required' => 'Password wajib diisi',
            'password.string'   => 'Password harus berupa teks',
            'password.min'      => 'Password minimal 8 karakter',
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
