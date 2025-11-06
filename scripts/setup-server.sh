#!/bin/bash

# Server setup script for AgriSiti LMS
# Run this script on a fresh Ubuntu/Debian server

set -e

echo "🚀 Setting up server for AgriSiti LMS..."

# Update system
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
echo "🐘 Installing PHP..."
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl \
    php8.2-xml php8.2-bcmath php8.2-sqlite3

# Install Composer
echo "📦 Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

# Install Node.js and NPM
echo "📦 Installing Node.js..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
    sudo apt install -y nodejs
fi

# Install Nginx
echo "🌐 Installing Nginx..."
sudo apt install -y nginx

# Install MySQL
echo "🗄️  Installing MySQL..."
sudo apt install -y mysql-server

# Install Redis (optional)
echo "📦 Installing Redis..."
sudo apt install -y redis-server

# Install Git
echo "📦 Installing Git..."
sudo apt install -y git

# Install Supervisor
echo "📦 Installing Supervisor..."
sudo apt install -y supervisor

# Install Certbot for SSL
echo "🔒 Installing Certbot..."
sudo apt install -y certbot python3-certbot-nginx

# Create project directory
echo "📁 Creating project directory..."
sudo mkdir -p /var/www/agrisiti-academy
sudo chown -R $USER:$USER /var/www/agrisiti-academy

# Configure PHP-FPM
echo "⚙️  Configuring PHP-FPM..."
sudo sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.2/fpm/php.ini
sudo systemctl restart php8.2-fpm

# Configure MySQL
echo "🗄️  Configuring MySQL..."
sudo mysql_secure_installation

# Create database
echo "📊 Creating database..."
read -p "Enter MySQL root password: " MYSQL_ROOT_PASSWORD
read -p "Enter database name: " DB_NAME
read -p "Enter database user: " DB_USER
read -p "Enter database password: " DB_PASSWORD

sudo mysql -u root -p$MYSQL_ROOT_PASSWORD <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "✅ Database created: $DB_NAME"

# Configure firewall
echo "🔥 Configuring firewall..."
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

echo ""
echo "✅ Server setup completed!"
echo ""
echo "📋 Next steps:"
echo "   1. Clone your repository:"
echo "      cd /var/www/agrisiti-academy"
echo "      git clone https://github.com/yourusername/agrisiti-main-academy.git ."
echo ""
echo "   2. Run initial deployment:"
echo "      ./scripts/initial-deploy.sh"
echo ""
echo "   3. Configure Nginx (see DEPLOYMENT_GUIDE.md)"
echo ""
echo "   4. Set up SSL certificate:"
echo "      sudo certbot --nginx -d your-domain.com"
echo ""

