<?php

namespace App\Enums;

enum CampaignRecipientStatus: int
{
    case PENDING = 1;
    case SENT    = 2;
    case FAILED  = 3;
    case OPENED  = 4;
    case CLICKED = 5;

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::SENT      => 'Sent',
            self::FAILED    => 'Failed',
            self::OPENED    => 'Opened',
            self::CLICKED   => 'Clicked',
        };
    }
}
