<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'api_key',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function parameters()
    {
        return $this->hasMany(Parameter::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
