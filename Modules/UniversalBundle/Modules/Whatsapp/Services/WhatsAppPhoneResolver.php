<?php

namespace Modules\Whatsapp\Services;

class WhatsAppPhoneResolver
{
    protected static function value(?object $entity, string $key): mixed
    {
        if (!$entity) {
            return null;
        }

        if (is_array($entity) && array_key_exists($key, $entity)) {
            return $entity[$key];
        }

        if ($entity instanceof \Illuminate\Database\Eloquent\Model) {
            return $entity->getAttribute($key);
        }

        return data_get($entity, $key);
    }

    /**
     * Build a WhatsApp-compatible phone number (digits only, with country code).
     */
    public static function format(?string $phoneCode, ?string $phoneNumber): ?string
    {
        $number = trim((string) ($phoneNumber ?? ''));
        if ($number === '') {
            return null;
        }

        $code = trim((string) ($phoneCode ?? ''));
        $combined = $code !== '' ? ($code . $number) : $number;
        $digits = preg_replace('/[^0-9]/', '', $combined);

        return $digits !== '' ? $digits : null;
    }

    /**
     * Resolve phone number from a User-like entity.
     */
    public static function fromUser(?object $user): ?string
    {
        if (!$user) {
            return null;
        }

        return self::format(
            self::value($user, 'phone_code'),
            self::value($user, 'phone_number')
        );
    }

    /**
     * Resolve phone number from a Customer-like entity.
     */
    public static function fromCustomer(?object $customer): ?string
    {
        if (!$customer) {
            return null;
        }

        return self::format(
            self::value($customer, 'phone_code'),
            self::value($customer, 'phone')
        );
    }

    /**
     * Resolve phone number from a DeliveryExecutive-like entity.
     */
    public static function fromDeliveryExecutive(?object $executive): ?string
    {
        if (!$executive) {
            return null;
        }

        // Primary fields in current schema: phone_code + phone
        $resolved = self::format(
            self::value($executive, 'phone_code'),
            self::value($executive, 'phone')
        );

        if ($resolved) {
            return $resolved;
        }

        // Legacy fallback if custom installs still carry these fields.
        return self::format(
            self::value($executive, 'phone_code'),
            self::value($executive, 'mobile')
        );
    }
}
