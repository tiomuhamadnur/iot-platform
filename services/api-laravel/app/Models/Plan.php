<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'device_limit',
        'telemetry_rate_limit',
        'retention_days',
        'price',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
