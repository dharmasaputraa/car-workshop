<?php

namespace App\Events;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendWorkOrderInvoice
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WorkOrder $workOrder)
    {
        //
    }
}
