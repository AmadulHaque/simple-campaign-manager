<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT      = 'draft';
    case SCHEDULED  = 'scheduled';
    case SENDING    = 'sending';
    case SEND       = 'sent';
    case FAILED     = 'failed';

    // make label method
    public function label(): string
    {
        return match ($this) {
            self::DRAFT         => 'Draft',
            self::SCHEDULED     => "Scheduled",
            self::SENDING       => "Sending",
            self::SEND          => "Sent",
            self::FAILED        => "Failed",
        };

    }
}
