<?php

namespace App\Enums;

enum CampaignRecipientStatus :int
{
    case PENDING = 1;
    case SENT = 2;
    case FAILED = 3;

    public function label(): string
    {
        return match($this){
            self::PENDING   => "Pending",
            self::SENT      => "Sent",
            self::FAILED    => "Failed",
        };
    }



}
