<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KirimAjakanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organisasi_ids'   => ['required', 'array', 'min:1'],
            'organisasi_ids.*' => ['required', 'integer', 'exists:organisasi,id_organisasi'],
        ];
    }

    public function messages(): array
    {
        return [
            'organisasi_ids.required'   => 'Pilih minimal satu organisasi tujuan',
            'organisasi_ids.array'      => 'Daftar organisasi tujuan harus berupa daftar/array',
            'organisasi_ids.min'        => 'Pilih minimal satu organisasi tujuan',
            'organisasi_ids.*.required' => 'ID organisasi tujuan tidak boleh kosong',
            'organisasi_ids.*.integer'  => 'ID organisasi tujuan harus berupa bilangan bulat',
            'organisasi_ids.*.exists'   => 'Organisasi tujuan tidak ditemukan dalam sistem',
        ];
    }
}
