<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'type',
        'subject',
        'description',
        'happened_at',
        'contact_id',
        'deal_id',
        'user_id',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    /**
     * Get the user who created this activity
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contact associated with this activity
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the deal associated with this activity
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Scope for activities by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for activities by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for activities by contact
     */
    public function scopeByContact($query, int $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    /**
     * Scope for activities by deal
     */
    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }
}
