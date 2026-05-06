<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    protected $fillable = [
        'tenant_id',
        'device_id',
        'identifier',
        'name',
        'unit',
        'type',
        'thresholds',
    ];

    protected $casts = [
        'thresholds' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
