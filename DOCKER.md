# Docker Deployment Guide

This guide covers the automated Docker image building and deployment to Docker Hub, plus deployment instructions for Unraid.

## Table of Contents
- [GitHub Setup](#github-setup)
- [GitHub Secrets Configuration]
- [How It Works](#how-it-works)
- [Unraid Deployment](#unraid-deployment)
- [Environment Variables](#environment-variables)
- [Troubleshooting](#troubleshooting)

## GitHub Setup

### Required GitHub Secrets

You need to configure the following secrets in your GitHub repository:

1. Go to your repository on GitHub
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret** and add the following:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `DOCKERHUB_USERNAME` | Your Docker Hub username | `yourname` |
| `DOCKERHUB_TOKEN` | Docker Hub access token (NOT your password) | Generate from Docker Hub |
| `DOCKERHUB_REPOSITORY` | Repository name on Docker Hub | `laravel-app` |

### Creating a Docker Hub Access Token

1. Log in to [Docker Hub](https://hub.docker.com/)
2. Click on your username → **Account Settings**
3. Go to **Security** → **New Access Token**
4. Give it a description (e.g., "GitHub Actions")
5. Set permissions to **Read & Write**
6. Copy the token (you won't be able to see it again!)
7. Add it as `DOCKERHUB_TOKEN` in GitHub secrets

### Creating a Docker Hub Repository

1. Log in to Docker Hub
2. Click **Create Repository**
3. Name it (e.g., `laravel-app`)
4. Set visibility (Public or Private)
5. Click **Create**

## How It Works

The GitHub Actions workflow (`.github/workflows/docker-release.yml`) automatically:

1. **Triggers on Release**: When you create a new release on GitHub
2. **Builds the Image**: Creates a production-optimized Docker image
3. **Tags Appropriately**:
   - Version tags (e.g., `1.0.0`, `1.0`, `1`)
   - `latest` tag for the default branch
4. **Pushes to Docker Hub**: Uploads to your Docker Hub repository
5. **Multi-platform**: Builds for both `linux/amd64` and `linux/arm64`

### Creating a Release

To trigger the workflow and push a new image:

```bash
# Method 1: GitHub UI
# 1. Go to your repo → Releases → Create a new release
# 2. Create a new tag (e.g., v1.0.0)
# 3. Fill in release notes
# 4. Click "Publish release"

# Method 2: Command line
git tag v1.0.0
git push origin v1.0.0
# Then create the release on GitHub UI
```

### Manual Trigger

You can also manually trigger the workflow:

1. Go to **Actions** → **Build and Push Docker Image**
2. Click **Run workflow**
3. Enter a custom tag name
4. Click **Run workflow**

## Unraid Deployment

### Prerequisites

1. Docker Hub image pushed (via GitHub Actions)
2. Nginx Proxy Manager set up on Unraid
3. Access to Unraid terminal or SSH

### Deployment Steps

#### 1. Create Environment File

Create a `.env.prod` file with your production configuration:

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=https://yourdomain.com
APP_PORT=8080

# Docker Hub
DOCKERHUB_USERNAME=yourname
DOCKERHUB_REPOSITORY=laravel-app

# Database
DB_CONNECTION=mysql
DB_PORT=3306
DB_DATABASE=laravel_production
DB_USERNAME=laravel_user
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_ROOT_PASSWORD=STRONG_ROOT_PASSWORD_HERE

# Redis (no password needed for local network)
REDIS_HOST=redis
REDIS_PORT=6379

# Cache & Sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (configure as needed)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

#### 2. Deploy with Docker Compose

```bash
# SSH into your Unraid server
ssh root@your-unraid-ip

# Create a directory for your app
mkdir -p /mnt/user/appdata/laravel-app
cd /mnt/user/appdata/laravel-app

# Copy the docker-compose.prod.yml and .env.prod files here
# Then rename .env.prod to .env
mv .env.prod .env

# Pull the latest image and start services
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d

# Run initial setup (first time only)
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
docker compose -f docker-compose.prod.yml exec app php artisan storage:link
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

#### 3. Configure Nginx Proxy Manager

1. Open Nginx Proxy Manager
2. Add **Proxy Host**:
   - **Domain Names**: `yourdomain.com`
   - **Scheme**: `http`
   - **Forward Hostname/IP**: Your Unraid server IP
   - **Forward Port**: `8080` (or whatever you set as `APP_PORT`)
   - **SSL**: Enable SSL, request Let's Encrypt certificate
3. Save

### Updating the Application

When you release a new version:

```bash
# Pull latest image
docker compose -f docker-compose.prod.yml pull

# Recreate containers
docker compose -f docker-compose.prod.yml up -d

# Run migrations (if any)
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear and rebuild cache
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## Environment Variables

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Laravel application key | Generate with `php artisan key:generate --show` |
| `APP_URL` | Your application URL | `https://yourdomain.com` |
| `DB_DATABASE` | Database name | `laravel_production` |
| `DB_USERNAME` | Database user | `laravel_user` |
| `DB_PASSWORD` | Database password | Use a strong password |
| `DB_ROOT_PASSWORD` | MySQL root password | Use a strong password |

### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_PORT` | External port mapping | `8080` |
| `APP_DEBUG` | Enable debug mode | `false` |
| `MAIL_MAILER` | Mail driver | `smtp` |

## Container Architecture

The production setup includes:

### 1. App Container
- **Base**: PHP 8.4 FPM Alpine
- **Web Server**: Nginx
- **Process Manager**: Supervisor
- **Services**:
  - Nginx (port 80)
  - PHP-FPM
  - Queue workers (2 processes)

### 2. Database Container
- **Image**: MySQL 8.0
- **Persistent Storage**: `db-data` volume

### 3. Redis Container
- **Image**: Redis 7 Alpine
- **Persistent Storage**: `redis-data` volume
- **Usage**: Cache, sessions, queues

## Troubleshooting

### View Container Logs

```bash
# All containers
docker compose -f docker-compose.prod.yml logs -f

# Specific container
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f db
docker compose -f docker-compose.prod.yml logs -f redis
```

### Access Container Shell

```bash
docker compose -f docker-compose.prod.yml exec app sh
```

### Clear All Caches

```bash
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.prod.yml exec app php artisan route:clear
docker compose -f docker-compose.prod.yml exec app php artisan view:clear
```

### Check Container Health

```bash
docker compose -f docker-compose.prod.yml ps
```

### Rebuild from Scratch

```bash
# Stop and remove everything
docker compose -f docker-compose.prod.yml down -v

# Pull fresh images
docker compose -f docker-compose.prod.yml pull

# Start again
docker compose -f docker-compose.prod.yml up -d
```

### Permission Issues

If you encounter permission errors:

```bash
docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker compose -f docker-compose.prod.yml exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

## Advanced Configuration

### Using PostgreSQL Instead of MySQL

Update `.env`:

```env
DB_CONNECTION=pgsql
DB_PORT=5432
```

Update `docker-compose.prod.yml` to use PostgreSQL image:

```yaml
db:
  image: postgres:16-alpine
  environment:
    - POSTGRES_DB=${DB_DATABASE}
    - POSTGRES_USER=${DB_USERNAME}
    - POSTGRES_PASSWORD=${DB_PASSWORD}
  healthcheck:
    test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME}"]
```

### Adding More Queue Workers

Edit `docker-compose.prod.yml` and update the environment variable in the app service:

```yaml
environment:
  - QUEUE_WORKERS=5
```

Then update `.docker/supervisor/supervisord.conf`:

```ini
[program:queue-worker]
numprocs=5  # Change from 2 to 5
```

Rebuild the image and redeploy.

## Security Best Practices

1. **Never commit `.env` files** to git
2. **Use strong passwords** for database credentials
3. **Keep `APP_DEBUG=false`** in production
4. **Enable SSL** in Nginx Proxy Manager
5. **Regularly update** Docker images
6. **Backup volumes** regularly:
   ```bash
   docker run --rm -v laravel-app_db-data:/data -v $(pwd):/backup alpine tar czf /backup/db-backup.tar.gz -C /data .
   ```

## Health Checks

The app container includes health checks. Add this route to your Laravel app:

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

## Support

For issues related to:
- **Docker setup**: Check this documentation
- **GitHub Actions**: Check `.github/workflows/docker-release.yml`
- **Laravel configuration**: Check Laravel documentation
- **Unraid**: Check Unraid forums

## Summary

1. Set up GitHub secrets (Docker Hub credentials)
2. Create a release on GitHub
3. GitHub Actions builds and pushes to Docker Hub automatically
4. Pull image on Unraid and deploy with docker-compose
5. Configure Nginx Proxy Manager for SSL and domain routing
