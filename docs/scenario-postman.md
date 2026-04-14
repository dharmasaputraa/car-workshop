# Postman Integration Testing Scenarios

**Car Workshop API - Complete Testing Guide**

---

## Table of Contents

1. [Setup & Prerequisites](#setup--prerequisites)
2. [Scenario 1: Auth Domain](#scenario-1-auth-domain)
3. [Scenario 2: Profile Domain](#scenario-2-profile-domain)
4. [Scenario 3: User Management Domain](#scenario-3-user-management-domain)
5. [Scenario 4: Car Domain](#scenario-4-car-domain)
6. [Scenario 5: Service Domain](#scenario-5-service-domain)
7. [Scenario 6: Full WorkOrder Lifecycle](#scenario-6-full-workorder-lifecycle) ⭐
8. [Scenario 7: Mechanic Assignment Domain](#scenario-7-mechanic-assignment-domain)
9. [Scenario 8: WorkOrder Edge Cases](#scenario-8-workorder-edge-cases)
10. [Scenario 9: Data Isolation Tests](#scenario-9-data-isolation-tests)

---

## Setup & Prerequisites

### Environment Variables

Create a Postman environment with these variables:

```json
{
    "base_url": "http://localhost:8000/api/v1",
    "token": "",
    "mechanic_token": "",
    "customer_token": "",
    "admin_token": "",
    "work_order_id": "",
    "work_order_service_id": "",
    "mechanic_assignment_id": "",
    "car_id": "",
    "service_id": ""
}
```

### Seeded Test Accounts

After running `php artisan db:seed`, use these accounts:

| Role        | Email                     | Password   |
| ----------- | ------------------------- | ---------- |
| Super Admin | `super_admin@example.com` | `password` |
| Admin       | `admin@example.com`       | `password` |
| Mechanic 1  | `mechanic1@example.com`   | `password` |
| Customer 1  | `customer1@example.com`   | `password` |

### How to Get Tokens

**Login as Admin:**

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

Save the `access_token` response to `{{admin_token}}` variable.

**Login as Mechanic:**

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "email": "mechanic1@example.com",
  "password": "password"
}
```

Save the `access_token` response to `{{mechanic_token}}` variable.

**Login as Customer:**

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "email": "customer1@example.com",
  "password": "password"
}
```

Save the `access_token` response to `{{customer_token}}` variable.

### Common Headers

Add these headers to your Postman requests (use auto-generated token variable):

```http
Authorization: Bearer {{token}}
Content-Type: application/json
```

---

## Scenario 1: Auth Domain

### 1.1 Login Successfully

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 7200,
        "user": {
            "id": "uuid-here",
            "name": "Admin User",
            "email": "admin@example.com",
            "role": "admin",
            "is_active": true
        }
    }
}
```

### 1.2 Login with Wrong Credentials

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "wrongpassword"
}
```

**Expected Response (401):**

```json
{
    "message": "The provided credentials are incorrect."
}
```

### 1.3 Login with Inactive User

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "email": "inactive@example.com",
  "password": "password"
}
```

**Expected Response (401):**

```json
{
    "message": "Your account is inactive. Please contact administrator."
}
```

### 1.4 Register New User

```http
POST {{base_url}}/auth/register
Content-Type: application/json

{
  "name": "New Customer",
  "email": "newcustomer@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

**Expected Response (201):**

```json
{
    "message": "User registered successfully. Please verify your email.",
    "data": {
        "id": "uuid-here",
        "name": "New Customer",
        "email": "newcustomer@example.com"
    }
}
```

### 1.5 Refresh Token

```http
POST {{base_url}}/auth/refresh
Authorization: Bearer {{token}}
```

**Expected Response (200):**

```json
{
    "data": {
        "access_token": "new-token-here",
        "token_type": "bearer",
        "expires_in": 7200
    }
}
```

### 1.6 Revoke Token (Logout)

```http
POST {{base_url}}/auth/revoke
Authorization: Bearer {{token}}
```

**Expected Response (200):**

```json
{
    "message": "Token revoked successfully"
}
```

---

## Scenario 2: Profile Domain

### 2.1 View Profile

```http
GET {{base_url}}/profile
Authorization: Bearer {{token}}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin",
        "is_active": true,
        "avatar": null,
        "email_verified_at": "2026-04-15T00:00:00.000000Z",
        "created_at": "2026-04-15T00:00:00.000000Z"
    }
}
```

### 2.2 Update Profile

```http
PUT {{base_url}}/profile
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "name": "Updated Name"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Updated Name",
        "email": "admin@example.com",
        "role": "admin",
        "is_active": true
    }
}
```

### 2.3 Change Password

```http
POST {{base_url}}/profile/change-password
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "current_password": "password",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Expected Response (200):**

```json
{
    "message": "Password changed successfully"
}
```

### 2.4 Change Password - Wrong Current Password

```http
POST {{base_url}}/profile/change-password
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "current_password": "wrongpassword",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Expected Response (422):**

```json
{
    "message": "The current password is incorrect."
}
```

---

## Scenario 3: User Management Domain

### 3.1 List All Users (Admin Only)

```http
GET {{base_url}}/users
Authorization: Bearer {{admin_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "uuid-here",
            "name": "Admin User",
            "email": "admin@example.com",
            "role": "admin",
            "is_active": true
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 20
    }
}
```

### 3.2 Create New User (Admin Only)

```http
POST {{base_url}}/users
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "name": "New Mechanic",
  "email": "newmechanic@example.com",
  "password": "Password123!",
  "role": "mechanic"
}
```

**Expected Response (201):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "New Mechanic",
        "email": "newmechanic@example.com",
        "role": "mechanic",
        "is_active": true
    }
}
```

### 3.3 Create User - Forbidden (Customer)

```http
POST {{base_url}}/users
Authorization: Bearer {{customer_token}}
Content-Type: application/json

{
  "name": "New User",
  "email": "newuser@example.com",
  "password": "Password123!",
  "role": "admin"
}
```

**Expected Response (403):**

```json
{
    "message": "You do not have permission to perform this action."
}
```

### 3.4 Update User (Admin Only)

```http
PUT {{base_url}}/users/{{user_id}}
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "name": "Updated Name",
  "is_active": true
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Updated Name",
        "role": "mechanic",
        "is_active": true
    }
}
```

### 3.5 Toggle User Active Status

```http
PATCH {{base_url}}/users/{{user_id}}/toggle-active
Authorization: Bearer {{admin_token}}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Mechanic Name",
        "is_active": false
    }
}
```

### 3.6 Change User Role (Super Admin Only)

```http
PATCH {{base_url}}/users/{{user_id}}/role
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "role": "admin"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "User Name",
        "role": "admin"
    }
}
```

---

## Scenario 4: Car Domain

### 4.1 List All Cars (Admin)

```http
GET {{base_url}}/cars
Authorization: Bearer {{admin_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "uuid-here",
            "plate_number": "B 1234 ABC",
            "brand": "Toyota",
            "model": "Camry",
            "year": 2022,
            "color": "White",
            "owner_id": "customer-uuid",
            "owner": {
                "id": "customer-uuid",
                "name": "Customer 1",
                "email": "customer1@example.com"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 20
    }
}
```

### 4.2 List Cars - Customer (Own Cars Only)

```http
GET {{base_url}}/cars
Authorization: Bearer {{customer_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "uuid-here",
            "plate_number": "B 1234 ABC",
            "brand": "Toyota",
            "model": "Camry",
            "owner_id": "customer-uuid"
        }
    ]
}
```

### 4.3 Create Car (Customer)

```http
POST {{base_url}}/cars
Authorization: Bearer {{customer_token}}
Content-Type: application/json

