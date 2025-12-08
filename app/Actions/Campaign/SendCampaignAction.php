<?php

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Events\CampaignSendingStarted;
use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;

class SendCampaignAction
{
    public function execute(Campaign $campaign): void
    {
        if ($campaign->status !== CampaignStatus::DRAFT) {
            throw new \InvalidArgumentException('Campaign can only be sent from draft status');
        }

        // Update campaign status to sending
        $campaign->update(['status' => CampaignStatus::SENDING]);

        event(new CampaignSendingStarted($campaign));

        // Dispatch jobs for each recipient
        $campaign->recipients()->chunk(100, function ($recipients) use ($campaign) {
            foreach ($recipients as $recipient) {
                SendCampaignEmail::dispatch($campaign, $recipient->contact);
            }
        });

       
    }
}
