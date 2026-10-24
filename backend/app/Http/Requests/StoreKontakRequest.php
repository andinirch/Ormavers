<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreKontakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis'  => ['required', 'string', 'in:wa,instagram,email,tiktok'],
            'tautan' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.required'  => 'Jenis kontak wajib dipilih',
            'jenis.string'    => 'Jenis kontak harus berupa teks',
            'jenis.in'        => 'Jenis kontak harus salah satu dari: wa, instagram, email, tiktok',
            'tautan.required' => 'Tautan atau nilai kontak wajib diisi',
            'tautan.string'   => 'Tautan atau nilai kontak harus berupa teks',
            'tautan.max'      => 'Tautan atau nilai kontak maksimal 255 karakter',
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
