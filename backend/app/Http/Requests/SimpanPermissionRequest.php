<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SimpanPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:3', 'max:100',
                'regex:/^[a-z0-9_\/ -]+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($this->route('id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama permission wajib diisi',
            'name.string' => 'Nama permission harus berupa teks',
            'name.min' => 'Nama permission minimal 3 karakter',
            'name.max' => 'Nama permission maksimal 100 karakter',
            'name.regex' => 'Nama permission hanya boleh berisi huruf kecil, angka, spasi, garis bawah, garis miring, dan tanda hubung',
            'name.unique' => 'Nama permission sudah digunakan',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Data tidak valid',
            'errors' => $validator->errors(),
        ], 422));
    }
}
