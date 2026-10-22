<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegistrasiBemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_instansi'            => ['required', 'string', 'max:150'],
            'nama_organisasi'          => ['required', 'string', 'max:150'],
            'nomor_telepon_organisasi' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:20'],
            'nama'                     => ['required', 'string', 'max:150'],
            'nim'                      => ['required', 'string', 'max:30'],
            'nomor_whatsapp'           => ['required', 'string', 'regex:/^08[0-9]{8,13}$/'],
            'username'                 => ['required', 'string', 'max:100', 'unique:pengguna,username'],
            'password'                 => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
            'foto_ktm'                 => ['required', 'file', 'mimes:jpg,jpeg', 'max:1024'],
            'sk_organisasi'            => ['required', 'file', 'mimes:pdf', 'max:2048'],
            'sk_kepengurusan'          => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_instansi.required'            => 'Nama instansi wajib diisi',
            'nama_instansi.string'              => 'Nama instansi harus berupa teks',
            'nama_instansi.max'                 => 'Nama instansi maksimal 150 karakter',
            'nama_organisasi.required'          => 'Nama organisasi wajib diisi',
            'nama_organisasi.string'            => 'Nama organisasi harus berupa teks',
            'nama_organisasi.max'               => 'Nama organisasi maksimal 150 karakter',
            'nomor_telepon_organisasi.required' => 'Nomor telepon organisasi wajib diisi',
            'nomor_telepon_organisasi.string'   => 'Nomor telepon organisasi harus berupa teks',
            'nomor_telepon_organisasi.regex'    => 'Nomor telepon organisasi hanya boleh berisi angka',
            'nomor_telepon_organisasi.max'      => 'Nomor telepon organisasi maksimal 20 karakter',
            'nama.required'                     => 'Nama penanggung jawab wajib diisi',
            'nama.string'                       => 'Nama penanggung jawab harus berupa teks',
            'nama.max'                          => 'Nama penanggung jawab maksimal 150 karakter',
            'nim.required'                      => 'NIM wajib diisi',
            'nim.string'                        => 'NIM harus berupa teks',
            'nim.max'                           => 'NIM maksimal 30 karakter',
            'nomor_whatsapp.required'           => 'Nomor WhatsApp wajib diisi',
            'nomor_whatsapp.string'             => 'Nomor WhatsApp harus berupa teks',
            'nomor_whatsapp.regex'              => 'Nomor WhatsApp harus diawali 08 dan terdiri dari 10-15 digit angka',
            'username.required'                 => 'Username wajib diisi',
            'username.string'                   => 'Username harus berupa teks',
            'username.max'                      => 'Username maksimal 100 karakter',
            'username.unique'                   => 'Username sudah digunakan',
            'password.required'                 => 'Password wajib diisi',
            'password.string'                   => 'Password harus berupa teks',
            'password.min'                      => 'Password minimal 8 karakter',
            'password.regex'                    => 'Password harus mengandung huruf dan angka',
            'foto_ktm.required'                 => 'Foto KTM wajib diunggah',
            'foto_ktm.file'                     => 'Foto KTM harus berupa file',
            'foto_ktm.mimes'                    => 'Foto KTM harus berformat JPG/JPEG atau PNG',
            'foto_ktm.max'                      => 'Foto KTM maksimal 2 MB',
            'sk_organisasi.required'            => 'SK Organisasi wajib diunggah',
            'sk_organisasi.file'                => 'SK Organisasi harus berupa file',
            'sk_organisasi.mimes'               => 'SK Organisasi harus berformat PDF',
            'sk_organisasi.max'                 => 'SK Organisasi maksimal 2 MB',
            'sk_kepengurusan.required'          => 'SK Kepengurusan wajib diunggah',
            'sk_kepengurusan.file'              => 'SK Kepengurusan harus berupa file',
            'sk_kepengurusan.mimes'             => 'SK Kepengurusan harus berformat PDF',
            'sk_kepengurusan.max'               => 'SK Kepengurusan maksimal 2 MB',
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
