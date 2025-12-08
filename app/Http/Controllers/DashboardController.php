<?php

namespace App\Http\Controllers;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;

class DashboardController extends Controller
{
    public function index()
    {
        $totalContacts  = Contact::count();
        $totalCampaigns = Campaign::count();

        $totalSent    = CampaignRecipient::where('status', CampaignRecipientStatus::SENT)->count();
        $totalFailed  = CampaignRecipient::where('status', CampaignRecipientStatus::FAILED)->count();
        $totalPending = CampaignRecipient::where('status', CampaignRecipientStatus::PENDING)->count();

        $totalDelivered = $totalSent + $totalFailed + $totalPending;
        $deliveryRate   = $totalDelivered > 0
            ? round(($totalSent / $totalDelivered) * 100, 1)
            : 0;

        $recentCampaigns = Campaign::withCount(['recipients as sent_count' => fn ($q) => $q->where('status', CampaignStatus::SENT)])
            ->latest()
            ->take(5)
            ->get();

        $chartData = [
            'sent'    => $totalSent,
            'pending' => $totalPending,
            'failed'  => $totalFailed,
        ];

        return inertia('dashboard', [
            'stats' => [
                'contacts'     => $totalContacts,
                'campaigns'    => $totalCampaigns,
                'emailsSent'   => $totalSent,
                'deliveryRate' => $deliveryRate.'%',
            ],
            'recentCampaigns' => $recentCampaigns,
            'chartData'       => $chartData,
        ]);
    }
}
