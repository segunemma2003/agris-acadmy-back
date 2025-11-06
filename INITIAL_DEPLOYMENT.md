# Initial Deployment Guide

This guide walks you through the complete initial deployment process step by step.

---

## 📋 Prerequisites Checklist

Before starting, ensure you have:

-   [ ] A server (VPS, dedicated server, or cloud instance)
-   [ ] SSH access to your server
-   [ ] Domain name pointing to your server IP
-   [ ] GitHub repository with your code
-   [ ] Basic knowledge of Linux commands

---

## 🚀 Step-by-Step Initial Deployment

### Step 1: Server Preparation

#### 1.1 Connect to Your Server

```bash
ssh user@your-server-ip
```

#### 1.2 Update System

```bash
sudo apt update && sudo apt upgrade -y
```

#### 1.3 Install Required Software

**Option A: Automated Setup (Recommended)**

```bash
# Download and run setup script
curl -sSL https://raw.githubusercontent.com/yourusername/agrisiti-main-academy/main/scripts/setup-server.sh | bash
```

**Option B: Manual Installation**

```bash
# Install PHP 8.2
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl \
    php8.2-xml php8.2-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Nginx
sudo apt install -y nginx

# Install MySQL
sudo apt install -y mysql-server

# Install Git
sudo apt install -y git
```

### Step 2: Database Setup

#### 2.1 Secure MySQL Installation

```bash
sudo mysql_secure_installation
```

#### 2.2 Create Database and User

```bash
sudo mysql -u root -p
```

In MySQL prompt:

```sql
CREATE DATABASE agrisiti_academy;
CREATE USER 'agrisiti_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON agrisiti_academy.* TO 'agrisiti_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Clone Repository

#### 3.1 Create Project Directory

```bash
sudo mkdir -p /var/www/agrisiti-academy
sudo chown -R $USER:$USER /var/www/agrisiti-academy
cd /var/www/agrisiti-academy
```

#### 3.2 Clone Repository

```bash
git clone https://github.com/yourusername/agrisiti-main-academy.git .
```

### Step 4: Configure Application

#### 4.1 Create Environment File

```bash
cp .env.example .env
nano .env
```

#### 4.2 Configure .env File

Update these key settings:

```env
APP_NAME="AgriSiti Academy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agrisiti_academy
DB_USERNAME=agrisiti_user
DB_PASSWORD=your_secure_password

# For file storage (use S3 in production)
FILESYSTEM_DISK=local
# Or use S3:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=your_key
# AWS_SECRET_ACCESS_KEY=your_secret
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=your-bucket

# Queue (use Redis in production)
QUEUE_CONNECTION=database
# Or use Redis:
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379

# Mail configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### 4.3 Generate Application Key

```bash
php artisan key:generate
```

### Step 5: Install Dependencies

#### 5.1 Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

#### 5.2 Install Node Dependencies

```bash
npm ci --production
```

#### 5.3 Build Assets

```bash
npm run build
```

### Step 6: Database Migration

#### 6.1 Run Migrations

```bash
php artisan migrate --force
```

### Step 7: Set Permissions

#### 7.1 Set Storage Permissions

```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### 7.2 Create Storage Link

```bash
php artisan storage:link
```

### Step 8: Optimize Application

#### 8.1 Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 9: Create Admin User

#### 9.1 Create Admin via Tinker

```bash
php artisan tinker
```

```php
$admin = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@your-domain.com',
    'password' => bcrypt('your-secure-password'),
    'role' => 'admin',
    'is_active' => true,
]);
exit
```

### Step 10: Configure Web Server

#### 10.1 Nginx Configuration

Create configuration file:

```bash
sudo nano /etc/nginx/sites-available/agrisiti-academy
```

Paste this configuration:

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
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 10.2 Enable Site

```bash
sudo ln -s /etc/nginx/sites-available/agrisiti-academy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 11: Set Up SSL Certificate

#### 11.1 Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

#### 11.2 Obtain SSL Certificate

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Certbot will automatically configure Nginx for HTTPS.

### Step 12: Set Up Queue Workers (Optional)

#### 12.1 Install Supervisor

```bash
sudo apt install -y supervisor
```

#### 12.2 Create Supervisor Configuration

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Paste:

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

#### 12.3 Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Step 13: Set Up Cron Jobs

#### 13.1 Add Cron Job

```bash
sudo crontab -e -u www-data
```

Add this line:

```
* * * * * cd /var/www/agrisiti-academy && php artisan schedule:run >> /dev/null 2>&1
```

### Step 14: Configure GitHub Actions (For Auto-Deployment)

#### 14.1 Generate SSH Key

On your local machine:

```bash
ssh-keygen -t rsa -b 4096 -C "github-actions" -f ~/.ssh/github_actions
```

#### 14.2 Copy Public Key to Server

```bash
ssh-copy-id -i ~/.ssh/github_actions.pub user@your-server-ip
```

#### 14.3 Add Private Key to GitHub Secrets

1. Go to GitHub repository
2. Settings → Secrets and variables → Actions
3. Click "New repository secret"
4. Add these secrets:

    **HOST:**

    ```
    your-domain.com
    ```

    **USERNAME:**

    ```
    your-ssh-username
    ```

    **SSH_KEY:**

    ```
    (paste content of ~/.ssh/github_actions - the private key)
    ```

    **DEPLOY_PATH:**

    ```
    /var/www/agrisiti-academy
    ```

    **PORT:**

    ```
    22
    ```

#### 14.4 Test Deployment

Push to main branch:

```bash
git push origin main
```

Check GitHub Actions tab to see deployment progress.

---

## ✅ Verification

### Test Application

1. **Visit your domain:**

    ```
    https://your-domain.com
    ```

2. **Test Admin Panel:**

    ```
    https://your-domain.com/admin
    Login: admin@your-domain.com / your-password
    ```

3. **Test Tutor Panel:**

    ```
    https://your-domain.com/tutor
    ```

4. **Test API:**
    ```
    https://your-domain.com/api/categories
    ```

### Check Logs

```bash
# Application logs
tail -f /var/www/agrisiti-academy/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

---

## 🔄 Future Deployments

After initial setup, deployments are automatic:

1. **Push to GitHub:**

    ```bash
    git push origin main
    ```

2. **GitHub Actions automatically:**
    - Runs tests
    - Builds assets
    - Deploys to server
    - Runs migrations
    - Clears caches
    - Optimizes application

---

## 🆘 Troubleshooting

### Application Not Loading

```bash
# Check Nginx status
sudo systemctl status nginx

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check permissions
ls -la /var/www/agrisiti-academy/storage
```

### Database Connection Error

```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

### Permission Errors

```bash
sudo chown -R www-data:www-data /var/www/agrisiti-academy
sudo chmod -R 775 /var/www/agrisiti-academy/storage
```

### GitHub Actions Deployment Fails

1. Check SSH connection:

    ```bash
    ssh -i ~/.ssh/github_actions user@your-server-ip
    ```

2. Verify secrets are correct in GitHub
3. Check deployment script permissions:
    ```bash
    chmod +x /var/www/agrisiti-academy/scripts/deploy.sh
    ```

---

## 📚 Additional Resources

-   See `DEPLOYMENT_GUIDE.md` for detailed information
-   See `QUICK_DEPLOY.md` for quick reference
-   See `DEPLOYMENT_CHECKLIST.md` for checklist

---

**Your application is now deployed! 🎉**
