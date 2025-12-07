<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT         = 'draft';
    case SCHEDULED     = 'scheduled';
    case SENDING       = 'sending';
    case SENT          = 'sent';
    case CANCELLED     = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT            => 'Draft',
            self::SCHEDULED        => 'Scheduled',
            self::SENDING          => 'Sending',
            self::SENT             => 'Sent',
            self::CANCELLED        => 'Cancelled',
        };

    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT     => 'gray',
            self::SCHEDULED => 'blue',
            self::SENDING   => 'yellow',
            self::SENT      => 'green',
            self::CANCELLED => 'red',
        };
    }
}
