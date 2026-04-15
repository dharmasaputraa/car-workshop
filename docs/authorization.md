# Authorization & Permissions Documentation

## Overview

This document describes the role-based access control (RBAC) system for the Car Workshop API, including roles, permissions, data isolation rules, and policy implementations.

---

## Roles

| Role            | Description                                                                      | Access Level                               |
| --------------- | -------------------------------------------------------------------------------- | ------------------------------------------ |
| **Super Admin** | Full system access, can manage all resources and users                           | All permissions                            |
| **Admin**       | Workshop operations manager, can manage work orders, assign mechanics            | Most permissions (no user/role management) |
| **Mechanic**    | Workshop staff, can view assigned work and update their own assignments          | Limited permissions, own data only         |
| **Customer**    | Car owners, can create work orders, approve diagnoses, and view their own orders | Limited permissions, own cars only         |

---

## Permission Matrix

### Work Orders

| Permission                    | Super Admin | Admin | Mechanic | Customer | Description                                       |
| ----------------------------- | :---------: | :---: | :------: | :------: | ------------------------------------------------- |
| `view_any_work_order`         |     ✅      |  ✅   |   ✅\*   |   ✅\*   | View all work orders (filtered by data isolation) |
| `view_work_order`             |     ✅      |  ✅   |   ✅\*   |   ✅\*   | View single work order                            |
| `create_work_order`           |     ✅      |  ✅   |    ❌    |    ❌    | Create new work order                             |
| `update_work_order`           |     ✅      |  ✅   |    ❌    |    ❌    | Update work order details                         |
| `delete_work_order`           |     ✅      |  ✅   |    ❌    |    ❌    | Delete work order                                 |
| `cancel_work_order`           |     ✅      |  ✅   |    ❌    |   ✅\*   | Cancel work order                                 |
| `diagnose_work_order`         |     ✅      |  ✅   |    ✅    |    ❌    | Submit diagnosis and proposed services            |
| `approve_work_order`          |     ✅      |  ✅   |    ❌    |   ✅\*   | Approve diagnosis (customer only)                 |
| `complete_work_order`         |     ✅      |  ✅   |    ❌    |    ❌    | Mark work order as completed                      |
| `mark_invoiced_work_order`    |     ✅      |  ✅   |    ❌    |    ❌    | Mark work order as invoiced                       |
| `record_complaint_work_order` |     ✅      |  ✅   |    ❌    |   ✅\*   | Record a complaint                                |
| `assign_mechanic_work_order`  |     ✅      |  ✅   |    ❌    |    ❌    | Assign/remove mechanics to services               |
| `start_work_order_service`    |     ✅      |  ✅   |    ❌    |    ❌    | Start a work order service (bulk)                 |
| `complete_work_order_service` |     ✅      |  ✅   |    ❌    |    ❌    | Complete a work order service (bulk)              |

\* = Data isolation applied (mechanics see only assigned work, customers see only their own cars)

### Mechanic Assignments

| Permission                     | Super Admin | Admin | Mechanic | Customer | Description                                       |
| ------------------------------ | :---------: | :---: | :------: | :------: | ------------------------------------------------- |
| `view_any_mechanic_assignment` |     ✅      |  ✅   |   ✅\*   |    ❌    | View all assignments (filtered by data isolation) |
| `view_mechanic_assignment`     |     ✅      |  ✅   |   ✅\*   |    ❌    | View single assignment                            |
| `create_mechanic_assignment`   |     ✅      |  ✅   |    ❌    |    ❌    | Create assignment                                 |
| `update_mechanic_assignment`   |     ✅      |  ✅   |   ✅\*   |    ❌    | Update assignment                                 |
| `delete_mechanic_assignment`   |     ✅      |  ✅   |    ❌    |    ❌    | Delete assignment                                 |
| `cancel_mechanic_assignment`   |     ✅      |  ✅   |    ❌    |    ❌    | Cancel assignment                                 |
| `start_mechanic_assignment`    |     ✅      |  ✅   |   ✅\*   |    ❌    | Mechanic starts own work                          |
| `complete_mechanic_assignment` |     ✅      |  ✅   |   ✅\*   |    ❌    | Mechanic completes own work                       |

\* = Data isolation applied (mechanics see only their own assignments)

### Other Resources

