<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBirdnetDetectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) \auth()->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'metadata' => ['required', 'string', 'json'],
            'audio' => ['nullable', 'file', 'mimes:wav,mp3,mpeg'],
        ];
    }
}