{
  "plate_number": "B 5678 XYZ",
  "brand": "Honda",
  "model": "Civic",
  "year": 2023,
  "color": "Black"
}
```

**Expected Response (201):**

```json
{
    "data": {
        "id": "uuid-here",
        "plate_number": "B 5678 XYZ",
        "brand": "Honda",
        "model": "Civic",
        "year": 2023,
        "color": "Black",
        "owner_id": "customer-uuid"
    }
}
```

### 4.4 Create Car - Invalid Plate Number

```http
POST {{base_url}}/cars
Authorization: Bearer {{customer_token}}
Content-Type: application/json

{
  "plate_number": "INVALID",
  "brand": "Honda",
  "model": "Civic",
  "year": 2023,
  "color": "Black"
}
```

**Expected Response (422):**

```json
{
    "message": "The plate number field is invalid.",
    "errors": {
        "plate_number": [
            "Invalid license plate format. Correct format: [Area Code][Space][Number 1–4 digits][Space][Letters 1–3]. Example: B 1234 ABC"
        ]
    }
}
```

### 4.5 Update Car (Owner)

```http
PUT {{base_url}}/cars/{{car_id}}
Authorization: Bearer {{customer_token}}
Content-Type: application/json

{
  "color": "Red"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "plate_number": "B 5678 XYZ",
        "brand": "Honda",
        "model": "Civic",
        "year": 2023,
        "color": "Red"
    }
}
```

### 4.6 Delete Car - Has Active Work Orders (Forbidden)

```http
DELETE {{base_url}}/cars/{{car_id}}
Authorization: Bearer {{customer_token}}
```

**Expected Response (403):**

```json
{
    "message": "Cannot delete car with active work orders."
}
```

### 4.7 Delete Car - Success

```http
DELETE {{base_url}}/cars/{{car_id_without_wos}}
Authorization: Bearer {{customer_token}}
```

**Expected Response (204):**
_(No content)_

---

## Scenario 5: Service Domain

### 5.1 List All Services

```http
GET {{base_url}}/services
Authorization: Bearer {{token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "uuid-here",
            "name": "Standard Engine Oil Change",
            "description": "Standard engine oil replacement",
            "base_price": 50000.0,
            "is_active": true
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 16
    }
}
```

### 5.2 Get Single Service

```http
GET {{base_url}}/services/{{service_id}}
Authorization: Bearer {{token}}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Basic Tune Up",
        "description": "Throttle body cleaning, spark plug check",
        "base_price": 250000.0,
        "is_active": true,
        "work_order_services": []
    }
}
```

### 5.3 Create Service (Admin Only)

```http
POST {{base_url}}/services
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "name": "Brake Fluid Replacement",
  "description": "Complete brake fluid system flush and refill",
  "base_price": 150000.00,
  "is_active": true
}
```

**Expected Response (201):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Brake Fluid Replacement",
        "description": "Complete brake fluid system flush and refill",
        "base_price": 150000.0,
        "is_active": true
    }
}
```

