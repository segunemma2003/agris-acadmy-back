# Quick Deployment Guide

## 🚀 Fastest Way to Deploy

### Step 1: Set Up GitHub Secrets

1. Go to your GitHub repository
2. Settings → Secrets and variables → Actions
3. Add these secrets:

```
HOST=your-server-ip-or-domain.com
USERNAME=deploy
SSH_KEY=your-private-ssh-key-content
DEPLOY_PATH=/var/www/agrisiti-academy
PORT=22
```

### Step 2: Initial Server Setup

**On your server, run:**

```bash
# Run server setup script
curl -sSL https://raw.githubusercontent.com/yourusername/agrisiti-main-academy/main/scripts/setup-server.sh | bash

# Or manually:
sudo apt update && sudo apt install -y php8.2 php8.2-fpm php8.2-mysql composer nginx mysql-server nodejs npm git
```

### Step 3: Initial Deployment

**On your server:**

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/yourusername/agrisiti-main-academy.git agrisiti-academy
cd agrisiti-academy

# Make scripts executable
chmod +x scripts/*.sh

# Run initial deployment
./scripts/initial-deploy.sh
```

### Step 4: Configure Web Server

**Nginx configuration:**

```bash
sudo nano /etc/nginx/sites-available/agrisiti-academy
```

Paste:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/agrisiti-academy/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Enable:

```bash
sudo ln -s /etc/nginx/sites-available/agrisiti-academy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 5: Set Up SSL

```bash
sudo certbot --nginx -d your-domain.com
```

### Step 6: Enable GitHub Actions

1. Push your code to GitHub
2. GitHub Actions will automatically deploy on push to main/master
3. Check Actions tab for deployment status

---

## 📝 Manual Deployment (Without GitHub Actions)

```bash
# On your server
cd /var/www/agrisiti-academy
git pull origin main
./scripts/deploy.sh
```

---

## ✅ Verify Deployment

1. **Check application:**

    ```
    http://your-domain.com
    ```

2. **Check admin panel:**

    ```
    http://your-domain.com/admin
    Login: admin@example.com / password123
    ```

3. **Check API:**
    ```
    http://your-domain.com/api/categories
    ```

---

## 🔄 Automatic Deployment

After initial setup, every push to `main` branch will automatically:

1. Run tests
2. Build assets
3. Deploy to server
4. Run migrations
5. Clear caches
6. Optimize application

---

## 🆘 Need Help?

See `DEPLOYMENT_GUIDE.md` for detailed instructions.
