<?php

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    // READ
    public function getPaginatedInvoices(): LengthAwarePaginator;
    public function findById(string $id): Invoice;
    public function findByWorkOrderId(string $workOrderId): ?Invoice;
    public function findByComplaintId(string $complaintId): ?Invoice;

    // WRITE
    public function create(array $data): Invoice;
    public function update(Invoice $invoice, array $data): Invoice;
    public function updateStatus(Invoice $invoice, string $status): Invoice;
    public function delete(Invoice $invoice): void;

    // RELATIONS
    public function loadRelations(Invoice $invoice, array $relations): Invoice;
    public function loadMissingRelations(Invoice $invoice, array $relations): Invoice;
}