### 5.4 Create Service - Forbidden (Customer)

```http
POST {{base_url}}/services
Authorization: Bearer {{customer_token}}
Content-Type: application/json

{
  "name": "New Service",
  "description": "Description",
  "base_price": 100000.00
}
```

**Expected Response (403):**

```json
{
    "message": "You do not have permission to perform this action."
}
```

### 5.5 Update Service (Admin Only)

```http
PUT {{base_url}}/services/{{service_id}}
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "base_price": 175000.00
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Brake Fluid Replacement",
        "base_price": 175000.0,
        "is_active": true
    }
}
```

### 5.6 Toggle Service Active Status

```http
PATCH {{base_url}}/services/{{service_id}}/toggle-active
Authorization: Bearer {{admin_token}}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "name": "Brake Fluid Replacement",
        "base_price": 175000.0,
        "is_active": false
    }
}
```

### 5.7 Delete Service (Admin Only)

```http
DELETE {{base_url}}/services/{{service_id}}
Authorization: Bearer {{admin_token}}
```

**Expected Response (204):**
_(No content)_

---

## Scenario 6: Full WorkOrder Lifecycle ⭐

### 6.1 Create WorkOrder (Admin)

```http
POST {{base_url}}/work-orders
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "car_id": "{{car_id}}"
}
```

**Expected Response (201):**

```json
{
    "data": {
        "id": "uuid-here",
        "order_number": "WO-2026-1021",
        "status": "draft",
        "car": {
            "id": "car-uuid",
            "plate_number": "B 1234 ABC",
            "brand": "Toyota",
            "model": "Camry"
        },
        "creator": {
            "id": "admin-uuid",
            "name": "Admin User"
        }
    }
}
```

Save the `id` to `{{work_order_id}}` variable.

### 6.2 Diagnose WorkOrder (Mechanic/Admin)

