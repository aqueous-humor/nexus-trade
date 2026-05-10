# NexusTrade Deployment Guide

This guide covers deploying NexusTrade to various platforms using Docker.

---

## Environment Variables Reference

### Backend (Laravel)

| Variable | Description | Example |
|---|---|---|
| `APP_NAME` | Application name | `NexusTrade` |
| `APP_ENV` | Environment | `production` |
| `APP_KEY` | Laravel encryption key (generate with `php artisan key:generate`) | `base64:...` |
| `APP_DEBUG` | Debug mode (must be `false` in production) | `false` |
| `APP_URL` | Full application URL | `https://api.nexustrade.io` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `db.example.com` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `nexustrade` |
| `DB_USERNAME` | Database username | `nexustrade` |
| `DB_PASSWORD` | Database password | `secret` |
| `REDIS_HOST` | Redis host | `redis.example.com` |
| `REDIS_PORT` | Redis port | `6379` |
| `REDIS_PASSWORD` | Redis password | `null` |
| `CACHE_STORE` | Cache driver | `redis` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `SESSION_DRIVER` | Session driver | `redis` |
| `MAIL_MAILER` | Mail driver | `smtp` |
| `MAIL_HOST` | SMTP host | `smtp.mailgun.org` |
| `MAIL_PORT` | SMTP port | `587` |
| `MAIL_USERNAME` | SMTP username | `postmaster@...` |
| `MAIL_PASSWORD` | SMTP password | `secret` |
| `MAIL_FROM_ADDRESS` | From email address | `noreply@nexustrade.io` |
| `BROADCAST_CONNECTION` | Broadcasting driver | `pusher` |
| `PUSHER_APP_ID` | Pusher/Soketi app ID | `nexustrade` |
| `PUSHER_APP_KEY` | Pusher/Soketi app key | `nexustrade-key` |
| `PUSHER_APP_SECRET` | Pusher/Soketi app secret | `nexustrade-secret` |
| `PUSHER_HOST` | WebSocket host (Soketi) | `soketi.example.com` |
| `PUSHER_PORT` | WebSocket port | `6001` |
| `PUSHER_SCHEME` | WebSocket scheme | `https` |
| `SANCTUM_STATEFUL_DOMAINS` | Allowed frontend domains | `nexustrade.io` |
| `FRONTEND_URL` | Frontend URL for CORS | `https://nexustrade.io` |

### Frontend (Vue)

| Variable | Description | Example |
|---|---|---|
| `VITE_API_URL` | Backend API base URL | `https://api.nexustrade.io` |
| `VITE_PUSHER_APP_KEY` | Pusher/Soketi app key | `nexustrade-key` |
| `VITE_PUSHER_HOST` | WebSocket host | `soketi.example.com` |
| `VITE_PUSHER_PORT` | WebSocket port | `6001` |
| `VITE_PUSHER_SCHEME` | WebSocket scheme | `https` |

---

## Docker Build Commands

### Build backend image

```bash
docker build -t nexustrade-backend:latest ./backend
```

### Build frontend image

```bash
docker build -t nexustrade-frontend:latest ./frontend
```

### Run locally with Docker Compose

```bash
docker compose up -d
```

---

## Platform Deployment Guides

### Railway

Railway supports Docker deployments with automatic builds from GitHub.

