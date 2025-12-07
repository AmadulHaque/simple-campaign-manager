<?php

namespace App\Actions\Campaign;

use App\Events\CampaignSendingStarted;
use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;

class SendCampaignAction
{
    public function execute(Campaign $campaign): void
    {
        if ($campaign->status !== 'draft') {
            throw new \InvalidArgumentException('Campaign can only be sent from draft status');
        }

        $campaign->update(['status' => 'sending']);

        event(new CampaignSendingStarted($campaign));

        foreach ($campaign->contacts as $contact) {
            SendCampaignEmail::dispatch($campaign, $contact);
        }

        $campaign->update(['sent_at' => now(), 'status' => 'sent']);
    }
}
