<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $connection = 'mysql'; // System database connection

    protected $fillable = [
        'name',
        'slug',
        'db_name',
        'db_user',
        'db_pass',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Scope for active tenants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for suspended tenants
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }


    public function setPasswordAttribute($value){

        return $this->attributes['db_pass'] = bcrypt($value);
    }
}