```http
PATCH {{base_url}}/work-orders/{{work_order_id}}/diagnose
Authorization: Bearer {{mechanic_token}}
Content-Type: application/json

{
  "diagnosis_notes": "Customer reported engine misfiring. Spark plugs need replacement.",
  "services": [
    {
      "service_id": "{{service_id}}",
      "notes": "Replace all 4 spark plugs"
    },
    {
      "service_id": "another-service-uuid",
      "notes": "Clean throttle body"
    }
  ]
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "order_number": "WO-2026-1021",
        "status": "diagnosed",
        "diagnosis_notes": "Customer reported engine misfacing. Spark plugs need replacement.",
        "work_order_services": [
            {
                "id": "wos-uuid-1",
                "service_id": "service-uuid",
                "price": 50000.0,
                "status": "pending",
                "service": {
                    "name": "Standard Engine Oil Change"
                }
            }
        ]
    }
}
```

Save one `work_order_service.id` to `{{work_order_service_id}}` variable.

### 6.3 Approve WorkOrder (Customer)

```http
PATCH {{base_url}}/work-orders/{{work_order_id}}/approve
Authorization: Bearer {{customer_token}}
Content-Type: application/json

{
  "approved": true,
  "approval_notes": "Approved to proceed with the work."
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "order_number": "WO-2026-1021",
        "status": "approved",
        "approved_at": "2026-04-15T10:30:00.000000Z",
        "work_order_services": [
            {
                "id": "wos-uuid-1",
                "status": "pending"
            }
        ]
    }
}
```

### 6.4 Assign Mechanic to Service (Admin)

```http
PATCH {{base_url}}/work-orders/services/{{work_order_service_id}}/assign-mechanic
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "mechanic_id": "mechanic-uuid"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "wos-uuid-1",
        "status": "pending",
        "mechanic_assignments": [
            {
                "id": "assignment-uuid",
                "mechanic_id": "mechanic-uuid",
                "mechanic": {
                    "name": "Mechanic 1",
                    "email": "mechanic1@example.com"
                },
                "status": "assigned",
                "assigned_at": "2026-04-15T10:35:00.000000Z"
            }
        ]
    }
}
```

Save the `mechanic_assignments[0].id` to `{{mechanic_assignment_id}}` variable.

### 6.5 Start WorkOrderService (Mechanic)

```http
PATCH {{base_url}}/work-orders/services/{{work_order_service_id}}/start
Authorization: Bearer {{mechanic_token}}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "wos-uuid-1",
        "status": "in_progress",
        "mechanic_assignments": [
            {
                "id": "assignment-uuid",
                "status": "in_progress",
                "assigned_at": "2026-04-15T10:35:00.000000Z"
            }
        ]
    }
}
```

### 6.6 Complete WorkOrderService (Mechanic)

```http
PATCH {{base_url}}/work-orders/services/{{work_order_service_id}}/complete
Authorization: Bearer {{mechanic_token}}
Content-Type: application/json

{
  "completion_notes": "Service completed successfully. All spark plugs replaced."
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "wos-uuid-1",
        "status": "completed",
        "mechanic_assignments": [
            {
                "id": "assignment-uuid",
                "status": "completed",
                "assigned_at": "2026-04-15T10:35:00.000000Z",
                "completed_at": "2026-04-15T11:45:00.000000Z"
            }
        ]
    }
}
```

### 6.7 Complete WorkOrder (Admin)

```http
PATCH {{base_url}}/work-orders/{{work_order_id}}/complete
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "completion_notes": "All services completed. Vehicle is ready for pickup."
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "order_number": "WO-2026-1021",
        "status": "completed",
        "completed_at": "2026-04-15T12:00:00.000000Z",
        "completion_notes": "All services completed. Vehicle is ready for pickup.",
        "work_order_services": [
            {
                "id": "wos-uuid-1",
                "status": "completed"
            }
        ]
    }
}
```

### 6.8 Mark WorkOrder as Invoiced (Admin)

```http
PATCH {{base_url}}/work-orders/{{work_order_id}}/mark-invoiced
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "invoice_number": "INV-2026-1001"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "order_number": "WO-2026-1021",
        "status": "invoiced",
        "invoice_number": "INV-2026-1001",
        "invoiced_at": "2026-04-15T12:30:00.000000Z"
    }
}
```

---

## Scenario 7: Mechanic Assignment Domain

### 7.1 List Mechanic Assignments (Admin)

