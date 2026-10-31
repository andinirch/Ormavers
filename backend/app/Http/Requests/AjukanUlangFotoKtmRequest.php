<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjukanUlangFotoKtmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto_ktm' => 'required|image|mimes:jpg,jpeg|max:1024',
        ];
    }
}