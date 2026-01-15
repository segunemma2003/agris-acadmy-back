# EC2 Instance Setup Guide for CI/CD Deployment

This guide outlines the steps required to prepare your AWS EC2 instance for automated CI/CD deployment using GitHub Actions.

## Prerequisites

-   AWS EC2 instance running Ubuntu 22.04 LTS (or similar)
-   SSH access to the EC2 instance
-   GitHub repository with Actions enabled
-   Domain name (optional, for SSL)

---

## Step 1: Initial Server Setup

### Launch EC2 Instance

1. Launch an EC2 instance with Ubuntu 22.04 LTS
2. Configure Security Groups:
    - **SSH (22)**: Allow from your IP or GitHub Actions IPs
    - **HTTP (80)**: Allow from anywhere (0.0.0.0/0)
    - **HTTPS (443)**: Allow from anywhere (0.0.0.0/0)
3. Associate an Elastic IP (recommended for static IP)

### Connect to Instance

```bash
ssh -i your-key.pem ubuntu@your-ec2-ip
```

---

## Step 2: Install Required Software

### Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### Install PHP 8.3 and Extensions

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml \
    php8.3-mbstring php8.3-bcmath php8.3-curl php8.3-zip php8.3-gd
```

### Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### Install Node.js and npm

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Install Git

```bash
sudo apt install -y git
```

### Install Nginx

```bash
sudo apt install -y nginx
```

### Install MySQL (if database is on same server)

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

---

## Step 3: Create Application Directory

```bash
sudo mkdir -p /var/www/laravel
sudo chown -R $USER:$USER /var/www/laravel
```

---

## Step 4: Clone Repository (Initial Setup)

### Option A: Using HTTPS

```bash
cd /var/www/laravel
git clone https://github.com/yourusername/agrisiti-main-academy.git .
```

### Option B: Using SSH (Recommended)

```bash
# Generate SSH key for GitHub
ssh-keygen -t ed25519 -C "deploy@ec2" -f ~/.ssh/github_deploy
cat ~/.ssh/github_deploy.pub
# Add this public key to GitHub as a deploy key

# Configure SSH
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/github_deploy

# Clone repository
cd /var/www/laravel
git clone git@github.com:yourusername/agrisiti-main-academy.git .
```

---

## Step 5: Set Up Database

### If MySQL is on the same server:

```bash
sudo mysql
```

Then run:

```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### If using RDS or external database:

-   Note the database host, port, name, username, and password
-   Ensure security groups allow connection from EC2 instance

---

## Step 6: Configure Nginx

### Create Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/laravel
```

Add the following configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name academy-backends.agrisiti.com;  # Replace with your domain
    root /var/www/laravel/public;

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
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Enable Site

```bash
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Set Up SSL with Let's Encrypt (Recommended)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d academy-backends.agrisiti.com
```

---

## Step 7: Configure PHP-FPM

### Start and Enable PHP-FPM

```bash
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
sudo systemctl status php8.3-fpm
```

### Verify PHP-FPM Configuration

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Ensure these settings:

```ini
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

---

## Step 8: Set Up Permissions

```bash
cd /var/www/laravel
sudo chown -R www-data:www-data /var/www/laravel
sudo chmod -R 755 /var/www/laravel
sudo chmod -R 775 /var/www/laravel/storage
sudo chmod -R 775 /var/www/laravel/bootstrap/cache
```

---

## Step 9: Create SSH Key for GitHub Actions

### Generate SSH Key Pair

```bash
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f ~/.ssh/github_actions_deploy -N ""
```

### Add Public Key to Authorized Keys

```bash
cat ~/.ssh/github_actions_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### Copy Private Key

```bash
cat ~/.ssh/github_actions_deploy
# Copy the entire output (including -----BEGIN and -----END lines)
```

**Important**: Keep this private key secure. You'll add it to GitHub Secrets.

---

## Step 10: Initial Application Setup

```bash
cd /var/www/laravel

# Install dependencies
composer install --no-dev --optimize-autoloader

# Create .env file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your configuration
nano .env
```

### Required .env Configuration

```env
APP_NAME="Agrisiti Academy"
APP_ENV=production
APP_KEY=base64:...  # Generated by key:generate
APP_DEBUG=false
APP_URL=https://academy-backends.agrisiti.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1  # Or RDS endpoint
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### Run Initial Migrations

```bash
php artisan migrate --force
php artisan storage:link
```

