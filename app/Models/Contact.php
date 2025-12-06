<?php

namespace App\Models;

use App\ContactStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'status',
    ];

    protected $casts = [
        'status'     => ContactStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_recipients')
            ->withPivot('status', 'sent_at', 'error_message')
            ->withTimestamps();
    }

    public function scopeActive($query): mixed
    {
        return $query->where('status', ContactStatus::ACTIVE);
    }


}
