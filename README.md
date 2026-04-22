# Car Workshop Management System

A modern, full-stack workshop management system built with Laravel, featuring work order tracking, mechanic assignments, complaint handling, and invoicing.

[![Laravel](https://img.shields.io/badge/Laravel-13.0-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql)](https://www.postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7.2-DC382D?style=for-the-badge&logo=redis)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-24.0-2496ED?style=for-the-badge&logo=docker)](https://docker.com)
[![Postman](https://img.shields.io/badge/Postman-FF6C37?style=for-the-badge&logo=postman)](https://documenter.getpostman.com/view/54024615/2sBXqCR4kU)

## About

This system helps automotive workshops manage their entire workflow from customer intake to invoicing. It provides role-based access control for administrators, mechanics, and customers, with comprehensive state tracking for work orders, service items, and complaints.

## Features

### Core Functionality

- JWT Authentication with role-based access control
- Car management with full CRUD operations
- Service catalog with pricing
- Work order lifecycle management
- Mechanic assignment per service item
- Email notifications for status changes
- Complaint handling with rework workflow
- Invoice generation and management

### Advanced Features

- State machine transitions
- Data isolation per role
- Real-time progress tracking
- Filament admin panel
- Unit and feature testing

## Architecture

This project follows a hybrid architecture:

- Repository Pattern
- Action Classes (per use case)
- Service Layer
- DTOs
- Event/Listener

Read more:

- [Architecture Documentation (ERD & State Mechine Diagram)](docs/architecture-en.md)
- [Authorization (Policy and Data Isolation)](docs/authorization.md)
- [API Reference](docs/architecture-en.md#5-api-endpoints)
- [Postman API Documentation](https://documenter.getpostman.com/view/54024615/2sBXqCR4kU)
- [Setup Guide](docs/setup.md)

---

## Tech Stack

| Layer         | Technology             |
| ------------- | ---------------------- |
| Backend       | Laravel 13             |
| Language      | PHP 8.4                |
| Database      | PostgreSQL 16          |
| Cache / Queue | Redis 7.2              |
| Auth          | JWT (tymon/jwt-auth)   |
| Testing       | PHPUnit                |
| Code Style    | Pint, ESLint, Prettier |
| Container     | Docker                 |

---

## Installation (Development)

This project uses a hybrid development setup:

- Laravel and Vite run locally
- Infrastructure runs via Docker

See full guide: [docs/setup.md](docs/setup.md)

---

### Prerequisites

- PHP 8.4+
- Composer 2.x
- Node.js 24+
- Docker with Docker Compose

---

### Setup Steps

#### 1. Clone

```bash
git clone https://github.com/dharmasaputraa/car-workshop.git
cd car-workshop
```

#### 2. Environment

```bash
cp .env.example .env
```

Update:

```env
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=15432

REDIS_HOST=127.0.0.1
REDIS_PORT=16379

MAIL_HOST=127.0.0.1
MAIL_PORT=11025
```

#### 3. Install Dependencies

```bash
composer install
npm install
```

#### 4. Generate Keys

```bash
php artisan key:generate
php artisan jwt:secret
```

#### 5. Start Infrastructure

```bash
docker compose up -d
```

#### 6. Run Migrations

```bash
php artisan migrate --seed
```

#### 7. Storage Link

```bash
php artisan storage:link
```

#### 8. Run App

```bash
composer dev
```

---

### Access

| Service     | URL                                                        |
| ----------- | ---------------------------------------------------------- |
| API         | http://localhost:8000                                      |
| Admin Panel | http://localhost:8000/admin                                |
| API Docs    | http://localhost:8000/docs/api                             |
| Postman     | https://documenter.getpostman.com/view/54024615/2sBXqCR4kU |
| Mailpit     | http://localhost:18025                                     |
| Storage UI  | http://localhost:19001                                     |

---

### Testing

#### Run All Tests

```bash
php artisan test
```

#### Run Per Test Suite (Recommended to Avoid Memory Errors)

Run only Unit tests:

```bash
php artisan test --testsuite=Unit
# Or using PHPUnit directly:
vendor/bin/phpunit --testsuite=Unit
```

Run only Feature tests:

```bash
php artisan test --testsuite=Feature
# Or using PHPUnit directly:
vendor/bin/phpunit --testsuite=Feature
```

#### Run Per Test Directory/Group

Run specific test directories:

```bash
# WorkOrders actions
php artisan test tests/Unit/Actions/WorkOrders/

# Complaints actions
php artisan test tests/Unit/Actions/Complaints/

# Invoices actions
php artisan test tests/Unit/Actions/Invoices/

# Repositories
php artisan test tests/Unit/Repositories/

# Services
php artisan test tests/Unit/Services/

# Feature/Api tests
php artisan test tests/Feature/Api/
```

#### Run a Single Test File

```bash
php artisan test tests/Unit/Actions/WorkOrders/CancelWorkOrderActionTest.php

# Or using PHPUnit directly:
vendor/bin/phpunit tests/Unit/Actions/WorkOrders/CancelWorkOrderActionTest.php
```

#### Run a Specific Test Method

```bash
php artisan test --filter testCancelWorkOrder
```

#### Memory Optimization Tips

The project uses SQLite in-memory (`:memory:`) database which is recreated per test. To avoid memory errors:

1. **Run tests per suite** instead of all at once
2. **Run tests per directory** for large test groups
3. **If paratest is installed**, run tests in parallel processes:
    ```bash
    vendor/bin/paratest --testsuite=Unit --processes=4
    ```
4. **Clean up test artifacts**:
    ```bash
    php artisan test --refresh-database
    ```

---

## Project Structure

```
app/
database/
docs/
routes/
tests/
```

See full structure: [docs/architecture-en.md#2-folder-structure](docs/architecture-en.md#2-folder-structure)

---
