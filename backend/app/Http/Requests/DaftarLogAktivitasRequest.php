<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DaftarLogAktivitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pengguna' => ['nullable', 'integer'],
            'jenis'       => ['nullable', 'string', 'max:50'],
            'dari'        => ['nullable', 'date_format:Y-m-d'],
            'sampai'      => ['nullable', 'date_format:Y-m-d'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'dari.date_format'   => 'Format tanggal dari harus YYYY-MM-DD',
            'sampai.date_format' => 'Format tanggal sampai harus YYYY-MM-DD',
            'per_page.max'       => 'Jumlah per halaman maksimal 50',
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