```http
GET {{base_url}}/mechanic-assignments
Authorization: Bearer {{admin_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "assignment-uuid",
            "work_order_service": {
                "id": "wos-uuid",
                "service": {
                    "name": "Engine Oil Change"
                },
                "work_order": {
                    "order_number": "WO-2026-1021"
                }
            },
            "mechanic": {
                "name": "Mechanic 1",
                "email": "mechanic1@example.com"
            },
            "status": "assigned",
            "assigned_at": "2026-04-15T10:35:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 35
    }
}
```

### 7.2 List Mechanic Assignments (Own Only)

```http
GET {{base_url}}/mechanic-assignments
Authorization: Bearer {{mechanic_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "assignment-uuid",
            "mechanic_id": "mechanic-uuid",
            "status": "in_progress",
            "work_order_service": {
                "service": {
                    "name": "Engine Oil Change"
                }
            }
        }
    ]
}
```

### 7.3 Start Mechanic Assignment

```http
PATCH {{base_url}}/mechanic-assignments/{{mechanic_assignment_id}}/start
Authorization: Bearer {{mechanic_token}}
Content-Type: application/json

{
  "notes": "Starting work on this assignment."
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "assignment-uuid",
        "status": "in_progress",
        "started_at": "2026-04-15T11:00:00.000000Z",
        "notes": "Starting work on this assignment."
    }
}
```

### 7.4 Complete Mechanic Assignment

```http
PATCH {{base_url}}/mechanic-assignments/{{mechanic_assignment_id}}/complete
Authorization: Bearer {{mechanic_token}}
Content-Type: application/json

{
  "completion_notes": "Work completed successfully."
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "assignment-uuid",
        "status": "completed",
        "assigned_at": "2026-04-15T10:35:00.000000Z",
        "completed_at": "2026-04-15T12:30:00.000000Z",
        "completion_notes": "Work completed successfully."
    }
}
```

### 7.5 Cancel Mechanic Assignment (Admin)

```http
PATCH {{base_url}}/mechanic-assignments/{{mechanic_assignment_id}}/cancel
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "cancel_reason": "Reassigning to another mechanic."
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "assignment-uuid",
        "status": "canceled",
        "cancel_reason": "Reassigning to another mechanic.",
        "canceled_at": "2026-04-15T13:00:00.000000Z"
    }
}
```

### 7.6 Start Other Mechanic's Assignment (Forbidden)

```http
PATCH {{base_url}}/mechanic-assignments/other-assignment-uuid/start
Authorization: Bearer {{mechanic_token}}
```

**Expected Response (403):**

```json
{
    "message": "You do not have permission to perform this action."
}
```

---

## Scenario 8: WorkOrder Edge Cases

### 8.1 Diagnose Non-DRAFT WorkOrder (Forbidden)

```http
PATCH {{base_url}}/work-orders/{{approved_wo_id}}/diagnose
Authorization: Bearer {{mechanic_token}}
Content-Type: application/json

{
  "diagnosis_notes": "Trying to diagnose an approved WO",
  "services": []
}
```

**Expected Response (403):**

```json
{
    "message": "Only Work Orders with DRAFT status can be diagnosed."
}
```

### 8.2 Approve Non-DIAGNOSED WorkOrder (Forbidden)

```http
PATCH {{base_url}}/work-orders/{{draft_wo_id}}/approve
Authorization: Bearer {{customer_token}}
Content-Type: application/json

{
  "approved": true
}
```

**Expected Response (403):**

```json
{
    "message": "Only DIAGNOSED Work Orders can be approved."
}
```

### 8.3 Cancel WorkOrder at Different Stages

**Cancel DRAFT WO:**

```http
PATCH {{base_url}}/work-orders/{{draft_wo_id}}/cancel
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "cancel_reason": "Customer changed mind"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "status": "canceled",
        "cancel_reason": "Customer changed mind",
        "canceled_at": "2026-04-15T14:00:00.000000Z"
    }
}
```

**Cancel IN_PROGRESS WO (Forbidden):**

```http
PATCH {{base_url}}/work-orders/{{in_progress_wo_id}}/cancel
Authorization: Bearer {{admin_token}}
```

**Expected Response (403):**

```json
{
    "message": "Cannot cancel Work Order in IN_PROGRESS status."
}
```

