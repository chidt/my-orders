<?php

namespace App\Actions\Site;

class ValidateProductPrefix
{
    /**
     * Validate and format product prefix.
     */
    public function handle(?string $prefix): ?string
    {
        if (empty($prefix) || trim($prefix) === '') {
            return null;
        }

        // Convert to uppercase and remove invalid characters
        $cleaned = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($prefix)));

        if (empty($cleaned)) {
            return null;
        }

        // Limit to 5 characters
        return substr($cleaned, 0, 5);
    }

    /**
     * Generate example product code with prefix.
     */
    public function generateExample(?string $prefix): string
    {
        if (empty($prefix)) {
            return '001';
        }

        return $prefix.'001';
    }
}
