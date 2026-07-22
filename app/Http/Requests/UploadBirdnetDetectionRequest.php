<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UploadBirdnetDetectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! (bool) \config('birdwatcher.auth_enabled', true)) {
            // Auth disabled — always allow, controller resolves user
            return true;
        }

        return (bool) \auth('api')->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'metadata' => ['required', 'string', 'json', 'max:65535'],
            'audio' => ['nullable', 'file', 'mimes:wav,mp3,mpeg'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $metadataRaw = $this->input('metadata');

            if (!\is_string($metadataRaw) || $metadataRaw === '') {
                return;
            }

            $metadata = \json_decode($metadataRaw, true, 512);

            if (!\is_array($metadata)) {
                $validator->errors()->add('metadata', 'The metadata must be a valid JSON object.');

                return;
            }

            $requiredKeys = ['id', 'scientific_name', 'recorded_at'];

            foreach ($requiredKeys as $key) {
                if (!\array_key_exists($key, $metadata) || $metadata[$key] === null || $metadata[$key] === '') {
                    $validator->errors()->add('metadata', "The metadata is missing the required '{$key}' field.");
                }
            }
        });
    }
}
