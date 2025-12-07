<?php
namespace App\Data;

use InvalidArgumentException;

class EmailAddress
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$value}");
        }
    }

    public function domain(): string
    {
        return substr(strrchr($this->value, "@"), 1);
    }

    public function localPart(): string
    {
        return strstr($this->value, '@', true);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}