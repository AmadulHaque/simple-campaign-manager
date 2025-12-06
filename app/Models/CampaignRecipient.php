<?php

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
protected $fillable = [
        'campaign_id',
        'contact_id',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'status'     => CampaignRecipientStatus::class,
        'sent_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
