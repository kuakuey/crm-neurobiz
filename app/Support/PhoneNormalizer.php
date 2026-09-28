<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function toE164(?string $raw, string $defaultCountry = 'EC'): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return null;
        }

        if ($defaultCountry === 'EC') {
            if (str_starts_with($digits, '593')) {
                return '+'.$digits;
            }

            if (str_starts_with($digits, '0') && strlen($digits) === 10) {
                return '+593'.substr($digits, 1);
            }

            if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
                return '+593'.$digits;
            }
        }

        if (str_starts_with($trimmed, '+')) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }

    public static function normalized(?string $raw): ?string
    {
        $e164 = self::toE164($raw);
        if ($e164 === null) {
            return null;
        }

        return ltrim($e164, '+');
    }
}