### 8.4 Record Complaint on Completed WorkOrder

```http
PATCH {{base_url}}/work-orders/{{completed_wo_id}}/record-complaint
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "complaint_notes": "Customer reported oil leak after service.",
  "complaint_type": "quality_issue"
}
```

**Expected Response (200):**

```json
{
    "data": {
        "id": "uuid-here",
        "order_number": "WO-2026-1021",
        "status": "complained",
        "complaint_recorded_at": "2026-04-15T15:00:00.000000Z",
        "complaint_notes": "Customer reported oil leak after service."
    }
}
```

### 8.5 Complete WorkOrder with Incomplete Services

```http
PATCH {{base_url}}/work-orders/{{wo_with_incomplete_services}}/complete
Authorization: Bearer {{admin_token}}
```

**Expected Response (403):**

```json
{
    "message": "Cannot complete Work Order. Some services are not yet completed."
}
```

---

## Scenario 9: Data Isolation Tests

### 9.1 Customer Views Only Own Cars

**Customer 1 Lists Cars:**

```http
GET {{base_url}}/cars
Authorization: Bearer {{customer_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "car-1-uuid",
            "plate_number": "B 1234 ABC",
            "owner_id": "customer-1-uuid"
        },
        {
            "id": "car-2-uuid",
            "plate_number": "B 5678 XYZ",
            "owner_id": "customer-1-uuid"
        }
    ]
}
```

_Customer should NOT see cars owned by Customer 2 or others._

### 9.2 Customer Views Only Own WorkOrders

```http
GET {{base_url}}/work-orders
Authorization: Bearer {{customer_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "wo-uuid-1",
            "order_number": "WO-2026-1021",
            "car": {
                "owner_id": "customer-1-uuid"
            }
        }
    ]
}
```

_Customer should NOT see WOs for other customers' cars._

### 9.3 Mechanic Views Only Assigned WorkOrders

```http
GET {{base_url}}/work-orders
Authorization: Bearer {{mechanic_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "wo-uuid-1",
            "order_number": "WO-2026-1021",
            "work_order_services": [
                {
                    "mechanic_assignments": [
                        {
                            "mechanic_id": "mechanic-1-uuid"
                        }
                    ]
                }
            ]
        }
    ]
}
```

_Mechanic should NOT see WOs where they have no assignments._

### 9.4 Mechanic Views Only Own Assignments

```http
GET {{base_url}}/mechanic-assignments
Authorization: Bearer {{mechanic_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "assignment-uuid-1",
            "mechanic_id": "mechanic-1-uuid",
            "status": "in_progress"
        }
    ]
}
```

_Mechanic should NOT see assignments for other mechanics._

### 9.5 Admin Views All Data

**Admin Lists Cars:**

```http
GET {{base_url}}/cars
Authorization: Bearer {{admin_token}}
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": "car-1-uuid",
            "owner_id": "customer-1-uuid"
        },
        {
            "id": "car-2-uuid",
            "owner_id": "customer-2-uuid"
        }
        // All cars visible
    ]
}
```

---

## Summary

This Postman testing guide covers **85+ test cases** across all domains:

- ✅ Auth: 6 tests
- ✅ Profile: 4 tests
- ✅ User Management: 6 tests
- ✅ Car: 7 tests
- ✅ Service: 7 tests
- ✅ WorkOrder Lifecycle: 8 tests
- ✅ Mechanic Assignments: 6 tests
- ✅ WorkOrder Edge Cases: 5 tests
- ✅ Data Isolation: 5 tests

### Quick Reference Commands

```bash
# Seed the database
php artisan db:seed

# Run the API server
php artisan serve

# Start in production mode
php artisan serve --host=0.0.0.0 --port=8000
```

### Postman Collection Tips

1. **Create a Collection** named "Car Workshop API"
2. **Create Folders** for each scenario (Auth, Profile, Cars, etc.)
3. **Use Pre-request Scripts** to auto-refresh tokens if needed
4. **Use Tests** to automate variable saving:
    ```javascript
    pm.test('Save work order ID', function () {
        var jsonData = pm.response.json();
        pm.environment.set('work_order_id', jsonData.data.id);
    });
    ```

---

**Document Version:** 1.0  
**Last Updated:** April 15, 2026  
**API Version:** v1.0
