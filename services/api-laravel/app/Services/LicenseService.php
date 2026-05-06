<?php

namespace App\Services;

class LicenseService
{
    public function isValid()
    {
        $key = config('app.license_key');
        if (!$key) return false;

        // Simplified validation logic
        return str_starts_with($key, 'IOT-PRO-');
    }

    public function getStatus()
    {
        if ($this->isValid()) {
            return 'valid';
        }
        return 'invalid';
    }
}
