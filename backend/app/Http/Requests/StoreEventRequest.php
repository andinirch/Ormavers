<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul'               => ['required', 'string', 'max:255'],
            'deskripsi'           => ['required', 'string'],
            'tanggal_event'       => ['required', 'date'],
            'id_kategori'         => ['nullable', 'required_without:tag', 'exists:kategori_event,id'],
            'tag'                 => ['nullable', 'required_without:id_kategori', 'string', 'max:255'],
            'tautan_pendaftaran'  => ['required', 'url', 'max:500'],
            'periode_mulai'       => ['required', 'date'],
            'periode_selesai'     => ['required', 'date', 'after:periode_mulai'],
            'jangkauan_publikasi' => ['required', 'in:internal,satu_universitas,multi_universitas'],

            // Poster: minimal 1, maksimal 3 file, JPG/JPEG, maks 2MB (2048 KB)
            'poster'              => ['required', 'array', 'min:1', 'max:3'],
            'poster.*'            => ['required', 'file', 'mimes:jpg,jpeg', 'max:2048'],
            'poster_utama_index'  => ['nullable', 'integer', 'min:0', 'max:2'],

            // Organisasi yang dilibatkan (opsional)
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
            'id_kategori.required_without' => 'Kategori event wajib dipilih atau isi tag jika memilih Lainnya',
            'id_kategori.exists'           => 'Kategori event tidak valid',
            'tag.required_without'         => 'Tag wajib diisi jika kategori tidak dipilih',
            'tautan_pendaftaran.required'  => 'Tautan pendaftaran wajib diisi',
            'tautan_pendaftaran.url'       => 'Tautan pendaftaran harus berupa URL yang valid',
            'periode_mulai.required'       => 'Periode mulai pendaftaran wajib diisi',
            'periode_mulai.date'           => 'Format periode mulai pendaftaran tidak valid',
            'periode_selesai.required'     => 'Periode selesai pendaftaran wajib diisi',
            'periode_selesai.date'         => 'Format periode selesai pendaftaran tidak valid',
            'periode_selesai.after'        => 'Periode selesai pendaftaran harus setelah periode mulai',
            'jangkauan_publikasi.required' => 'Jangkauan publikasi wajib dipilih',
            'jangkauan_publikasi.in'       => 'Jangkauan publikasi harus internal, satu_universitas, atau multi_universitas',

            'poster.required'              => 'Poster event wajib diunggah',
            'poster.array'                 => 'Poster harus berupa berkas',
            'poster.min'                   => 'Minimal unggah 1 poster',
            'poster.max'                   => 'Maksimal unggah 3 poster',
            'poster.*.required'            => 'File poster wajib diisi',
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
