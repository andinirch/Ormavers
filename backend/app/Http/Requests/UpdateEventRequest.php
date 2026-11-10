<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul'               => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi'           => ['sometimes', 'required', 'string'],
            'tanggal_event'       => ['sometimes', 'required', 'date'],
            'id_kategori'         => ['nullable', 'exists:kategori_event,id'],
            'tag'                 => ['nullable', 'string', 'max:255'],
            'tautan_pendaftaran'  => ['sometimes', 'required', 'url', 'max:500'],
            'periode_mulai'       => ['sometimes', 'required', 'date'],
            'periode_selesai'     => ['sometimes', 'required', 'date'],
            'jangkauan_publikasi' => ['sometimes', 'required', 'in:internal,satu_universitas,multi_universitas'],

            // Poster opsional saat update (jika ingin menambah/mengganti)
            'poster'              => ['nullable', 'array', 'max:3'],
            'poster.*'            => ['file', 'mimes:jpg,jpeg', 'max:2048'],
            'hapus_poster_id'     => ['nullable', 'array'],
            'hapus_poster_id.*'   => ['integer', 'exists:event_poster,id'],
            'poster_utama_id'     => ['nullable', 'integer', 'exists:event_poster,id'],
            'poster_utama_index'  => ['nullable', 'integer', 'min:0', 'max:2'],

            // Tambah ajakan baru
            'organisasi_terlibat'                   => ['nullable', 'array'],
            'organisasi_terlibat.*.id_organisasi'   => ['required', 'integer', 'exists:organisasi,id_organisasi'],
            'organisasi_terlibat.*.jenis'           => ['required', 'in:kolaborator,undangan'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'               => 'Judul event wajib diisi',
            'judul.max'                    => 'Judul event maksimal 255 karakter',
            'deskripsi.required'           => 'Deskripsi event wajib diisi',
            'tanggal_event.required'       => 'Tanggal event wajib diisi',
            'tanggal_event.date'           => 'Format tanggal event tidak valid',
            'id_kategori.exists'           => 'Kategori event tidak valid',
            'tautan_pendaftaran.required'  => 'Tautan pendaftaran wajib diisi',
            'tautan_pendaftaran.url'       => 'Tautan pendaftaran harus berupa URL yang valid',
            'periode_mulai.required'       => 'Periode mulai pendaftaran wajib diisi',
            'periode_selesai.required'     => 'Periode selesai pendaftaran wajib diisi',
            'jangkauan_publikasi.in'       => 'Jangkauan publikasi harus internal, satu_universitas, atau multi_universitas',

            'poster.array'                 => 'Poster harus berupa berkas',
            'poster.max'                   => 'Maksimal unggah 3 poster',
            'poster.*.file'                => 'Poster harus berupa file',
            'poster.*.mimes'               => 'Poster harus berformat JPG atau JPEG',
            'poster.*.max'                 => 'Ukuran poster maksimal 2 MB per file',

            'organisasi_terlibat.*.id_organisasi.required' => 'Organisasi yang dilibatkan wajib dipilih',
            'organisasi_terlibat.*.id_organisasi.exists'   => 'Organisasi yang dilibatkan tidak ditemukan',
            'organisasi_terlibat.*.jenis.required'         => 'Peran organisasi wajib ditentukan',
            'organisasi_terlibat.*.jenis.in'               => 'Peran organisasi harus kolaborator atau undangan',
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
