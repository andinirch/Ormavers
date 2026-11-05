<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IdentitasOrganisasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo'                => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'logo_universitas'    => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'id_bidang'           => ['nullable'],
            'bidang_organisasi'   => ['nullable', 'string', 'max:100'],
            'bidang_kustom'       => ['nullable', 'string', 'max:100'],
            'is_custom_bidang'    => ['nullable'],
            'alamat'              => ['required', 'string'],
            'tanggal_mulai_sk'    => ['required', 'date'],
            'tanggal_berakhir_sk' => ['required', 'date', 'after_or_equal:tanggal_mulai_sk'],
        ];
    }

    /**
     * Apakah permintaan menggunakan opsi bidang kustom "Lainnya".
     */
    public function isCustomBidang(): bool
    {
        $idBidang = $this->input('id_bidang');
        $isCustom = filter_var($this->input('is_custom_bidang'), FILTER_VALIDATE_BOOLEAN);

        return $isCustom
            || strtolower(trim((string) $idBidang)) === 'lainnya'
            || (! empty($this->input('bidang_organisasi')) && (empty($idBidang) || ! is_numeric($idBidang) || strtolower((string) $idBidang) === 'lainnya'))
            || (! empty($this->input('bidang_kustom')) && (empty($idBidang) || ! is_numeric($idBidang) || strtolower((string) $idBidang) === 'lainnya'));
    }

    /**
     * Dapatkan teks bidang kustom.
     */
    public function getBidangKustom(): ?string
    {
        $val = $this->input('bidang_organisasi', $this->input('bidang_kustom'));
        return $val !== null ? trim((string) $val) : null;
    }

    /**
     * Validasi kustom untuk bidang organisasi.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->isCustomBidang()) {
                $customText = $this->getBidangKustom();
                if (empty($customText)) {
                    $validator->errors()->add('bidang_organisasi', 'Nama bidang organisasi kustom wajib diisi.');
                }
            } else {
                $idBidang = $this->input('id_bidang');
                if ($idBidang === null || $idBidang === '') {
                    $validator->errors()->add('id_bidang', 'Bidang organisasi wajib dipilih.');
                    return;
                }

                if (! is_numeric($idBidang) || ! \App\Models\BidangOrganisasi::where('id_bidang', (int) $idBidang)->exists()) {
                    $validator->errors()->add('id_bidang', 'Bidang organisasi yang dipilih tidak valid.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'logo.image'                         => 'File logo harus berupa gambar.',
            'logo.mimes'                         => 'Format file logo harus jpeg, png, atau jpg.',
            'logo.max'                           => 'Ukuran file logo maksimal 2048 KB (2 MB).',
            'logo_universitas.image'             => 'File logo universitas harus berupa gambar.',
            'logo_universitas.mimes'             => 'Format file logo universitas harus jpeg, png, atau jpg.',
            'logo_universitas.max'               => 'Ukuran file logo universitas maksimal 2048 KB (2 MB).',
            'id_bidang.required'                 => 'Bidang organisasi wajib dipilih.',
            'id_bidang.exists'                   => 'Bidang organisasi yang dipilih tidak valid.',
            'bidang_organisasi.max'              => 'Nama bidang organisasi maksimal 100 karakter.',
            'bidang_kustom.max'                  => 'Nama bidang organisasi maksimal 100 karakter.',
            'alamat.required'                    => 'Alamat organisasi wajib diisi.',
            'alamat.string'                      => 'Alamat organisasi harus berupa teks.',
            'tanggal_mulai_sk.required'          => 'Tanggal mulai SK wajib diisi.',
            'tanggal_mulai_sk.date'              => 'Format tanggal mulai SK tidak valid.',
            'tanggal_berakhir_sk.required'       => 'Tanggal berakhir SK wajib diisi.',
            'tanggal_berakhir_sk.date'           => 'Format tanggal berakhir SK tidak valid.',
            'tanggal_berakhir_sk.after_or_equal' => 'Tanggal berakhir SK harus sama dengan atau setelah tanggal mulai SK.',
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
