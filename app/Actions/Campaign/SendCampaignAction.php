<?php

namespace App\Actions\Campaign;

use App\Jobs\ProcessCampaignBatchJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCampaignAction
{
    /**
     * Batch size for processing recipients
     * Adjust based on your server capacity and email rate limits
     */
    private const BATCH_SIZE = 100;

    /**
     * Number of parallel workers to dispatch
     * Increase for faster processing with multiple queue workers
     */
    private const PARALLEL_BATCHES = 10;

    /**
     * Cache TTL for campaign progress tracking (in seconds)
     */
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Execute the campaign sending process
     *
     * @throws \Exception
     */
    public function execute(Campaign $campaign): void
    {
        // Validation: Ensure campaign can be sent
        $this->validateCampaign($campaign);

        DB::transaction(function () use ($campaign) {
            // Update campaign status
            $campaign->update([
                'status'  => 'sending',
                'sent_at' => null, // Will be set when completed
            ]);

            // Initialize campaign progress tracking
            $this->initializeProgressTracking($campaign);

            // Log campaign start
            Log::info('Campaign sending started', [
                'campaign_id'      => $campaign->id,
                'campaign_name'    => $campaign->name,
                'total_recipients' => $campaign->total_recipients,
            ]);
        });

        // Dispatch batches to queue
        $this->dispatchBatches($campaign);

        // Optional: Broadcast event for real-time updates
        // event(new CampaignSendingStarted($campaign));
    }

    /**
     * Validate that the campaign can be sent
     *
     * @throws \Exception
     */
    private function validateCampaign(Campaign $campaign): void
    {
        // Check if campaign is in draft status
        if ($campaign->status !== 'draft') {
            throw new \Exception("Campaign must be in 'draft' status to send. Current status: {$campaign->status}");
        }

        // Check if campaign has recipients
        if ($campaign->total_recipients === 0) {
            throw new \Exception('Campaign has no recipients.');
        }

        // Check if campaign has pending recipients
        $pendingCount = $campaign->recipients()->where('status', 'pending')->count();
        if ($pendingCount === 0) {
            throw new \Exception('Campaign has no pending recipients to send.');
        }

        // Validate campaign content
        if (empty($campaign->subject) || empty($campaign->body)) {
            throw new \Exception('Campaign must have both subject and body content.');
        }
    }

    /**
     * Initialize progress tracking in cache
     */
    private function initializeProgressTracking(Campaign $campaign): void
    {
        $cacheKey = "campaign:{$campaign->id}:progress";

        Cache::put($cacheKey, [
            'status'               => 'sending',
            'total'                => $campaign->total_recipients,
            'sent'                 => 0,
            'failed'               => 0,
            'pending'              => $campaign->total_recipients,
            'progress_percentage'  => 0,
            'started_at'           => now()->toIso8601String(),
            'estimated_completion' => null,
        ], self::CACHE_TTL);
    }

    /**
     * Dispatch recipient batches to the queue
     */
    private function dispatchBatches(Campaign $campaign): void
    {
        $totalBatches = 0;
        $recipientIds = [];

        // Get all pending recipient IDs
        $pendingRecipients = $campaign->recipients()
            ->where('status', 'pending')
            ->select('id')
            ->get()
            ->pluck('id')
            ->toArray();

        $totalPending = count($pendingRecipients);

        Log::info('Preparing to dispatch batches', [
            'campaign_id'   => $campaign->id,
            'total_pending' => $totalPending,
            'batch_size'    => self::BATCH_SIZE,
        ]);

        // Split recipients into batches and dispatch
        $batches = array_chunk($pendingRecipients, self::BATCH_SIZE);

        foreach ($batches as $index => $batchIds) {
            // Fetch recipient models for this batch
            $recipients = CampaignRecipient::whereIn('id', $batchIds)
                ->with('contact:id,name,email,status') // Eager load contacts
                ->get();

            // Calculate priority for staggered dispatch
            // Higher priority for earlier batches
            $priority = self::PARALLEL_BATCHES - ($index % self::PARALLEL_BATCHES);

            // Dispatch batch job to queue with priority
            ProcessCampaignBatchJob::dispatch($campaign, $recipients)
                ->onQueue('campaigns') // Use dedicated queue
                ->delay(now()->addSeconds($index % self::PARALLEL_BATCHES)) // Stagger dispatch
                ->onConnection('redis'); // Use Redis for better performance

            $totalBatches++;

            // Optional: Throttle dispatch for very large campaigns
            if ($totalBatches % 100 === 0) {
                Log::info("Dispatched {$totalBatches} batches", [
                    'campaign_id' => $campaign->id,
                ]);

                // Small delay every 100 batches to prevent overwhelming the queue
                usleep(100000); // 0.1 seconds
            }
        }

        Log::info('Campaign batch dispatch completed', [
            'campaign_id'          => $campaign->id,
            'total_batches'        => $totalBatches,
            'recipients_per_batch' => self::BATCH_SIZE,
        ]);

        // Update campaign metadata
        $campaign->update([
            'metadata' => json_encode([
                'total_batches' => $totalBatches,
                'batch_size'    => self::BATCH_SIZE,
                'dispatched_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Resume a paused or failed campaign
     * Useful for retry functionality
     */
    public function resume(Campaign $campaign): void
    {
        Log::info('Resuming campaign', [
            'campaign_id' => $campaign->id,
        ]);

        // Reset status if it was failed
        if (in_array($campaign->status, ['failed', 'paused'])) {
            $campaign->update(['status' => 'sending']);
        }

        // Only dispatch pending and failed recipients
        $this->dispatchBatches($campaign);
    }

    /**
     * Pause an ongoing campaign
     * Useful for emergency stops
     */
    public function pause(Campaign $campaign): void
    {
        if ($campaign->status !== 'sending') {
            throw new \Exception('Can only pause campaigns that are currently sending.');
        }

        $campaign->update(['status' => 'paused']);

        Log::warning('Campaign paused', [
            'campaign_id'      => $campaign->id,
            'sent_count'       => $campaign->sent_count,
            'total_recipients' => $campaign->total_recipients,
        ]);

        // Optional: Cancel queued jobs
        // This would require implementing job tracking
    }

    /**
     * Cancel a campaign and mark all pending as failed
     */
    public function cancel(Campaign $campaign): void
    {
        if (! in_array($campaign->status, ['sending', 'paused', 'draft'])) {
            throw new \Exception('Cannot cancel a campaign that has already been sent.');
        }

        DB::transaction(function () use ($campaign) {
            // Mark all pending recipients as failed
            $pendingCount = $campaign->recipients()
                ->where('status', 'pending')
                ->update([
                    'status'        => 'failed',
                    'error_message' => 'Campaign cancelled by user',
                    'updated_at'    => now(),
                ]);

            // Update campaign status
            $campaign->update([
                'status'       => 'failed',
                'failed_count' => $campaign->failed_count + $pendingCount,
            ]);
        });

        Log::warning('Campaign cancelled', [
            'campaign_id' => $campaign->id,
        ]);
    }

    /**
     * Retry failed recipients
     */
    public function retryFailed(Campaign $campaign): void
    {
        $failedCount = $campaign->recipients()
            ->where('status', 'failed')
            ->count();

        if ($failedCount === 0) {
            throw new \Exception('No failed recipients to retry.');
        }

        Log::info('Retrying failed recipients', [
            'campaign_id'  => $campaign->id,
            'failed_count' => $failedCount,
        ]);

        // Reset failed recipients to pending
        $campaign->recipients()
            ->where('status', 'failed')
            ->update([
                'status'        => 'pending',
                'error_message' => null,
                'updated_at'    => now(),
            ]);

        // Update campaign counters
        $campaign->update([
            'status'       => 'sending',
            'failed_count' => 0,
        ]);

        // Dispatch batches for retry
        $this->dispatchBatches($campaign);
    }

    /**
     * Get current campaign progress
     */
    public function getProgress(Campaign $campaign): array
    {
        $cacheKey = "campaign:{$campaign->id}:progress";

        // Try to get from cache first
        $progress = Cache::get($cacheKey);

        // If not in cache, calculate from database
        if (! $progress) {
            $campaign->refresh();

            $totalProcessed     = $campaign->sent_count + $campaign->failed_count;
            $progressPercentage = $campaign->total_recipients > 0
                ? round(($totalProcessed / $campaign->total_recipients) * 100, 2)
                : 0;

            $progress = [
                'status'              => $campaign->status,
                'total'               => $campaign->total_recipients,
                'sent'                => $campaign->sent_count,
                'failed'              => $campaign->failed_count,
                'pending'             => $campaign->total_recipients - $totalProcessed,
                'progress_percentage' => $progressPercentage,
                'started_at'          => $campaign->created_at->toIso8601String(),
                'completed_at'        => $campaign->sent_at?->toIso8601String(),
            ];

            // Cache the progress
            Cache::put($cacheKey, $progress, 300); // 5 minutes
        }

        return $progress;
    }

    /**
     * Check if campaign sending is complete
     * This is typically called by the last job
     */
    public function checkCompletion(Campaign $campaign): void
    {
        $campaign->refresh();

        $totalProcessed = $campaign->sent_count + $campaign->failed_count;

        // Check if all recipients have been processed
        if ($totalProcessed >= $campaign->total_recipients) {
            DB::transaction(function () use ($campaign) {
                $campaign->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);

                Log::info('Campaign sending completed', [
                    'campaign_id'      => $campaign->id,
                    'campaign_name'    => $campaign->name,
                    'total_recipients' => $campaign->total_recipients,
                    'sent_count'       => $campaign->sent_count,
                    'failed_count'     => $campaign->failed_count,
                    'success_rate'     => $campaign->success_rate,
                ]);
            });

            // Clear progress cache
            Cache::forget("campaign:{$campaign->id}:progress");

            // Optional: Send completion notification
            // Notification::send($campaign->user, new CampaignCompletedNotification($campaign));

            // Optional: Broadcast completion event
            // event(new CampaignCompleted($campaign));
        }
    }

    /**
     * Get campaign sending statistics
     */
    public function getStatistics(Campaign $campaign): array
    {
        $campaign->refresh();

        $totalProcessed = $campaign->sent_count + $campaign->failed_count;
        $pending        = $campaign->total_recipients - $totalProcessed;

        // Calculate estimated time remaining
        $estimatedTimeRemaining = null;
        if ($campaign->status === 'sending' && $campaign->created_at) {
            $elapsedMinutes = $campaign->created_at->diffInMinutes(now());
            if ($totalProcessed > 0 && $elapsedMinutes > 0) {
                $recipientsPerMinute    = $totalProcessed / $elapsedMinutes;
                $remainingMinutes       = $pending           / $recipientsPerMinute;
                $estimatedTimeRemaining = round($remainingMinutes, 2);
            }
        }

        return [
            'campaign_id'                      => $campaign->id,
            'campaign_name'                    => $campaign->name,
            'status'                           => $campaign->status,
            'total_recipients'                 => $campaign->total_recipients,
            'sent_count'                       => $campaign->sent_count,
            'failed_count'                     => $campaign->failed_count,
            'pending_count'                    => $pending,
            'success_rate'                     => $campaign->success_rate,
            'progress_percentage'              => round(($totalProcessed / $campaign->total_recipients) * 100, 2),
            'estimated_time_remaining_minutes' => $estimatedTimeRemaining,
            'started_at'                       => $campaign->created_at->toIso8601String(),
            'completed_at'                     => $campaign->sent_at?->toIso8601String(),
        ];
    }
}
