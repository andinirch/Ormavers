<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SimpanRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('id');

        return [
            'name' => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions'   => ['present', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama role wajib diisi',
            'name.string'       => 'Nama role harus berupa teks',
            'name.min'          => 'Nama role minimal 3 karakter',
            'name.max'          => 'Nama role maksimal 50 karakter',
            'name.regex'        => 'Nama role hanya boleh huruf kecil, angka, dan garis bawah',
            'name.unique'       => 'Nama role sudah digunakan',
            'permissions.present' => 'Field permissions wajib ada',
            'permissions.array'   => 'Permissions harus berupa array',
            'permissions.*.string' => 'Setiap permission harus berupa teks',
            'permissions.*.exists' => 'Permission :input tidak ditemukan',
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
