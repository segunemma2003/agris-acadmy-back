# 🚀 Quick Start - Deployment

## Initial Deployment (First Time)

### 1. On Your Server

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/yourusername/agrisiti-main-academy.git agrisiti-academy
cd agrisiti-academy

# Run initial deployment
chmod +x scripts/*.sh
./scripts/initial-deploy.sh
```

### 2. Configure .env

Edit `.env` file with your database and other settings.

### 3. Set Up Web Server

See `DEPLOYMENT_GUIDE.md` for Nginx/Apache configuration.

### 4. Set Up SSL

```bash
sudo certbot --nginx -d your-domain.com
```

## Automated Deployment (GitHub Actions)

### 1. Configure GitHub Secrets

Go to: Repository → Settings → Secrets → Actions

Add:
- `HOST` - Your server IP/domain
- `USERNAME` - SSH username  
- `SSH_KEY` - Private SSH key
- `DEPLOY_PATH` - `/var/www/agrisiti-academy`
- `PORT` - `22`

### 2. Push to Deploy

```bash
git push origin main
```

That's it! GitHub Actions will automatically deploy.

## Documentation

- `INITIAL_DEPLOYMENT.md` - Step-by-step initial setup
- `DEPLOYMENT_GUIDE.md` - Complete deployment guide
- `QUICK_DEPLOY.md` - Quick reference
- `DEPLOYMENT_CHECKLIST.md` - Deployment checklist

