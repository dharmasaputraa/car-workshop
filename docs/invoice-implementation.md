# Invoice System Implementation

## Overview

A complete invoice system has been implemented for the car workshop application. The system allows generating invoices from completed work orders, sending them to customers, tracking payments, and managing invoice status.

## Status Flow

```
DRAFT → UNPAID → PAID
  ↓
CANCELED
```

## Files Created/Modified

### 1. Database

- **Migration**: `database/migrations/2026_04_14_060623_create_invoices_table.php`
    - Creates invoices table with fields: id, invoice_number, work_order_id, subtotal, discount, tax, total, status, due_date

### 2. Enums

- **InvoiceStatus**: `app/Enums/InvoiceStatus.php`
    - Added DRAFT status for initial invoice state
    - Flow: DRAFT → UNPAID → PAID
    - Alternative: DRAFT/UNPAID → CANCELED

### 3. Models

- **Invoice**: `app/Models/Invoice.php`
    - Added HasFactory trait
    - Added is_overdue accessor
    - Relationship to WorkOrder

- **WorkOrder**: Already has invoice relationship defined

### 4. DTOs

- **GenerateInvoiceData**: `app/DTOs/Invoice/GenerateInvoiceData.php`
    - work_order_id, invoice_number, discount, tax, due_date

- **PayInvoiceData**: `app/DTOs/Invoice/PayInvoiceData.php`
    - payment_method, payment_reference, payment_notes

### 5. Requests

- **PayInvoiceRequest**: `app/Http/Requests/Api/V1/Invoice/PayInvoiceRequest.php`
    - Validates payment data

### 6. Resources

- **InvoiceResource**: `app/Http/Resources/Api/V1/Invoice/InvoiceResource.php`
    - Updated with includePreviouslyLoadedRelationships
    - Added is_overdue meta field

- **WorkOrderResource**: Already includes invoice relationship

### 7. Repositories

- **InvoiceRepositoryInterface**: `app/Repositories/Contracts/InvoiceRepositoryInterface.php`
    - CRUD operations
    - Data isolation based on roles
    - findByWorkOrderId method

- **InvoiceRepository**: `app/Repositories/Eloquent/InvoiceRepository.php`
    - Full implementation with QueryBuilder
    - Role-based data isolation:
        - Super Admin/Admin: all invoices
        - Customer: only their own invoices
        - Mechanic: no access

### 8. Services

- **InvoiceService**: `app/Services/InvoiceService.php`
    - calculateInvoiceTotals: sums non-canceled services
    - generateInvoiceNumber: creates unique invoice numbers (INV-YYYYMM-XXXX)
    - generateInvoice: creates invoice from work order
    - Validation methods for send, pay, cancel operations

### 9. Actions

- **GenerateInvoiceAction**: `app/Actions/Invoices/GenerateInvoiceAction.php`
    - Generates invoice from work order

- **SendInvoiceAction**: `app/Actions/Invoices/SendInvoiceAction.php`
    - Transitions DRAFT → UNPAID
    - Dispatches SendWorkOrderInvoice event

- **PayInvoiceAction**: `app/Actions/Invoices/PayInvoiceAction.php`
    - Transitions UNPAID → PAID

- **CancelInvoiceAction**: `app/Actions/Invoices/CancelInvoiceAction.php`
    - Transitions DRAFT/UNPAID → CANCELED

### 10. Controllers

- **InvoiceController**: `app/Http/Controllers/Api/V1/Invoice/InvoiceController.php`
    - index: List invoices with filtering
    - generate: Create new invoice
    - show: Get invoice details
    - send: Send invoice to customer
    - pay: Mark as paid
    - cancel: Cancel invoice

### 11. Policies

- **InvoicePolicy**: `app/Policies/InvoicePolicy.php`
    - viewAny, view, create, update, delete
    - send, pay, cancel custom actions
    - Role-based authorization

### 12. Routes

- **routes/api/v1.php**:
    ```
    POST   /api/v1/invoices/generate
    GET    /api/v1/invoices
    GET    /api/v1/invoices/{id}
    PATCH  /api/v1/invoices/{id}/send
    PATCH  /api/v1/invoices/{id}/pay
    PATCH  /api/v1/invoices/{id}/cancel
    ```

### 13. Service Provider

- **AppServiceProvider**: Registered InvoiceRepository binding

### 14. Work Order Integration

- **MarkWorkOrderAsInvoicedAction**: Updated to automatically generate and send invoice
    - When marking work order as INVOICED, generates invoice and immediately sends to customer
    - Transitions invoice from DRAFT → UNPAID
    - Dispatches SendWorkOrderInvoice event for email notification
    - Loads invoice relationship in response

## API Endpoints

### Generate Invoice

```http
POST /api/v1/invoices/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "work_order_id": "uuid",
  "discount": 0.00,
  "tax": 0.00,
  "due_date": "2026-04-22"
}
```

### List Invoices

```http
GET /api/v1/invoices?filter[status]=unpaid&include=workOrder,workOrder.car
Authorization: Bearer {token}
```

### Get Invoice

```http
GET /api/v1/invoices/{id}?include=workOrder,workOrder.car,workOrder.workOrderServices
Authorization: Bearer {token}
```

### Send Invoice

```http
PATCH /api/v1/invoices/{id}/send
Authorization: Bearer {token}
```

Transitions DRAFT → UNPAID and sends email notification

### Pay Invoice

```http
PATCH /api/v1/invoices/{id}/pay
Authorization: Bearer {token}
Content-Type: application/json

{
  "payment_method": "Bank Transfer",
  "payment_reference": "REF123",
  "payment_notes": "Paid via bank transfer"
}
```

Transitions UNPAID → PAID

### Cancel Invoice

```http
PATCH /api/v1/invoices/{id}/cancel
Authorization: Bearer {token}
```

Transitions DRAFT/UNPAID → CANCELED

## Work Order Flow Integration

When a work order is marked as INVOICED:

1. System automatically generates an invoice (starts as DRAFT)
2. Immediately transitions invoice to UNPAID status
3. Calculates totals from completed services
4. Creates unique invoice number
5. Sets default due date (7 days from now)
6. Dispatches email notification to customer
7. Returns work order with invoice relationship

**Important**: The customer receives the invoice email immediately when the admin marks the work order as invoiced. No manual "send" action is required.

## Permissions Needed

Add these permissions to your roles via Shield:

- `view_any_invoice`
- `view_invoice`
- `create_invoice`
- `update_invoice`
- `delete_invoice`
- `send_invoice`
- `pay_invoice`
- `cancel_invoice`

Generate and seed permissions:

```bash
php artisan shield:seeder --generate --option=permissions_via_roles
php artisan db:seed --class=ShieldSeeder
```

## Testing

After running migrations, test the flow:

1. Complete a work order
2. Mark as invoiced (auto-generates AND sends invoice)
3. Customer receives email notification immediately
4. Customer views invoice
5. Mark invoice as paid

## Notes

- Invoice numbers follow format: `INV-YYYYMM-XXXX` (e.g., INV-202604-0001)
- Only non-canceled services are included in invoice totals
- Default due date is 7 days from invoice creation
- Email notifications use existing SendWorkOrderInvoice event
- Data isolation ensures customers only see their own invoices
- Mechanics cannot access invoices directly
- When marking work order as invoiced, invoice is automatically sent to customer (no manual step required)
