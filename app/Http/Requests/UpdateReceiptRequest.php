<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'currency' => ['required', 'string', 'max:3'],
            'total' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'file_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
