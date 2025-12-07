<?php

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'status'        => CampaignStatus::class,
        'scheduled_at'  => 'datetime',
        'sent_at'       => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'campaign_recipients')
            ->withPivot('status', 'sent_at', 'error_message')
            ->withTimestamps();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function getPendingRecipientsAttribute(): int
    {
        return $this->recipients()->where('status', CampaignRecipientStatus::PENDING)->count();
    }

    public function getSuccessRateAttribute(): float|int
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    public function getStatsAttribute()
    {
        return [
            'total'   => $this->contacts()->count(),
            'pending' => $this->contacts()->wherePivot('status', CampaignRecipientStatus::PENDING)->count(),
            'sent'    => $this->contacts()->wherePivot('status', CampaignRecipientStatus::SENT)->count(),
            'failed'  => $this->contacts()->wherePivot('status', CampaignRecipientStatus::FAILED)->count(),
            'opened'  => $this->contacts()->wherePivot('status', CampaignRecipientStatus::OPENED)->count(),
            'clicked' => $this->contacts()->wherePivot('status', CampaignRecipientStatus::CLICKED)->count(),
        ];
    }

    public function scopeDraft($query)
    {
        return $query->where('status', CampaignStatus::DRAFT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', CampaignStatus::SENT);
    }
}
