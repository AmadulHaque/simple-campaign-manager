<?php

namespace App\Services;

use App\Actions\Campaign\CreateCampaignAction;
use App\Actions\Campaign\SendCampaignAction;
use App\Data\CampaignData;
use App\Enums\CampaignRecipientStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use Illuminate\Support\Collection;

class CampaignService
{
    public function __construct(
        private CreateCampaignAction $createCampaignAction,
        private SendCampaignAction $sendCampaignAction
    ) {}

    public function createCampaign(CampaignData $data): Campaign
    {
        return $this->createCampaignAction->execute($data);
    }

    public function updateCampaign(Campaign $campaign, array $data): bool
    {
        return $campaign->update($data);
    }

    public function attachContacts(Campaign $campaign, array $contactIds): void
    {
        $existingContacts = $campaign->recipients()->pluck('contact_id')->toArray();
        $newContacts      = array_diff($contactIds, $existingContacts);

        if (! empty($newContacts)) {
            $recipients = collect($newContacts)->map(fn ($contactId) => [
                'campaign_id' => $campaign->id,
                'contact_id'  => $contactId,
                'status'      => CampaignRecipientStatus::PENDING,
                'created_at'  => now(),
                'updated_at'  => now(),
            ])->toArray();

            CampaignRecipient::insert($recipients);

            // Update campaign totals
            $campaign->total_recipients = $campaign->recipients()->count();
            $campaign->save();
        }
    }

    public function detachContacts(Campaign $campaign, array $contactIds): void
    {
        $campaign->recipients()->whereIn('contact_id', $contactIds)->delete();

        // Update campaign totals
        $campaign->total_recipients = $campaign->recipients()->count();
        $campaign->sent_count       = $campaign->recipients()->where('status', CampaignRecipientStatus::SENT)->count();
        $campaign->failed_count     = $campaign->recipients()->where('status', CampaignRecipientStatus::FAILED)->count();
        $campaign->save();
    }

    public function sendCampaign(Campaign $campaign): void
    {
        $this->sendCampaignAction->execute($campaign);
    }

    public function getCampaignStats(Campaign $campaign): array
    {
        $stats = $campaign->recipients()->selectRaw('
            status,
            COUNT(*) as count
        ')->groupBy('status')->pluck('count', 'status')->toArray();

        return [
            'total'        => $campaign->total_recipients,
            'pending'      => $stats[CampaignRecipientStatus::PENDING->value] ?? 0,
            'sent'         => $stats[CampaignRecipientStatus::SENT->value]    ?? 0,
            'failed'       => $stats[CampaignRecipientStatus::FAILED->value]  ?? 0,
            'opened'       => $stats[CampaignRecipientStatus::OPENED->value]  ?? 0,
            'clicked'      => $stats[CampaignRecipientStatus::CLICKED->value] ?? 0,
            'success_rate' => $campaign->success_rate,
        ];
    }

    public function getAvailableContacts(Campaign $campaign): Collection
    {
        return Contact::active()
            ->whereNotIn('id', $campaign->recipients()->pluck('contact_id'))
            ->get();
    }
}
