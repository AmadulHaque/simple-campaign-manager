<?php
namespace App\Services;

use App\Models\Contact;
use App\Models\Campaign;
use Illuminate\Support\Collection;
use App\Repositories\CampaignRepository;
use App\Actions\Campaign\SendCampaignAction;

class CampaignService
{
    public function __construct(
        private CampaignRepository $repository,
        private SendCampaignAction $sendCampaignAction
    ) {}

    public function createCampaign(array $data): Campaign
    {
        return $this->repository->create($data);
    }

    public function updateCampaign(Campaign $campaign, array $data): Campaign
    {
        return $this->repository->update($campaign, $data);
    }

    public function attachContacts(Campaign $campaign, array $contactIds): void
    {
        $this->repository->attachContacts($campaign, $contactIds);
    }

    public function detachContacts(Campaign $campaign, array $contactIds): void
    {
        $this->repository->detachContacts($campaign, $contactIds);
    }

    public function sendCampaign(Campaign $campaign): void
    {
        $this->sendCampaignAction->execute($campaign);
    }

    public function getCampaignStats(Campaign $campaign): array
    {
        return $this->repository->getStats($campaign);
    }

    public function getAvailableContacts(Campaign $campaign): Collection
    {
        return Contact::active()
            ->subscribed()
            ->whereNotIn('id', $campaign->contacts()->pluck('contacts.id'))
            ->get();
    }
}