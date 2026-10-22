<?php

namespace App\Http\Requests;

use App\Models\Organisasi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegistrasiAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_organisasi' => ['required', 'integer', 'exists:organisasi,id_organisasi'],
            'nama'          => ['required', 'string', 'max:150'],
            'nim'           => ['required', 'string', 'max:30'],
            'username'      => ['required', 'string', 'max:100', 'unique:pengguna,username'],
            'password'      => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
            'foto_ktm'      => ['required', 'file', 'mimes:jpg,jpeg', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_organisasi.required' => 'Organisasi wajib dipilih',
            'id_organisasi.integer'  => 'ID organisasi harus berupa angka',
            'id_organisasi.exists'   => 'Organisasi tidak ditemukan',
            'nama.required'          => 'Nama lengkap wajib diisi',
            'nama.string'            => 'Nama lengkap harus berupa teks',
            'nama.max'               => 'Nama lengkap maksimal 150 karakter',
            'nim.required'           => 'NIM wajib diisi',
            'nim.string'             => 'NIM harus berupa teks',
            'nim.max'                => 'NIM maksimal 30 karakter',
            'username.required'      => 'Username wajib diisi',
            'username.string'        => 'Username harus berupa teks',
            'username.max'           => 'Username maksimal 100 karakter',
            'username.unique'        => 'Username sudah digunakan',
            'password.required'      => 'Password wajib diisi',
            'password.string'        => 'Password harus berupa teks',
            'password.min'           => 'Password minimal 8 karakter',
            'password.regex'         => 'Password harus mengandung huruf dan angka',
            'foto_ktm.required'      => 'Foto KTM wajib diunggah',
            'foto_ktm.file'          => 'Foto KTM harus berupa file',
            'foto_ktm.mimes'         => 'Foto KTM harus berformat JPG/JPEG',
            'foto_ktm.max'           => 'Foto KTM maksimal 1 MB',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $idOrg = $this->input('id_organisasi');

            if ($idOrg && ! $validator->errors()->has('id_organisasi')) {
                $org = Organisasi::find($idOrg);

                if (! $org || ! in_array($org->jenis_organisasi, ['bem', 'hmj', 'ukm'], true) || $org->status_organisasi !== 'aktif') {
                    $validator->errors()->add('id_organisasi', 'Organisasi tidak ditemukan atau belum aktif');
                }
            }
        });
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
