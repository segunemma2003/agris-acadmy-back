# Deployment Checklist

## Pre-Deployment

-   [ ] Server meets requirements (PHP 8.2+, MySQL, Nginx/Apache)
-   [ ] Domain name configured and pointing to server
-   [ ] SSH access to server configured
-   [ ] GitHub repository created
-   [ ] GitHub Secrets configured (HOST, USERNAME, SSH_KEY, DEPLOY_PATH)

## Initial Deployment

### Server Setup

-   [ ] Run server setup script or install manually
-   [ ] PHP 8.2+ installed with required extensions
-   [ ] Composer installed
-   [ ] Node.js 18+ installed
-   [ ] MySQL/PostgreSQL installed and configured
-   [ ] Nginx/Apache installed and configured
-   [ ] Firewall configured (ports 22, 80, 443)

### Application Setup

-   [ ] Repository cloned to server
-   [ ] `.env` file created and configured
-   [ ] Database created
-   [ ] Application key generated
-   [ ] Dependencies installed (composer, npm)
-   [ ] Assets built
-   [ ] Migrations run
-   [ ] Storage permissions set
-   [ ] Storage link created
-   [ ] Admin user created

### Web Server Configuration

-   [ ] Nginx/Apache virtual host configured
-   [ ] Document root set to `public` directory
-   [ ] PHP-FPM configured
-   [ ] SSL certificate installed (Let's Encrypt)
-   [ ] HTTP redirects to HTTPS

### Background Services

-   [ ] Queue workers configured (Supervisor)
-   [ ] Cron jobs configured
-   [ ] Redis configured (if using)

## Post-Deployment

### Verification

-   [ ] Application accessible via domain
-   [ ] Admin panel accessible and login works
-   [ ] Tutor panel accessible and login works
-   [ ] API endpoints responding
-   [ ] Database connections working
-   [ ] File uploads working
-   [ ] Email sending working (if configured)

### Security

-   [ ] Default admin password changed
-   [ ] `.env` file secured (not in repository)
-   [ ] File permissions correct
-   [ ] Firewall enabled
-   [ ] SSL certificate valid
-   [ ] Regular backups configured

### Monitoring

-   [ ] Error logging enabled
-   [ ] Log rotation configured
-   [ ] Monitoring tools set up
-   [ ] Backup system tested

## GitHub Actions Setup

-   [ ] Workflow file created (`.github/workflows/deploy.yml`)
-   [ ] GitHub Secrets configured
-   [ ] SSH key added to server
-   [ ] Deployment script executable
-   [ ] Test deployment successful

## Ongoing Maintenance

-   [ ] Regular backups scheduled
-   [ ] Security updates applied
-   [ ] Dependencies updated
-   [ ] Logs monitored
-   [ ] Performance optimized
-   [ ] SSL certificate auto-renewal configured

---

## Quick Commands

### Check Application Status

```bash
php artisan about
php artisan route:list
php artisan config:cache
```

### View Logs

```bash
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Run Migrations

```bash
php artisan migrate
php artisan migrate:status
```

### Check Queue Workers

```bash
sudo supervisorctl status
sudo supervisorctl restart laravel-worker:*
```

---

**Keep this checklist updated as you deploy!**
