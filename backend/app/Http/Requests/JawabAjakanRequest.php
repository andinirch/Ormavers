<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JawabAjakanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keputusan'        => ['required', 'in:terima,tolak'],
            'alasan_penolakan' => [
                'required_if:keputusan,tolak',
                'nullable',
                'string',
                'min:3',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'keputusan.required'           => 'Keputusan harus ditentukan (terima atau tolak)',
            'keputusan.in'                 => 'Keputusan hanya boleh bernilai "terima" atau "tolak"',
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika Anda menolak ajakan atau undangan',
            'alasan_penolakan.string'      => 'Alasan penolakan harus berupa teks',
            'alasan_penolakan.min'         => 'Alasan penolakan minimal 3 karakter',
        ];
    }
}
