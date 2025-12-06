<?php

namespace App\Actions\Campaign;

use App\Data\CampaignData;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\DB;

class CreateCampaignAction
{
    public function execute(CampaignData $data): Campaign
    {
        return DB::transaction(function () use ($data): Campaign|null {
            $campaign = Campaign::create([
                'name'             => $data->name,
                'subject'          => $data->subject,
                'body'             => $data->body,
                'status'           => CampaignStatus::DRAFT,
                'scheduled_at'     => $data->scheduledAt,
                'total_recipients' => count($data->contactIds),
            ]);

            // Batch insert recipients for performance
            $recipients = collect($data->contactIds)->map(fn ($contactId) => [
                'campaign_id'   => $campaign->id,
                'contact_id'    => $contactId,
                'status'        => CampaignRecipientStatus::PENDING,
                'created_at'    => now(),
                'updated_at'    => now(),
            ])->toArray();

            // Insert in chunks for very large datasets
            collect($recipients)->chunk(1000)->each(function ($chunk) {
                CampaignRecipient::insert($chunk->toArray());
            });

            return $campaign->fresh();
        });
    }
}
