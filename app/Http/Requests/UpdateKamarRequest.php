<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKamarRequest extends FormRequest
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
        return [
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
            'kapasitas.required' => 'Kapasitas harus diisi.',
            'kapasitas.integer' => 'Kapasitas harus berupa angka.',
            'kapasitas.min' => 'Kapasitas minimal 1.',
            'kapasitas.max' => 'Kapasitas maksimal 10.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $kamar = $this->route('kamar');
            if (! $kamar) {
                return;
            }

            $kapasitasBaru = $this->input('kapasitas');
            $penhuniAktif = $kamar->pendaftarans()
                ->penghuniAktif()
                ->count();

            if ($kapasitasBaru < $penhuniAktif) {
                $validator->errors()->add(
                    'kapasitas',
                    "Kapasitas kamar tidak boleh kurang dari {$penhuniAktif} penghuni aktif."
                );
            }
        });

        return $validator;
    }
}
