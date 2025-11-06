# Deployment Guide - AgriSiti LMS

This guide covers both initial deployment and automated CI/CD setup using GitHub Actions.

---

## 📋 Prerequisites

### Server Requirements

-   **PHP:** 8.2 or higher
-   **Composer:** Latest version
-   **Node.js:** 18 or higher
-   **NPM:** Latest version
-   **Database:** MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.8+
-   **Web Server:** Nginx or Apache
-   **SSH Access:** To your server

### PHP Extensions Required

-   mbstring
-   xml
-   bcmath
-   pdo
-   pdo_mysql (or pdo_pgsql)
-   fileinfo
-   gd
-   curl
-   zip

---

## 🚀 Initial Deployment

### Option 1: Manual Deployment

#### Step 1: Clone Repository

```bash
cd /var/www
git clone https://github.com/yourusername/agrisiti-main-academy.git
cd agrisiti-main-academy
```

#### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci --production

# Build assets
npm run build
```

#### Step 3: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env file
nano .env
```

**Configure these settings in .env:**

```env
APP_NAME="AgriSiti Academy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agrisiti_academy
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=your_region
AWS_BUCKET=your_bucket

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Step 4: Generate Application Key

```bash
php artisan key:generate
```

#### Step 5: Run Migrations

```bash
php artisan migrate --force
```

#### Step 6: Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Step 7: Create Storage Link

```bash
php artisan storage:link
```

#### Step 8: Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 9: Create Admin User

```bash
php artisan tinker
```

```php
$admin = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('your-secure-password'),
    'role' => 'admin',
    'is_active' => true,
]);
```

### Option 2: Automated Initial Deployment

Use the provided script:

```bash
# Make script executable
chmod +x scripts/initial-deploy.sh

# Run initial deployment
./scripts/initial-deploy.sh /var/www/agrisiti-academy https://github.com/yourusername/agrisiti-main-academy.git main
```

---

## 🔄 Automated Deployment with GitHub Actions

### Step 1: Set Up GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions

Add these secrets:

1. **HOST** - Your server IP or domain

    ```
    example.com
    ```

2. **USERNAME** - SSH username

    ```
    deploy
    ```

3. **SSH_KEY** - Private SSH key (content of ~/.ssh/id_rsa)

    ```
    -----BEGIN OPENSSH PRIVATE KEY-----
    ...
    -----END OPENSSH PRIVATE KEY-----
    ```

4. **DEPLOY_PATH** - Deployment path on server

    ```
    /var/www/agrisiti-academy
    ```

5. **PORT** (optional) - SSH port (default: 22)
    ```
    22
    ```

### Step 2: Generate SSH Key

On your local machine:

```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-actions"

# Copy public key to server
ssh-copy-id -i ~/.ssh/id_rsa.pub user@your-server.com

# Display private key (add to GitHub Secrets)
cat ~/.ssh/id_rsa
```

On your server:

```bash
# Add public key to authorized_keys
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "YOUR_PUBLIC_KEY" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### Step 3: Choose Deployment Workflow

Two workflows are available:

#### Option A: SCP Deployment (`.github/workflows/deploy.yml`)

-   Uses SCP to copy files
-   Good for smaller deployments
-   Simpler setup

#### Option B: RSYNC Deployment (`.github/workflows/deploy-rsync.yml`)

-   Uses RSYNC for efficient file transfer
-   Better for larger deployments
-   Only transfers changed files

**To use a specific workflow:**

1. Rename the workflow file you want to use to `deploy.yml`
2. Delete or rename the other workflow file

### Step 4: Configure Deployment Script

Make sure `scripts/deploy.sh` is executable on your server:

```bash
chmod +x scripts/deploy.sh
```

### Step 5: Test Deployment

1. Push to main/master branch
2. GitHub Actions will automatically trigger
3. Check Actions tab in GitHub for deployment status

---

## 🔧 Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/agrisiti-academy`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/agrisiti-academy/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/agrisiti-academy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

Create `/etc/apache2/sites-available/agrisiti-academy.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/agrisiti-academy/public

    <Directory /var/www/agrisiti-academy/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/agrisiti-error.log
    CustomLog ${APACHE_LOG_DIR}/agrisiti-access.log combined
</VirtualHost>
```

Enable site:

```bash
sudo a2ensite agrisiti-academy.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

---

## 📦 Queue Workers Setup

### Using Supervisor

Install Supervisor:

```bash
sudo apt install supervisor
```

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/agrisiti-academy/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/agrisiti-academy/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## ⏰ Cron Jobs Setup

Add to crontab:

```bash
sudo crontab -e -u www-data
```

Add this line:

```
* * * * * cd /var/www/agrisiti-academy && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔍 Post-Deployment Checklist

-   [ ] Verify application is accessible
-   [ ] Test admin panel login
-   [ ] Test tutor panel login
-   [ ] Test API endpoints
-   [ ] Verify database connections
-   [ ] Check file uploads working
-   [ ] Verify email configuration
-   [ ] Test queue workers
-   [ ] Check cron jobs
-   [ ] Verify SSL certificate
-   [ ] Test backup system
-   [ ] Monitor error logs

---

## 🐛 Troubleshooting

### Deployment Fails

1. **Check SSH connection:**

    ```bash
    ssh -i ~/.ssh/id_rsa user@your-server.com
    ```

2. **Check permissions:**

    ```bash
    ls -la /var/www/agrisiti-academy
    ```

3. **Check logs:**
    ```bash
    tail -f storage/logs/laravel.log
    ```

### Application Not Loading

1. **Check web server:**

    ```bash
    sudo systemctl status nginx
    # or
    sudo systemctl status apache2
    ```

2. **Check PHP-FPM:**

    ```bash
    sudo systemctl status php8.2-fpm
    ```

3. **Check permissions:**
    ```bash
    sudo chown -R www-data:www-data /var/www/agrisiti-academy
    sudo chmod -R 775 /var/www/agrisiti-academy/storage
    ```

### Database Connection Issues

1. **Test connection:**

    ```bash
    php artisan tinker
    DB::connection()->getPdo();
    ```

2. **Check .env file:**
    ```bash
    cat .env | grep DB_
    ```

---

## 📚 Additional Resources

-   [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
-   [Filament Documentation](https://filamentphp.com/docs)
-   [Nginx Configuration](https://www.nginx.com/resources/wiki/)
-   [GitHub Actions Documentation](https://docs.github.com/en/actions)

---

## 🔐 Security Best Practices

1. **Change default passwords**
2. **Use strong database passwords**
3. **Enable firewall (UFW)**
4. **Keep software updated**
5. **Use SSL/HTTPS**
6. **Regular backups**
7. **Monitor logs**
8. **Limit SSH access**
9. **Use environment variables for secrets**
10. **Regular security audits**

---

## 📞 Support

For deployment issues, check:

-   Application logs: `storage/logs/laravel.log`
-   Web server logs: `/var/log/nginx/error.log` or `/var/log/apache2/error.log`
-   GitHub Actions logs: Repository → Actions tab

---

**Happy Deploying! 🚀**
