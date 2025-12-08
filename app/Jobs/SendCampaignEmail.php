<?php

namespace App\Jobs;

use App\Enums\CampaignRecipientStatus;
use App\Events\EmailStatusUpdated;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\CampaignRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 180, 300];

    public function __construct(
        public Campaign $campaign,
        public Contact $contact
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Simulate email sending
            $this->simulateEmailSending();

            // Update recipient status
            $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
                ->where('contact_id', $this->contact->id)
                ->first();

            if ($recipient) {
                $recipient->update([
                    'status' => CampaignRecipientStatus::SENT,
                    'sent_at' => now(),
                ]);
            }

            event(new EmailStatusUpdated($this->campaign, $this->contact, 'sent'));

            // Check if all emails have been sent
            $this->updateCampaignStatus();

        } catch (\Exception $e) {
            // Update recipient status to failed
            $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
                ->where('contact_id', $this->contact->id)
                ->first();

            if ($recipient) {
                $recipient->update([
                    'status' => CampaignRecipientStatus::FAILED,
                    'error_message' => $e->getMessage(),
                ]);
            }

            event(new EmailStatusUpdated($this->campaign, $this->contact, 'failed', $e->getMessage()));

            throw $e;
        }
    }

    private function simulateEmailSending(): void
    {
        // Simulate network delay
        sleep(rand(1, 3));

        // Randomly fail 10% of emails to simulate real-world conditions
        if (rand(1, 100) <= 10) {
            throw new \Exception('SMTP server unavailable');
        }
    }

    private function updateCampaignStatus(): void
    {
        $totalRecipients = $this->campaign->recipients()->count();
        $sentRecipients = $this->campaign->recipients()
            ->where('status', CampaignRecipientStatus::SENT)
            ->count();
        $failedRecipients = $this->campaign->recipients()
            ->where('status', CampaignRecipientStatus::FAILED)
            ->count();

        // Update campaign statistics
        $this->campaign->update([
            'sent_count' => $sentRecipients,
            'failed_count' => $failedRecipients,
        ]);

        // If all emails have been processed, mark campaign as sent
        if ($sentRecipients + $failedRecipients >= $totalRecipients) {
            $this->campaign->update([
                'status' => \App\Enums\CampaignStatus::SENT,
                'sent_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send campaign email', [
            'campaign_id' => $this->campaign->id,
            'contact_id'  => $this->contact->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
