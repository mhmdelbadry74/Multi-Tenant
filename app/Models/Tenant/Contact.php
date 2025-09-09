<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'notes',
        'created_by',
    ];

    /**
     * Get the user who created this contact
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get deals associated with this contact
     */
    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * Get activities associated with this contact
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
