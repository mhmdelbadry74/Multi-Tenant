<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'title',
        'amount',
        'status',
        'closed_at',
        'contact_id',
        'assigned_to',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the contact associated with this deal
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the user assigned to this deal
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get activities associated with this deal
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Check if deal is won
     */
    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    /**
     * Check if deal is lost
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Check if deal is open
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Mark deal as won
     */
    public function markAsWon(): void
    {
        $this->update([
            'status' => 'won',
            'closed_at' => now(),
        ]);
    }

    /**
     * Mark deal as lost
     */
    public function markAsLost(): void
    {
        $this->update([
            'status' => 'lost',
            'closed_at' => now(),
        ]);
    }
}
