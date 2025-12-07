<?php

namespace App\Data;

use App\Enums\ContactStatus;

class ContactData
{
    public function __construct(
        public string $name,
        public string $email,
        public int $status = ContactStatus::ACTIVE->value,
    ) {}
}
