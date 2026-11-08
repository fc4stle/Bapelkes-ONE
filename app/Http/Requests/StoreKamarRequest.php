<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKamarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->isStaf() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $asramaId = $this->route('asrama')?->id;

        return [
            'nomor_kamar' => [
                'required',
                'string',
                'max:50',
                "unique:kamars,nomor_kamar,NULL,id,asrama_id,$asramaId",
            ],
            'kapasitas' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nomor_kamar.required' => 'Nomor kamar harus diisi.',
            'nomor_kamar.unique' => 'Nomor kamar sudah ada di asrama ini.',
            'nomor_kamar.max' => 'Nomor kamar maksimal 50 karakter.',
            'kapasitas.required' => 'Kapasitas harus diisi.',
            'kapasitas.integer' => 'Kapasitas harus berupa angka.',
            'kapasitas.min' => 'Kapasitas minimal 1.',
            'kapasitas.max' => 'Kapasitas maksimal 10.',
        ];
    }
}
