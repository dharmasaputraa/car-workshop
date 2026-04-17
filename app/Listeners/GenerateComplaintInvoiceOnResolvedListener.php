<?php

namespace App\Listeners;

use App\Actions\Invoices\GenerateComplaintInvoiceAction;
use App\Events\ComplaintResolved;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateComplaintInvoiceOnResolvedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function __construct(
        protected ComplaintRepositoryInterface $complaintRepository,
        protected GenerateComplaintInvoiceAction $generateComplaintInvoiceAction
    ) {}

    public function handle(ComplaintResolved $event): void
    {
        $complaint = $event->complaint;
        $workOrderId = $complaint->work_order_id;

        // Check if there are other active complaints on this work order
        $activeComplaint = $this->complaintRepository->findActiveByWorkOrderId($workOrderId);

        // Only generate invoice when ALL complaints are resolved (no more active complaints)
        if (!$activeComplaint) {
            // Generate invoice for this resolved complaint
            // Note: If there were multiple complaints, we might want to generate one invoice per complaint
            // Or one consolidated invoice. For now, we generate per complaint.
            try {
                $this->generateComplaintInvoiceAction->execute($complaint->id);
            } catch (Exception $e) {
                // Log error but don't fail the entire process
                Log::error('Failed to generate complaint invoice', [
                    'complaint_id' => $complaint->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
