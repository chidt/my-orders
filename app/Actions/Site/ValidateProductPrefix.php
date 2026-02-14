<?php

namespace App\Actions\Site;

use App\Contracts\ActionContract;

class ValidateProductPrefix implements ActionContract
{
    /**
     * Validate and format product prefix.
     */
    public function handle(mixed ...$parameters): mixed
    {
        $prefix = $parameters[0] ?? null;

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
     * Validate and format product prefix (helper method with original signature).
     */
    public function validatePrefix(?string $prefix): ?string
    {
        return $this->handle($prefix);
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