| Permission           | Super Admin | Admin | Mechanic | Customer | Description                                             |
| -------------------- | :---------: | :---: | :------: | :------: | ------------------------------------------------------- |
| **Users**            |
| `view_any_user`      |     ✅      |  ✅   |    ❌    |    ❌    | View all users                                          |
| `view_user`          |     ✅      |  ✅   |    ❌    |    ❌    | View single user                                        |
| `create_user`        |     ✅      |  ✅   |    ❌    |    ❌    | Create user                                             |
| `update_user`        |     ✅      |  ✅   |    ❌    |    ❌    | Update user                                             |
| `delete_user`        |     ✅      |  ❌   |    ❌    |    ❌    | Delete user (super admin only)                          |
| `toggle_active_user` |     ✅      |  ✅   |    ❌    |    ❌    | Activate/deactivate user                                |
| `change_role_user`   |     ✅      |  ❌   |    ❌    |    ❌    | Change user role (super admin only)                     |
| **Cars**             |
| `view_any_car`       |     ✅      |  ✅   |   ✅\*   |   ✅\*   | View all cars (with data isolation)                     |
| `view_car`           |     ✅      |  ✅   |   ✅\*   |   ✅\*   | View single car (with data isolation)                   |
| `create_car`         |     ✅      |  ✅   |    ❌    |   ✅\*   | Create car (customer adds own)                          |
| `update_car`         |     ✅      |  ✅   |    ❌    |   ✅\*   | Update car (customer updates own)                       |
| `delete_car`         |     ✅      |  ✅   |    ❌    |   ✅\*   | Delete car (customer deletes own, with active WO guard) |

\* = Data isolation applied (mechanics see only assigned WO cars, customers see only own cars)
| Permission | Super Admin | Admin | Mechanic | Customer | Description |
|------------------------|------------|-------|----------|----------|----------------------------------------------|
| `view_any_service` | ✅ | ✅ | ✅ | ✅ | View all services |
| `view_service` | ✅ | ✅ | ✅ | ✅ | View single service |
| `create_service` | ✅ | ✅ | ❌ | ❌ | Create service catalog item |
| `update_service` | ✅ | ✅ | ❌ | ❌ | Update service catalog item |
| `delete_service` | ✅ | ✅ | ❌ | ❌ | Delete service catalog item |
| `toggle_active_service`| ✅ | ✅ | ❌ | ❌ | Activate/deactivate service |
| `view_any_invoice` | ✅ | ✅ | ❌ | ✅* | View all invoices (data isolation applied) |
| `view_invoice` | ✅ | ✅ | ❌ | ✅* | View single invoice (data isolation applied) |
| `create_invoice` | ✅ | ✅ | ❌ | ❌ | Generate invoice from work order |
| `update_invoice` | ✅ | ✅ | ❌ | ❌ | Update invoice details |
| `delete_invoice` | ✅ | ✅ | ❌ | ❌ | Delete invoice |
| `send_invoice` | ✅ | ✅ | ❌ | ❌ | Send invoice to customer |
| `pay_invoice` | ✅ | ✅ | ❌ | ✅\* | Record payment for invoice |
| `cancel_invoice` | ✅ | ✅ | ❌ | ❌ | Cancel invoice |

\* = Data isolation applied (customers see only invoices for their own work orders)
| Permission | Super Admin | Admin | Mechanic | Customer | Description |
|------------------------------|------------|-------|----------|----------|---------------------------------------------------------------|
| `view_any_complaint` | ✅ | ✅ | ✅* | ✅* | View all complaints (data isolation applied) |
| `view_complaint` | ✅ | ✅ | ✅* | ✅* | View single complaint (data isolation applied) |
| `create_complaint` | ✅ | ✅ | ❌ | ❌ | Create complaint (via work order) |
| `reassign_complaint` | ✅ | ✅ | ❌ | ❌ | Reassign complaint for rework |
| `resolve_complaint` | ✅ | ✅ | ❌ | ❌ | Mark complaint as resolved |
| `reject_complaint` | ✅ | ✅ | ❌ | ❌ | Reject complaint |
| `assign_mechanic_complaint` | ✅ | ✅ | ❌ | ❌ | Assign mechanic to complaint service |

\* = Data isolation applied (mechanics see only complaints where assigned, customers see only their own work order complaints)

---

## Data Isolation Rules

### Work Orders

| Role            | Data Scope                                                    |
| --------------- | ------------------------------------------------------------- |
| **Super Admin** | All work orders                                               |
| **Admin**       | All work orders                                               |
| **Mechanic**    | Work orders where they have at least one active assignment    |
| **Customer**    | Work orders for cars they own (`car.owner_id = auth()->id()`) |

### Mechanic Assignments

| Role            | Data Scope                                                |
| --------------- | --------------------------------------------------------- |
| **Super Admin** | All assignments                                           |
| **Admin**       | All assignments                                           |
| **Mechanic**    | Only their own assignments (`mechanic_id = auth()->id()`) |
| **Customer**    | None (cannot view assignments)                            |

### Cars