1. Create a new project on [Railway](https://railway.app)
2. Add a MySQL service and a Redis service from the Railway marketplace
3. Create a backend service pointing to `./backend` with the `Dockerfile`
4. Create a frontend service pointing to `./frontend` with the `Dockerfile`
5. Set environment variables in the Railway dashboard for each service
6. Railway auto-assigns domains — set `APP_URL` and `VITE_API_URL` accordingly
7. Enable automatic deploys from your GitHub repository

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login and deploy
railway login
railway link
railway up
```

### Render

1. Create a new Web Service on [Render](https://render.com)
2. Connect your GitHub repository
3. Set the root directory to `backend` and Docker as the environment
4. Add a PostgreSQL or MySQL database from Render's managed databases
5. Add a Redis instance from Render's managed Redis
6. Set all environment variables in the Render dashboard
7. Repeat for the frontend service with root directory `frontend`

```bash
# Render deploys automatically on push to main
git push origin main
```

### Fly.io

```bash
# Install flyctl
curl -L https://fly.io/install.sh | sh

# Deploy backend
fly launch --dockerfile backend/Dockerfile --name nexustrade-api
fly secrets set APP_KEY=base64:... DB_HOST=... --app nexustrade-api
fly deploy --app nexustrade-api

# Deploy frontend
fly launch --dockerfile frontend/Dockerfile --name nexustrade-web
fly deploy --app nexustrade-web
```

### DigitalOcean App Platform

1. Go to [DigitalOcean App Platform](https://cloud.digitalocean.com/apps)
2. Create a new app from your GitHub repository
3. Add two components: one for `backend/` and one for `frontend/`
4. Set the Dockerfile path for each component
5. Add a managed MySQL database and Redis cluster
6. Configure environment variables in the app settings
7. Deploy — DigitalOcean handles SSL and load balancing automatically

### VPS (Ubuntu/Debian)

```bash
# Install Docker and Docker Compose
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Clone repository
git clone https://github.com/your-org/nexustrade.git
cd nexustrade

# Create .env files
cp backend/.env.example backend/.env
# Edit backend/.env with production values

# Build and start
docker compose -f docker-compose.prod.yml up -d

# Run migrations and seed
docker compose exec backend php artisan migrate --force
docker compose exec backend php artisan db:seed --force

# Set up SSL with Certbot
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d nexustrade.io -d api.nexustrade.io
```

---

## WebSocket Options

### Option 1: Soketi (Self-hosted)

[Soketi](https://docs.soketi.app) is a free, open-source Pusher-compatible WebSocket server.

```bash
# Add to docker-compose.yml
soketi:
  image: quay.io/soketi/soketi:latest-16-alpine
  environment:
    SOKETI_DEFAULT_APP_ID: nexustrade
    SOKETI_DEFAULT_APP_KEY: nexustrade-key
    SOKETI_DEFAULT_APP_SECRET: nexustrade-secret
  ports:
    - "6001:6001"
```

Backend `.env`:
```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=nexustrade
PUSHER_APP_KEY=nexustrade-key
PUSHER_APP_SECRET=nexustrade-secret
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### Option 2: Pusher Cloud

1. Create an account at [Pusher](https://pusher.com)
2. Create a new Channels app
3. Copy the app credentials to your `.env`:

```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

---

## Database Options

### PlanetScale (MySQL-compatible)

1. Create a database at [PlanetScale](https://planetscale.com)
2. Create a branch for production
3. Get the connection string from the dashboard
4. Set `DB_CONNECTION=mysql` and the PlanetScale credentials in your `.env`
5. Note: PlanetScale does not support foreign key constraints — ensure `FOREIGN_KEY_CHECKS=0` or use their schema branching workflow

### Railway MySQL

Railway provides managed MySQL databases with automatic backups.

```
DB_CONNECTION=mysql
DB_HOST=containers-us-west-xxx.railway.app
DB_PORT=6543
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=<railway-provided-password>
```

### Amazon RDS (Managed MySQL)

1. Create an RDS MySQL 8.0 instance in your AWS region
2. Configure security groups to allow connections from your application servers
3. Use the RDS endpoint as `DB_HOST`
4. Enable automated backups and Multi-AZ for production workloads

```
DB_CONNECTION=mysql
DB_HOST=nexustrade.cluster-xxx.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=nexustrade
DB_USERNAME=nexustrade
DB_PASSWORD=<rds-password>
```

---

## Branch Protection & CI Requirements

To enforce quality gates before merging to `main`:

1. Go to your GitHub repository → Settings → Branches
2. Add a branch protection rule for `main`
3. Enable:
   - **Require status checks to pass before merging**
   - Select `backend-tests` and `frontend-build` from the CI workflow
   - **Require branches to be up to date before merging**
   - **Require pull request reviews before merging** (recommended: 1 reviewer)
4. Click **Save changes**

This ensures all CI checks must pass before any code reaches `main`.

---

## Deployment Commands by Platform

| Platform | Deploy Command |
|---|---|
| Railway | `railway up` |
| Render | `git push origin main` (auto-deploy) |
| Fly.io | `fly deploy --app nexustrade-api` |
| DigitalOcean | Push to connected branch (auto-deploy) |
| VPS | `docker compose -f docker-compose.prod.yml up -d --build` |
