<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    protected $fillable = [
        'tenant_id',
        'device_id',
        'command',
        'payload',
        'status',
        'sent_at',
        'ack_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'ack_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
