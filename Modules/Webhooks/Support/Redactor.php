<?php

namespace Modules\Webhooks\Support;

class Redactor
{
    /**
     * Recursively redact keys from an array payload.
     */
    public static function apply(array $payload, array $keys = ['password', 'token', 'secret', 'card']): array
    {
        $normalized = array_map('strtolower', $keys);

        $walker = function ($value) use (&$walker, $normalized) {
            if (is_array($value)) {
                $redacted = [];
                foreach ($value as $k => $v) {
                    $key = strtolower((string) $k);
                    if (in_array($key, $normalized, true)) {
                        $redacted[$k] = '[REDACTED]';
                    } else {
                        $redacted[$k] = $walker($v);
                    }
                }
                return $redacted;
            }

            return $value;
        };

        return $walker($payload);
    }
}