---

## Step 11: Configure GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions → New repository secret

Add the following secrets:

| Secret Name        | Description                      | Example Value                                     |
| ------------------ | -------------------------------- | ------------------------------------------------- |
| `AWS_EC2_HOST`     | EC2 instance public IP or domain | `54.123.45.67` or `academy-backends.agrisiti.com` |
| `AWS_EC2_USER`     | SSH username                     | `ubuntu`                                          |
| `AWS_EC2_SSH_KEY`  | Private SSH key content          | `-----BEGIN RSA PRIVATE KEY-----...`              |
| `AWS_EC2_SSH_PORT` | SSH port                         | `22`                                              |
| `APP_ENV`          | Application environment          | `production`                                      |
| `APP_KEY`          | Laravel app key                  | `base64:...` (from `php artisan key:generate`)    |
| `APP_DEBUG`        | Debug mode                       | `false`                                           |
| `DB_HOST`          | Database host                    | `127.0.0.1` or RDS endpoint                       |
| `DB_DATABASE`      | Database name                    | `agrisiti_academy`                                |
| `DB_USERNAME`      | Database username                | `db_user`                                         |
| `DB_PASSWORD`      | Database password                | `secure_password`                                 |

---

## Step 12: Configure Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status
```

---

## Step 13: Set Up Laravel Scheduler (Optional)

### Add Cron Job

```bash
sudo crontab -e -u www-data
```

Add:

```
* * * * * cd /var/www/laravel && php artisan schedule:run >> /dev/null 2>&1
```

---

## Step 14: Set Up Queue Workers (Optional)

**See `SUPERVISOR_SETUP.md` for detailed instructions.**

Quick setup:

```bash
# Install Supervisor
sudo apt install -y supervisor

# Create config
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Add configuration (see `SUPERVISOR_SETUP.md` for full details):

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/laravel/storage/logs/worker.log
stopwaitsecs=3600
```

Start workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

**For complete setup guide, troubleshooting, and advanced configuration, see `SUPERVISOR_SETUP.md`**

---

## Step 15: Test Deployment

### Test SSH Connection

From your local machine:

```bash
ssh -i ~/.ssh/github_actions_deploy ubuntu@your-ec2-ip
```

### Test Manual Deployment

```bash
cd /var/www/laravel
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

---

## Step 16: Security Hardening

### Disable Root Login

```bash
sudo nano /etc/ssh/sshd_config
```

Set:

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

Restart SSH:

```bash
sudo systemctl restart sshd
```

### Keep System Updated

```bash
# Set up automatic security updates
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

---

## Verification Checklist

-   [ ] PHP 8.3 installed and configured
-   [ ] Composer installed
-   [ ] Node.js and npm installed
-   [ ] Git installed and repository cloned
-   [ ] Nginx configured and running
-   [ ] PHP-FPM running
-   [ ] Database created and accessible
-   [ ] Application directory permissions set correctly
-   [ ] SSH key generated and added to authorized_keys
-   [ ] GitHub secrets configured
-   [ ] Firewall configured
-   [ ] SSL certificate installed (if using domain)
-   [ ] Application accessible via browser
-   [ ] Test deployment successful

---

## Troubleshooting

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/laravel
sudo chmod -R 755 /var/www/laravel
sudo chmod -R 775 /var/www/laravel/storage
sudo chmod -R 775 /var/www/laravel/bootstrap/cache
```

### Nginx 502 Bad Gateway

-   Check PHP-FPM is running: `sudo systemctl status php8.3-fpm`
-   Check PHP-FPM socket: `ls -la /var/run/php/php8.3-fpm.sock`
-   Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`

### Database Connection Issues

-   Verify database credentials in `.env`
-   Check MySQL is running: `sudo systemctl status mysql`
-   Test connection: `mysql -u username -p database_name`

### Git Pull Issues

-   Ensure SSH key is added to GitHub
-   Check repository permissions
-   Verify git remote URL: `git remote -v`

---

## Maintenance

### Regular Updates

```bash
sudo apt update && sudo apt upgrade -y
composer self-update
```

### Monitor Logs

```bash
# Application logs
tail -f /var/www/laravel/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log
```

---

## Support

For issues or questions, refer to:

-   Laravel Documentation: https://laravel.com/docs
-   Nginx Documentation: https://nginx.org/en/docs/
-   AWS EC2 Documentation: https://docs.aws.amazon.com/ec2/