| Role            | Data Scope                                                                                                    |
| --------------- | ------------------------------------------------------------------------------------------------------------- |
| **Super Admin** | All cars                                                                                                      |
| **Admin**       | All cars                                                                                                      |
| **Mechanic**    | Only cars linked to assigned Work Orders (`workOrderServices.mechanicAssignments.mechanic_id = auth()->id()`) |
| **Customer**    | Only their own cars (`owner_id = auth()->id()`)                                                               |

**Additional Constraints:**

- Cars with active Work Orders (DRAFT, DIAGNOSED, APPROVED, IN_PROGRESS status) cannot be deleted

### Invoices

| Role            | Data Scope                                                                        |
| --------------- | --------------------------------------------------------------------------------- |
| **Super Admin** | All invoices                                                                      |
| **Admin**       | All invoices                                                                      |
| **Mechanic**    | None (cannot view invoices)                                                       |
| **Customer**    | Only invoices for their own work orders (`workOrder.car.owner_id = auth()->id()`) |

---

## API Endpoint to Permission Mapping

### Work Order Endpoints

| Method    | Endpoint                                            | Permission Required           | Policy Method                                 |
| --------- | --------------------------------------------------- | ----------------------------- | --------------------------------------------- |
| GET       | `/api/v1/work-orders`                               | `view_any_work_order`         | `WorkOrderPolicy::viewAny()`                  |
| POST      | `/api/v1/work-orders`                               | `create_work_order`           | `WorkOrderPolicy::create()`                   |
| GET       | `/api/v1/work-orders/{id}`                          | `view_work_order`             | `WorkOrderPolicy::view()`                     |
| PUT/PATCH | `/api/v1/work-orders/{id}`                          | `update_work_order`           | `WorkOrderPolicy::update()`                   |
| DELETE    | `/api/v1/work-orders/{id}`                          | `delete_work_order`           | `WorkOrderPolicy::delete()`                   |
| PATCH     | `/api/v1/work-orders/{id}/cancel`                   | `cancel_work_order`           | `WorkOrderPolicy::cancel()`                   |
| PATCH     | `/api/v1/work-orders/{id}/diagnose`                 | `diagnose_work_order`         | `WorkOrderPolicy::diagnose()`                 |
| PATCH     | `/api/v1/work-orders/{id}/approve`                  | `approve_work_order`          | `WorkOrderPolicy::approve()`                  |
| PATCH     | `/api/v1/work-orders/{id}/complete`                 | `complete_work_order`         | `WorkOrderPolicy::complete()`                 |
| PATCH     | `/api/v1/work-orders/{id}/mark-invoiced`            | `mark_invoiced_work_order`    | `WorkOrderPolicy::markAsInvoiced()`           |
| PATCH     | `/api/v1/work-orders/{id}/record-complaint`         | `record_complaint_work_order` | `WorkOrderPolicy::recordComplaint()`          |
| PATCH     | `/api/v1/work-orders/services/{id}/assign-mechanic` | `assign_mechanic_work_order`  | `WorkOrderPolicy::assignMechanic()`           |
| PATCH     | `/api/v1/work-orders/services/{id}/start`           | `start_work_order_service`    | `WorkOrderPolicy::startWorkOrderService()`    |
| PATCH     | `/api/v1/work-orders/services/{id}/complete`        | `complete_work_order_service` | `WorkOrderPolicy::completeWorkOrderService()` |
| PATCH     | `/api/v1/work-orders/assignments/{id}/cancel`       | `assign_mechanic_work_order`  | `WorkOrderPolicy::cancelMechanicAssignment()` |

### Mechanic Assignment Endpoints

| Method    | Endpoint                                     | Permission Required            | Policy Method                          |
| --------- | -------------------------------------------- | ------------------------------ | -------------------------------------- |
| GET       | `/api/v1/mechanic-assignments`               | `view_any_mechanic_assignment` | `MechanicAssignmentPolicy::viewAny()`  |
| POST      | `/api/v1/mechanic-assignments`               | `create_mechanic_assignment`   | `MechanicAssignmentPolicy::create()`   |
| GET       | `/api/v1/mechanic-assignments/{id}`          | `view_mechanic_assignment`     | `MechanicAssignmentPolicy::view()`     |
| PUT/PATCH | `/api/v1/mechanic-assignments/{id}`          | `update_mechanic_assignment`   | `MechanicAssignmentPolicy::update()`   |
| DELETE    | `/api/v1/mechanic-assignments/{id}`          | `delete_mechanic_assignment`   | `MechanicAssignmentPolicy::delete()`   |
| PATCH     | `/api/v1/mechanic-assignments/{id}/cancel`   | `cancel_mechanic_assignment`   | `MechanicAssignmentPolicy::cancel()`   |
| PATCH     | `/api/v1/mechanic-assignments/{id}/start`    | `start_mechanic_assignment`    | `MechanicAssignmentPolicy::start()`    |
| PATCH     | `/api/v1/mechanic-assignments/{id}/complete` | `complete_mechanic_assignment` | `MechanicAssignmentPolicy::complete()` |

