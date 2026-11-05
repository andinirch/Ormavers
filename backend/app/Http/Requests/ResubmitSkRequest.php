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
            'foto_ktm'        => ['nullable', 'image', 'mimes:jpg,jpeg', 'max:1024'],
            'sk_organisasi'   => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
            'sk_kepengurusan' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }

    /**
     * After-hook: pastikan minimal satu file diunggah.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('foto_ktm') && ! $this->hasFile('sk_organisasi') && ! $this->hasFile('sk_kepengurusan')) {
                $validator->errors()->add('dokumen', 'Minimal salah satu dokumen harus diunggah');
            }
        });
    }

    public function messages(): array
    {
        return [
            'foto_ktm.image'               => 'Foto KTM harus berupa gambar',
            'foto_ktm.mimes'               => 'Foto KTM harus berformat JPG/JPEG',
            'foto_ktm.max'                 => 'Foto KTM maksimal 1 MB',
            'sk_organisasi.file'           => 'SK Organisasi harus berupa file',
            'sk_organisasi.mimes'          => 'SK Organisasi harus berformat PDF',
            'sk_organisasi.max'            => 'SK Organisasi maksimal 2 MB',
            'sk_kepengurusan.file'         => 'SK Kepengurusan harus berupa file',
            'sk_kepengurusan.mimes'        => 'SK Kepengurusan harus berformat PDF',
            'sk_kepengurusan.max'          => 'SK Kepengurusan maksimal 2 MB',
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
