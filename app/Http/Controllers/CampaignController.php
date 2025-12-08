<?php

// app/Http/Controllers/CampaignController.php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $service
    ) {}

    public function index(Request $request)
    {
        $campaigns = Campaign::with('contacts')
            ->latest()
            ->paginate(10);

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create()
    {
        $contacts = $this->service->getAvailableContacts(new Campaign);

        return Inertia::render('campaigns/create', [
            'contacts' => $contacts,
        ]);
    }

    public function store(CampaignRequest $request)
    {
        $campaign = $this->service->createCampaign([
            'subject' => $request->subject,
            'content' => $request->content,
            'status'  => 'draft',
            'user_id' => auth()->id(),
        ]);

        if ($request->has('contacts')) {
            $this->service->attachContacts($campaign, $request->contacts);
        }

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['contacts', 'emailLogs']);
        $availableContacts = $this->service->getAvailableContacts($campaign);
        $stats             = $this->service->getCampaignStats($campaign);

        return Inertia::render('campaigns/show', [
            'campaign'          => $campaign,
            'availableContacts' => $availableContacts,
            'stats'             => $stats,
        ]);
    }

    public function send(Campaign $campaign)
    {
        $this->service->sendCampaign($campaign);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign is being sent.');
    }

    public function updateContacts(Request $request, Campaign $campaign)
    {
        $request->validate([
            'contacts'   => 'required|array',
            'contacts.*' => 'exists:contacts,id',
            'action'     => 'required|in:attach,detach',
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
