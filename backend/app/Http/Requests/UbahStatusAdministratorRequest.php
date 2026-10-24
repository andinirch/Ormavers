<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahStatusAdministratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hak akses tetap dibatasi oleh middleware route superadmin.
        return true;
    }

    public function rules(): array
    {
        return [
            'status_akun' => [
                'required',
                'string',
                Rule::in(['aktif', 'nonaktif']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status_akun.required' => 'Status akun wajib diisi.',
            'status_akun.in' => 'Status akun hanya boleh aktif atau nonaktif.',
        ];
    }
}
