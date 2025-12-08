<?php

// app/Http/Controllers/CampaignController.php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Campaign;
use App\Data\CampaignData;
use Illuminate\Http\Request;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\CampaignRequest;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $service
    ) {}

    public function index(Request $request)
    {
        $campaigns = Campaign::with(['recipients.contact'])
            ->latest()
            ->paginate(10);

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create()
    {
        $contacts = $this->service->getAvailableContacts(new Campaign());

        return Inertia::render('campaigns/create', [
            'contacts' => $contacts,
        ]);
    }

    public function store(CampaignRequest $request)
    {
        $campaignData = CampaignData::fromRequest($request->validated());
        $campaign = $this->service->createCampaign($campaignData);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['recipients.contact']);
        $availableContacts = $this->service->getAvailableContacts($campaign);
        $stats = $this->service->getCampaignStats($campaign);

        return Inertia::render('campaigns/show', [
            'campaign' => $campaign,
            'availableContacts' => $availableContacts,
            'stats' => $stats,
        ]);
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        $this->service->sendCampaign($campaign);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign is being sent.');
    }

    public function updateContacts(Request $request, Campaign $campaign): RedirectResponse
    {
        $request->validate([
            'contacts' => 'required|array',
            'contacts.*' => 'exists:contacts,id',
            'action' => 'required|in:attach,detach',
        ]);

        if ($request->action === 'attach') {
            $this->service->attachContacts($campaign, $request->contacts);
            $message = 'Contacts added to campaign.';
        } else {
            $this->service->detachContacts($campaign, $request->contacts);
            $message = 'Contacts removed from campaign.';
        }

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', $message);
    }
}
