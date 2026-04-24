<?php

namespace App\Services;

class Payment
{
    public static function payWithVisa(string $cardNumber, string $cvcCode): bool
    {
        if ($cvcCode === '123' || strlen($cardNumber) !== 16) {
            return false;
        }
        return true;
    }

    public static function payWithPayPal(string $emailAddress): bool
    {
        if (str_contains($emailAddress, 'fail')) {
            return false;
        }
        return true;
    }

    public static function payWithMBway(string $phoneNumber): bool
    {
        if (!str_starts_with($phoneNumber, '9') || strlen($phoneNumber) !== 9) {
            return false;
        }
        return true;
    }
}