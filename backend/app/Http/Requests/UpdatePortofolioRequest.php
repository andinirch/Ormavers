<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePortofolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'judul'     => ['required', 'string', 'max:200'],
            'tahun'     => ['required', 'integer', 'min:2000', 'max:2100'],
            'deskripsi' => ['required', 'string'],
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
            'judul.required'       => 'Judul portofolio wajib diisi',
            'judul.string'         => 'Judul portofolio harus berupa teks',
            'judul.max'            => 'Judul portofolio maksimal 200 karakter',
            'tahun.required'       => 'Tahun portofolio wajib diisi',
            'tahun.integer'        => 'Tahun harus berupa angka',
            'tahun.min'            => 'Tahun minimal 2000',
            'tahun.max'            => 'Tahun maksimal 2100',
            'deskripsi.required'   => 'Deskripsi portofolio wajib diisi',
            'deskripsi.string'     => 'Deskripsi portofolio harus berupa teks',
            'dokumentasi.required' => 'Dokumentasi portofolio wajib diisi',
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