### Car Endpoints

| Method    | Endpoint            | Permission Required | Policy Method          |
| --------- | ------------------- | ------------------- | ---------------------- |
| GET       | `/api/v1/cars`      | `view_any_car`      | `CarPolicy::viewAny()` |
| POST      | `/api/v1/cars`      | `create_car`        | `CarPolicy::create()`  |
| GET       | `/api/v1/cars/{id}` | `view_car`          | `CarPolicy::view()`    |
| PUT/PATCH | `/api/v1/cars/{id}` | `update_car`        | `CarPolicy::update()`  |
| DELETE    | `/api/v1/cars/{id}` | `delete_car`        | `CarPolicy::delete()`  |

### Service Endpoints

| Method    | Endpoint                       | Permission Required     | Policy Method                   |
| --------- | ------------------------------ | ----------------------- | ------------------------------- |
| GET       | `/api/v1/services`             | `view_any_service`      | `ServicePolicy::viewAny()`      |
| POST      | `/api/v1/services`             | `create_service`        | `ServicePolicy::create()`       |
| GET       | `/api/v1/services/{id}`        | `view_service`          | `ServicePolicy::view()`         |
| PUT/PATCH | `/api/v1/services/{id}`        | `update_service`        | `ServicePolicy::update()`       |
| DELETE    | `/api/v1/services/{id}`        | `delete_service`        | `ServicePolicy::delete()`       |
| PATCH     | `/api/v1/services/{id}/toggle` | `toggle_active_service` | `ServicePolicy::toggleActive()` |

### Invoice Endpoints

| Method | Endpoint                       | Permission Required | Policy Method              |
| ------ | ------------------------------ | ------------------- | -------------------------- |
| GET    | `/api/v1/invoices`             | `view_any_invoice`  | `InvoicePolicy::viewAny()` |
| POST   | `/api/v1/invoices/generate`    | `create_invoice`    | `InvoicePolicy::create()`  |
| GET    | `/api/v1/invoices/{id}`        | `view_invoice`      | `InvoicePolicy::view()`    |
| PATCH  | `/api/v1/invoices/{id}/send`   | `send_invoice`      | `InvoicePolicy::send()`    |
| PATCH  | `/api/v1/invoices/{id}/pay`    | `pay_invoice`       | `InvoicePolicy::pay()`     |
| PATCH  | `/api/v1/invoices/{id}/cancel` | `cancel_invoice`    | `InvoicePolicy::cancel()`  |

### Complaint Endpoints

| Method | Endpoint             | Permission Required  | Policy Method                |
| ------ | -------------------- | -------------------- | ---------------------------- |
| GET    | `/api/v1/complaints` | `view_any_complaint` | `ComplaintPolicy::viewAny()` |

| GET
**WorkOrderPolicy:**

- `isCarOwner()` — Checks if authenticated user owns the car associated with the work order

**MechanicAssignmentPolicy:**

- `isAssignedMechanic()` — Checks if authenticated user is the mechanic assigned to the assignment

### Before Hooks

All policies have a `before()` hook that checks if the user is active:

```php
public function before(AuthUser $authUser, $ability)
{
    if (! $authUser->is_active) {
        return false;
    }
    return null;
}
```

### Permission Naming Convention

Permissions follow the pattern: `{action}_{resource}`

- Examples: `view_any_work_order`, `create_mechanic_assignment`, `start_mechanic_assignment`

### Updating Permissions

To add new permissions:

1. Add the permission to `ShieldSeeder.php`
2. Add the corresponding policy method
3. Use `Gate::authorize()` or `@can` in controllers/views
4. Run `php artisan db:seed --class=ShieldSeeder`

### Testing Authorization

Use Laravel's authorization testing helpers:

```php
$this->actingAs($user)
     ->getJson('/api/v1/work-orders')
     ->assertStatus(403); // or 200 if authorized
```

---

## Best Practices

1. **Always use Policies** — Don't check permissions directly in controllers; use Gate/Policy methods
2. **Data Isolation at Repository Level** — Filter data in repositories, not just in policies
3. **Use Descriptive Permission Names** — Make them self-documenting
4. **Test Each Role** — Ensure each role can only access what they should
5. **Document Custom Actions** — Any non-standard actions should be documented here
6. **Keep Permissions Granular** — Separate `view` from `view_any`, `update` from `complete`, etc.
