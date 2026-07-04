<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class EncryptedChatMessage implements ValidationRule
{
    private const IV_BYTES = 12;

    private const PAYLOAD_VERSION = 1;

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The message must be a valid encrypted payload.');

            return;
        }

        try {
            $payload = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $fail('The message must be a valid encrypted payload.');

            return;
        }

        if (! is_array($payload)) {
            $fail('The message must be a valid encrypted payload.');

            return;
        }

        if (! isset($payload['v']) || (int) $payload['v'] !== self::PAYLOAD_VERSION) {
            $fail('The message must be a valid encrypted payload.');

            return;
        }

        foreach (['ciphertext', 'iv'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key]) || $payload[$key] === '') {
                $fail('The message must be a valid encrypted payload.');

                return;
            }
        }

        $ciphertext = base64_decode($payload['ciphertext'], true);
        $iv = base64_decode($payload['iv'], true);

        if (
            $ciphertext === false
            || $iv === false
            || $ciphertext === ''
            || strlen($iv) !== self::IV_BYTES
        ) {
            $fail('The message must be a valid encrypted payload.');

            return;
        }
    }
}
