<?php

namespace App;

enum ContactStatus: int
{
    case ACTIVE         = 1;
    case UNSUBSCRIBED   = 2;
    case BOUNCED        = 3;
    case UNKNOWN        = 4;

    public function label(): string
    {
        return match($this){
            self::ACTIVE        => "Active",
            self::UNSUBSCRIBED  => "Unsubscribed",
            self::BOUNCED       => "Bounced",
            self::UNKNOWN       => "Unknown",
        };
    }


}
