<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePortofolioEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'judul'     => ['nullable', 'string', 'max:200'],
            'tahun'     => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'deskripsi' => ['nullable', 'string'],
        ];

        if ($this->hasFile('dokumentasi')) {
            $rules['dokumentasi'] = ['required', 'file', 'max:5120'];
        } else {
            $rules['dokumentasi'] = ['required', 'string', 'max:500'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'judul.string'         => 'Judul portofolio harus berupa teks',
            'judul.max'            => 'Judul portofolio maksimal 200 karakter',
            'tahun.integer'        => 'Tahun harus berupa angka',
            'tahun.min'            => 'Tahun minimal 2000',
            'tahun.max'            => 'Tahun maksimal 2100',
            'deskripsi.string'     => 'Deskripsi portofolio harus berupa teks',
            'dokumentasi.required' => 'Dokumentasi portofolio wajib diisi',
            'dokumentasi.file'     => 'Dokumentasi harus berupa file yang valid',
            'dokumentasi.max'      => 'Ukuran file dokumentasi maksimal 5MB',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
