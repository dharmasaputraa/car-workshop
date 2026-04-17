<?php

namespace App\Repositories\Eloquent;

use App\Enums\RoleType;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    private const PER_PAGE = 15;

    /*
    |--------------------------------------------------------------------------
    | DATA ISOLATION
    |--------------------------------------------------------------------------
    */

    private function applyDataIsolation($query)
    {
        $user = Auth::user();

        if (! $user) {
            return $query;
        }

        // Super Admin and Admin see all invoices
        if ($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return $query;
        }

        // Customer sees only invoices for their own work orders (through their cars)
        if ($user->hasRole(RoleType::CUSTOMER->value)) {
            return $query->whereHas('workOrder.car', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        }

        // Mechanic cannot access invoices directly
        return $query->whereRaw('1 = 0');
    }

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getPaginatedInvoices(): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Invoice::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('work_order_id'),
                AllowedFilter::partial('invoice_number'),
            )
            ->allowedSorts('invoice_number', 'created_at', 'due_date', 'status', 'total')
            ->allowedIncludes('workOrder', 'workOrder.car', 'workOrder.car.owner')
            ->defaultSort('-created_at');

        return $this->applyDataIsolation($query)
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function findById(string $id): Invoice
    {
        $query = QueryBuilder::for(Invoice::class)
            ->allowedIncludes('workOrder', 'workOrder.car', 'workOrder.car.owner', 'workOrder.workOrderServices');

        return $this->applyDataIsolation($query)->findOrFail($id);
    }

    public function findByWorkOrderId(string $workOrderId): ?Invoice
    {
        $query = Invoice::where('work_order_id', $workOrderId);

        // Apply data isolation
        $query = $this->applyDataIsolation($query);

        return $query->first();
    }

    public function findByComplaintId(string $complaintId): ?Invoice
    {
        $query = Invoice::where('complaint_id', $complaintId);

        // Apply data isolation
        $query = $this->applyDataIsolation($query);

        return $query->first();
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Invoice
    {
        return Invoice::create([
            'invoice_number' => $data['invoice_number'],
            'work_order_id' => $data['work_order_id'],
            'complaint_id' => $data['complaint_id'] ?? null,
            'subtotal' => $data['subtotal'],
            'discount' => $data['discount'] ?? 0,
            'tax' => $data['tax'] ?? 0,
            'total' => $data['total'],
            'status' => $data['status'],
            'due_date' => $data['due_date'],
        ]);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);

        return $invoice;
    }

    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);

        return $invoice;
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function loadRelations(Invoice $invoice, array $relations): Invoice
    {
        return $invoice->load($relations);
    }

    public function loadMissingRelations(Invoice $invoice, array $relations): Invoice
    {
        return $invoice->loadMissing($relations);
    }
}
