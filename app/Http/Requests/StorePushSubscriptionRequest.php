<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'endpoint' => [
                'required',
                'string',
                'url',
                'max:500',
            ],
            'keys' => [
                'required',
                'array',
            ],
            'keys.p256dh' => [
                'required',
                'string',
                'max:255',
            ],
            'keys.auth' => [
                'required',
                'string',
                'max:255',
            ],
            'content_encoding' => [
                'nullable',
                'string',
                'in:aesgcm,aes128gcm',
            ],
        ];
    }
}
