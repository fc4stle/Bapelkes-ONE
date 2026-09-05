<?php

namespace App\Http\Requests;

use App\Enums\StatusPelatihan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePelatihanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal_mulai' => ['sometimes', 'required', 'date'],
            'tanggal_selesai' => ['sometimes', 'required', 'date', 'after_or_equal:tanggal_mulai'],
            'lokasi' => ['sometimes', 'required', 'string', 'max:255'],
            'kuota' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['sometimes', 'required', Rule::enum(StatusPelatihan::class)],
        ];
    }
}
