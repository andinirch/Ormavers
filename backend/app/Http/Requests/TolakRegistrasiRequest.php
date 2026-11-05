<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TolakRegistrasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan'               => ['required', 'string', 'max:1000'],
            'dokumen_ditolak'      => ['nullable', 'array'],
            'dokumen_ditolak.*'    => ['string'],
            'rejected_documents'   => ['nullable', 'array'],
            'rejected_documents.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required'           => 'Alasan penolakan wajib diisi',
            'alasan.string'             => 'Alasan penolakan harus berupa teks',
            'alasan.max'                => 'Alasan penolakan maksimal 1000 karakter',
            'dokumen_ditolak.array'     => 'Daftar dokumen ditolak harus berupa array',
            'rejected_documents.array'  => 'Daftar dokumen ditolak harus berupa array',
        ];
    }

    /**
     * Dapatkan daftar dokumen yang ditolak yang dinormalisasi.
     *
     * @return array
     */
    public function getDokumenDitolak(): array
    {
        $raw = $this->input('dokumen_ditolak', $this->input('rejected_documents', []));

        if (! is_array($raw)) {
            return [];
        }

        $validMap = [
            'ktm'             => 'foto_ktm',
            'foto_ktm'        => 'foto_ktm',
            'sk_organisasi'   => 'sk_organisasi',
            'sk-organisasi'   => 'sk_organisasi',
            'sk_kepengurusan' => 'sk_kepengurusan',
            'sk-kepengurusan' => 'sk_kepengurusan',
        ];

        $normalized = [];
        foreach ($raw as $item) {
            $key = strtolower(trim((string) $item));
            if (isset($validMap[$key])) {
                $normalized[] = $validMap[$key];
            }
        }

        return array_values(array_unique($normalized));
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
