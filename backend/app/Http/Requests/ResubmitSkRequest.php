<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResubmitSkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sk_organisasi'   => ['nullable', 'file', 'mimes:pdf', 'max:2048', 'required_without:sk_kepengurusan'],
            'sk_kepengurusan' => ['nullable', 'file', 'mimes:pdf', 'max:2048', 'required_without:sk_organisasi'],
        ];
    }

    public function messages(): array
    {
        return [
            'sk_organisasi.required_without'   => 'Minimal salah satu dokumen SK harus diunggah',
            'sk_organisasi.file'               => 'SK Organisasi harus berupa file',
            'sk_organisasi.mimes'              => 'SK Organisasi harus berformat PDF',
            'sk_organisasi.max'                => 'SK Organisasi maksimal 2 MB',
            'sk_kepengurusan.required_without' => 'Minimal salah satu dokumen SK harus diunggah',
            'sk_kepengurusan.file'             => 'SK Kepengurusan harus berupa file',
            'sk_kepengurusan.mimes'            => 'SK Kepengurusan harus berformat PDF',
            'sk_kepengurusan.max'              => 'SK Kepengurusan maksimal 2 MB',
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
