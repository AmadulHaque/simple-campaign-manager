<?php

namespace App\Data;

class CampaignData
{
    public function __construct(
        public string  $name,
        public string  $subject,
        public string  $body,
        public array   $contactIds,
        public ?string $scheduledAt = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name:        $data['name'],
            subject:     $data['subject'],
            body:        $data['body'],
            contactIds:  $data['contact_ids'] ?? [],
            scheduledAt: $data['scheduled_at'] ?? null,
        );
    }
}