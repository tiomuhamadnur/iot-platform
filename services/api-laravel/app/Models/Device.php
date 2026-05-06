<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'tenant_id',
        'device_id',
        'name',
        'secret',
        'certificate',
        'hardware_binding',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parameters()
    {
        return $this->hasMany(Parameter::class);
    }

    public function commands()
    {
        return $this->hasMany(Command::class);
    }
}
