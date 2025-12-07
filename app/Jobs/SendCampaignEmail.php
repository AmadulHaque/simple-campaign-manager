<?php

namespace App\Jobs;

use App\Events\EmailStatusUpdated;
use App\Models\Campaign;
use App\Models\Contact;
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

            $this->contact->campaigns()->updateExistingPivot($this->campaign->id, [
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

            event(new EmailStatusUpdated($this->campaign, $this->contact, 'sent'));

        } catch (\Exception $e) {
            $this->contact->campaigns()->updateExistingPivot($this->campaign->id, [
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

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

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send campaign email', [
            'campaign_id' => $this->campaign->id,
            'contact_id'  => $this->contact->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
