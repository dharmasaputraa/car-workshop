# Car Workshop System — Setup Guide

> ⚠️ **DISCLAIMER:** It is recommended to use the **Development Setup** first. The Production setup has not been thoroughly tested and may require additional adjustments.

---

## Table of Contents

- [Development Setup](#development-setup)
- [Production Setup](#production-setup)
- [Docker Services Reference](#docker-services-reference)
- [Useful Commands](#useful-commands)
- [Troubleshooting](#troubleshooting)

---

## Development Setup

This setup runs Laravel and Vite locally on your machine, while Docker manages the infrastructure services (PostgreSQL, Redis, RustFS, Mailpit).

### Prerequisites

- **PHP**: 8.3 or higher
- **Composer**: 2.x
- **Node.js**: 20.x or higher
- **Docker**: 20.10+ with Docker Compose v2
- **npm** or **pnpm** (included with Node.js)

### Step-by-Step Setup

#### 1. Clone the Repository

```bash
git clone https://github.com/dharmasaputraa/car-workshop.git
cd car-workshop
```

#### 2. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and configure the following:

```env
APP_NAME="Car Workshop"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (connects to Docker PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=15432
DB_DATABASE=car_workshop
DB_USERNAME=root
DB_PASSWORD=secret

# Redis (connects to Docker Redis)
REDIS_HOST=127.0.0.1
REDIS_PORT=16379

# Queue
QUEUE_CONNECTION=database

# Mail (uses Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=11025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@carworkshop.dev"

# Storage (uses RustFS)
FILESYSTEM_DISK=local  # Use local in dev
# FILESYSTEM_DISK=s3  # Uncomment to use RustFS

# RustFS/S3 (if using S3 disk)
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=local
AWS_ENDPOINT=http://localhost:19000
AWS_USE_PATH_STYLE_ENDPOINT=true

# JWT
JWT_TTL=1440
JWT_SECRET=  # Will be generated below
```

#### 3. Install PHP Dependencies

```bash
composer install
```

#### 4. Generate Application Keys

```bash
php artisan key:generate
php artisan jwt:secret
```

#### 5. Start Docker Infrastructure Services

```bash
docker compose up -d
```

This will start:

- **PostgreSQL 16** (port `15432`)
- **Redis** (port `16379`)
- **RustFS** (S3-compatible storage, ports `19000`/`19001`)
- **Mailpit** (Email testing, ports `11025`/`18025`)

Verify services are running:

```bash
docker compose ps
```

#### 6. Run Database Migrations

Run the database migrations:

```bash
php artisan migrate
```

If you also want to seed the database with initial data, run:

```bash
php artisan migrate --seed
```

## 7. Create Storage Link (if needed)

```bash
php artisan storage:link
```

#### 8. Install Node.js Dependencies

```bash
npm install
```

#### 9. Start Development Servers

**Option A: Using Composer script (recommended)**

```bash
composer dev
```

This runs three processes concurrently:

- Laravel API server at `http://localhost:8000`
- Queue worker
- Vite dev server (HMR) at `http://localhost:5173`

**Option B: Manual startup**

In separate terminals:

```bash
# Terminal 1: Laravel API
php artisan serve

# Terminal 2: Queue worker
php artisan queue:listen --tries=1

# Terminal 3: Vite dev server (HMR)
npm run dev
```

#### 10. Access Development Environment

| Service            | URL                            | Purpose                       |
| ------------------ | ------------------------------ | ----------------------------- |
| **API**            | http://localhost:8000          | Main API endpoint             |
| **API Docs**       | http://localhost:8000/docs/api | Scramble API documentation    |
| **Admin Panel**    | http://localhost:8000/admin    | Filament admin panel          |
| **Mailpit**        | http://localhost:18025         | Email testing dashboard       |
| **RustFS Console** | http://localhost:19001         | S3-compatible storage console |
| **RustFS API**     | http://localhost:19000         | S3-compatible storage API     |

#### 11. Run Horizon (Optional)

If you need Laravel Horizon for queue monitoring:

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml up -d horizon
```

Access Horizon at: `http://localhost:8000/horizon`

---

## Production Setup

> ⚠️ **DISCLAIMER:** Production setup has not been thoroughly tested. Proceed with caution and test thoroughly in a staging environment first.

This setup runs everything inside Docker containers, including the PHP application and Nginx web server.

### Prerequisites

- **Docker**: 20.10+ with Docker Compose v2
- **Domain name** pointing to your server
- **SMTP credentials** for sending emails (e.g., Mailtrap, SendGrid, SES)

### Step-by-Step Setup

#### 1. Clone the Repository on Server

```bash
git clone https://github.com/dharmasaputraa/car-workshop.git
cd car-workshop
```

#### 2. Configure Production Environment

Copy the production environment example:

```bash
cp .env.prod.example .env.prod
```

Edit `.env.prod` with your production settings:

```env
APP_NAME="Car Workshop"
APP_ENV=production
APP_DEBUG=false  # Must be false in production!
APP_URL=https://your-domain.com

# Database (connects to Docker PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=car_workshop_prod
DB_USERNAME=laravel
DB_PASSWORD=your_secure_password

# Session & Cache (use Redis in production)
SESSION_DRIVER=redis
CACHE_STORE=redis

# Queue (must use Redis in production)
QUEUE_CONNECTION=redis

# Redis (connects to Docker Redis)
REDIS_HOST=redis
REDIS_PORT=6379

# Mail (use real SMTP in production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="Car Workshop"

# Storage (use S3/RustFS in production)
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=your_secure_password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=car-workshop
AWS_ENDPOINT=http://rustfs:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# JWT
JWT_TTL=1440
JWT_SECRET=your_jwt_secret_here
```

**Important:** Generate a secure `APP_KEY` and `JWT_SECRET`:

```bash
# Generate APP_KEY
openssl rand -base64 32

# Generate JWT_SECRET
openssl rand -base64 32
```

#### 3. Build and Start Docker Services

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

This will build and start:

- **PostgreSQL**
- **Redis**
- **RustFS**
- **PHP-FPM (Laravel app)**
- **Nginx**
- **Horizon**
- **Pail** (Laravel logs)

Check service status:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

#### 4. Run Database Migrations

```bash
docker exec car_workshop_app php artisan migrate --force
```

#### 5. Generate Application Keys

```bash
docker exec car_workshop_app php artisan key:generate
docker exec car_workshop_app php artisan jwt:secret
```

#### 6. Create Storage Link

```bash
docker exec car_workshop_app php artisan storage:link
```

#### 7. Build Frontend Assets

```bash
docker exec car_workshop_app npm install
docker exec car_workshop_app npm run build
```

#### 8. Setup RustFS Bucket

1. Access RustFS console at `http://your-server:19001`
2. Login with:
    - Access Key: `sail`
    - Secret Key: `your_secure_password`
3. Create a bucket named `car-workshop` (or whatever you set in `AWS_BUCKET`)

#### 9. Configure Nginx (Optional)

If you need custom Nginx configuration, edit `docker/nginx/default.conf` and restart:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml restart nginx
```

#### 10. Access Production Environment

| Service         | URL                              | Purpose              |
| --------------- | -------------------------------- | -------------------- |
| **Application** | https://your-domain.com          | Main application     |
| **API**         | https://your-domain.com/api/v1   | API endpoint         |
| **API Docs**    | https://your-domain.com/docs/api | API documentation    |
| **Admin Panel** | https://your-domain.com/admin    | Filament admin panel |
| **Horizon**     | https://your-domain.com/horizon  | Queue monitoring     |

---

## Docker Services Reference

| Service             | Dev                         | Prod                     | Port                               | Notes                    |
| ------------------- | --------------------------- | ------------------------ | ---------------------------------- | ------------------------ |
| **PostgreSQL**      | Docker                      | Docker                   | 15432 (dev) / 5432 (prod internal) | Main database            |
| **Redis**           | Docker                      | Docker                   | 16379 (dev) / 6379 (prod internal) | Cache & queue            |
| **RustFS**          | Docker                      | Docker                   | 19000 (API), 19001 (Console)       | S3-compatible storage    |
| **Mailpit**         | Docker                      | —                        | 11025 (SMTP), 18025 (Dashboard)    | Email testing (dev only) |
| **PHP-FPM**         | —                           | Docker                   | 9000 (prod internal)               | Laravel application      |
| **Nginx**           | —                           | Docker                   | 80 (prod)                          | Web server               |
| **Horizon**         | Docker (override)           | Docker (prod)            | —                                  | Queue monitoring         |
| **Pail**            | Docker (override)           | Docker (prod)            | —                                  | Laravel logs             |
| **Laravel App**     | Local (`php artisan serve`) | Docker (via Nginx)       | 8000 (dev) / 80 (prod)             | API server               |
| **Frontend (Vite)** | Local (`npm run dev`)       | Docker (`npm run build`) | 5173 (dev HMR)                     | Frontend assets          |

### Key Differences Between Dev and Prod

**Development:**

- Laravel runs locally via `php artisan serve`
- Vite runs locally with HMR (Hot Module Replacement)
- Queue runs locally via `php artisan queue:listen`
- Mailpit available for email testing
- Debug mode enabled

**Production:**

- Everything runs in Docker containers
- Nginx serves the application
- Vite builds static assets (no HMR)
- Horizon manages queues
- No email testing service (uses real SMTP)
- Debug mode disabled

---

## Useful Commands

### Development

```bash
# Start/Stop Docker infrastructure
docker compose up -d
docker compose down

# View Docker logs
docker compose logs -f
docker compose logs -f pgsql
docker compose logs -f redis

# Run Laravel artisan commands
php artisan migrate
php artisan migrate:rollback
php artisan db:seed
php artisan queue:work
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run tests
php artisan test
composer test

# Code quality
composer lint           # Fix PHP code style
composer lint:check     # Check PHP code style
npm run lint           # Fix JS code style
npm run lint:check     # Check JS code style
composer ci:check      # Run all checks

# Frontend
npm run dev            # Start Vite dev server with HMR
npm run build          # Build for production
npm run build:ssr      # Build with SSR
```

### Production

```bash
# Start/Stop services
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
docker compose -f docker-compose.yml -f docker-compose.prod.yml down

# View logs
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app

# Execute commands in app container
docker exec -it car_workshop_app bash
docker exec car_workshop_app php artisan migrate --force
docker exec car_workshop_app php artisan cache:clear
docker exec car_workshop_app php artisan config:clear

# Restart specific service
docker compose -f docker-compose.yml -f docker-compose.prod.yml restart app
docker compose -f docker-compose.yml -f docker-compose.prod.yml restart nginx

# Rebuild and restart
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### Docker General

```bash
# List all containers
docker ps -a

# Remove all stopped containers
docker container prune

# Remove unused images
docker image prune -a

# Remove unused volumes (⚠️ be careful with data!)
docker volume prune

# View container resource usage
docker stats
```

---

## Troubleshooting

### Database Connection Issues

**Problem:** `SQLSTATE[08006] [7] could not connect to server: Connection refused`

**Solutions:**

1. Check if PostgreSQL is running: `docker compose ps`
2. Verify `.env` DB_HOST and DB_PORT match Docker forwarding
3. Restart PostgreSQL: `docker compose restart pgsql`
4. Check PostgreSQL logs: `docker compose logs pgsql`

### Redis Connection Issues

**Problem:** `Redis connection refused`

**Solutions:**

1. Check if Redis is running: `docker compose ps`
2. Verify `.env` REDIS_HOST and REDIS_PORT match Docker forwarding
3. Restart Redis: `docker compose restart redis`
4. Check Redis logs: `docker compose logs redis`

### Queue Jobs Not Processing

**Problem:** Jobs in queue but not executing

**Solutions:**

1. Ensure queue worker is running: `composer dev` (includes queue listener)
2. Check queue configuration in `.env`: `QUEUE_CONNECTION=database` (dev) or `redis` (prod)
3. View failed jobs: `php artisan queue:failed`
4. Retry failed jobs: `php artisan queue:retry all`

### Vite HMR Not Working

**Problem:** Frontend changes not reflecting in browser

**Solutions:**

1. Ensure Vite dev server is running: `npm run dev`
2. Check browser console for WebSocket connection errors
3. Clear browser cache and restart Vite
4. Verify `VITE_APP_NAME` in `.env` matches

### Permission Issues (Linux/Mac)

**Problem:** `Permission denied` when writing to storage

**Solutions:**

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Fix cache permissions
php artisan cache:clear
php artisan config:clear
```

### Docker Volume Issues

**Problem:** Data persists after `docker compose down`

**Solutions:**

```bash
# Remove all volumes (⚠️ deletes all data!)
docker compose down -v

# Remove specific volume
docker volume rm car_workshop_pgsql_data
```

### Horizon Not Showing Jobs

**Problem:** Horizon dashboard empty or not updating

**Solutions:**

1. Ensure Horizon is running: `docker compose ps horizon`
2. Check Horizon logs: `docker compose logs horizon`
3. Verify `QUEUE_CONNECTION=redis` in `.env`
4. Clear Horizon cache: `php artisan horizon:purge`
5. Restart Horizon: `docker compose restart horizon`

### Mailpit Not Receiving Emails

**Problem:** Emails not showing in Mailpit

**Solutions:**

1. Verify Mailpit is running: `docker compose ps mailpit`
2. Check `.env` MAIL settings:
    - `MAIL_HOST=127.0.0.1`
    - `MAIL_PORT=11025`
3. Access Mailpit at `http://localhost:18025`
4. Check if emails are being dispatched: `php artisan queue:work`

### Memory Issues

**Problem:** Container out of memory errors

**Solutions:**

1. Increase Docker memory limit in Docker Desktop settings
2. Restart Docker daemon
3. Reduce PHP memory limit in `.env`:
    ```env
    MEMORY_LIMIT=256M
    ```

### Production Deployment Issues

**Problem:** 502 Bad Gateway after deployment

**Solutions:**

1. Check if app container is healthy: `docker compose ps`
2. Check Nginx logs: `docker compose logs nginx`
3. Verify `docker/nginx/default.conf` configuration
4. Ensure PHP-FPM is running: `docker exec car_workshop_app php -v`

**Problem:** Static assets (CSS/JS) not loading

**Solutions:**

1. Ensure frontend assets were built: `docker exec car_workshop_app npm run build`
2. Check if `public/build` directory exists
3. Verify `APP_URL` in `.env.prod` matches your domain
4. Clear cache: `docker exec car_workshop_app php artisan cache:clear`

---

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Filament Documentation](https://filamentphp.com/docs)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)

---

**Last Updated:** April 15, 2026
