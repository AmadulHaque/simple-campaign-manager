<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Support\Collection;

class CampaignService
{
    public function createCampaign(array $data): Campaign
    {
        return Campaign::create($data);
    }

    public function updateCampaign(Campaign $campaign, array $data): bool
    {
        return $campaign->update($data);
    }

    public function attachContacts(Campaign $campaign, array $contactIds): void
    {
        //
    }

    public function detachContacts(Campaign $campaign, array $contactIds): void
    {
        //
    }

    public function sendCampaign(Campaign $campaign): void
    {
       //
    }

    public function getCampaignStats(Campaign $campaign): array
    {
       //
    }

    public function getAvailableContacts(Campaign $campaign): Collection
    {
        return Contact::active()
            ->subscribed()
            ->whereNotIn('id', $campaign->contacts()->pluck('contacts.id'))
            ->get();
    }
}
