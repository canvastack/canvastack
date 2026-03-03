# Deployment Guide

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Complete

---

## Table of Contents

1. [Overview](#overview)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Server Requirements](#server-requirements)
4. [Environment Setup](#environment-setup)
5. [Database Migration](#database-migration)
6. [Cache Configuration](#cache-configuration)
7. [Asset Compilation](#asset-compilation)
8. [Web Server Configuration](#web-server-configuration)
9. [Security Hardening](#security-hardening)
10. [Performance Optimization](#performance-optimization)
11. [Monitoring & Logging](#monitoring--logging)
12. [Backup Strategy](#backup-strategy)
13. [Rollback Procedure](#rollback-procedure)
14. [Troubleshooting](#troubleshooting)

---

## Overview

This guide covers deploying CanvaStack to production environments, including server setup, configuration, optimization, and maintenance procedures.

### Deployment Strategies

- **Traditional**: Deploy to VPS/dedicated server
- **Cloud**: Deploy to AWS, DigitalOcean, Linode, etc.
- **Container**: Deploy using Docker/Kubernetes
- **Platform**: Deploy to Laravel Forge, Ploi, etc.

---

## Pre-Deployment Checklist

### Code Preparation

- [ ] All tests passing (`php artisan test`)
- [ ] Code formatted (`./vendor/bin/pint`)
- [ ] Static analysis clean (`./vendor/bin/phpstan analyse`)
- [ ] Dependencies updated (`composer update`)
- [ ] Version tagged in Git
- [ ] CHANGELOG.md updated
- [ ] Documentation updated

### Configuration

- [ ] `.env.production` configured
- [ ] Database credentials secured
- [ ] Redis credentials secured
- [ ] API keys configured
- [ ] Mail settings configured
- [ ] Queue settings configured
- [ ] Debug mode disabled (`APP_DEBUG=false`)
- [ ] Error logging configured

### Security

- [ ] SSL certificate installed
- [ ] Security headers configured
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] File permissions set correctly
- [ ] Sensitive files protected
- [ ] Firewall rules configured

### Performance

- [ ] Assets compiled (`npm run build`)
- [ ] Opcache enabled
- [ ] Redis configured
- [ ] Database indexes created
- [ ] Query optimization verified
- [ ] CDN configured (if applicable)

---

## Server Requirements

### Minimum Requirements

- **PHP**: 8.2 or higher
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Cache**: Redis 7.x
- **Memory**: 2GB RAM minimum
- **Storage**: 10GB minimum
- **SSL**: Valid SSL certificate

### Recommended Requirements

- **PHP**: 8.3
- **Web Server**: Nginx 1.24+
- **Database**: MySQL 8.0+
- **Cache**: Redis 7.2+
- **Memory**: 4GB RAM
- **Storage**: 20GB SSD
- **CPU**: 2+ cores
- **Backup**: Automated daily backups

### PHP Extensions

Required extensions:
```bash
php -m | grep -E 'pdo|pdo_mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|redis'
```

Install missing extensions:
```bash
# Ubuntu/Debian
sudo apt install php8.2-{pdo,mysql,mbstring,xml,bcmath,redis,curl,zip,gd}

# CentOS/RHEL
sudo yum install php82-{pdo,mysqlnd,mbstring,xml,bcmath,redis,curl,zip,gd}
```

---

## Environment Setup

### 1. Clone Repository

```bash
# Clone to production directory
cd /var/www
sudo git clone https://github.com/your-org/your-app.git production
cd production

# Set ownership
sudo chown -R www-data:www-data /var/www/production
```

### 2. Install Dependencies

```bash
# Install Composer dependencies (production only)
composer install --no-dev --optimize-autoloader

# Install npm dependencies
npm ci

# Build assets
npm run build
```

### 3. Configure Environment

```bash
# Copy environment file
cp .env.example .env.production
ln -s .env.production .env

# Generate application key
php artisan key:generate

# Configure environment
nano .env
```

### 4. Production Environment Variables

```env
# Application
APP_NAME="Your Application"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=production_db
DB_USERNAME=production_user
DB_PASSWORD=secure_password

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error

# CanvaStack
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_DRIVER=redis
```

### 5. Set File Permissions

```bash
# Set correct ownership
sudo chown -R www-data:www-data /var/www/production

# Set directory permissions
sudo find /var/www/production -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/production -type f -exec chmod 644 {} \;

# Make storage and cache writable
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## Database Migration

### 1. Backup Existing Database

```bash
# Create backup
mysqldump -u root -p production_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Compress backup
gzip backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Run Migrations

```bash
# Check migration status
php artisan migrate:status

# Run migrations (with confirmation)
php artisan migrate --force

# Or run with seeding
php artisan migrate --seed --force
```

### 3. Verify Database

```bash
# Test database connection
php artisan tinker

# In tinker:
DB::connection()->getPdo();
User::count();
```

---

## Cache Configuration

### 1. Configure Redis

```bash
# Install Redis
sudo apt install redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf
```

Redis configuration:
```conf
# Bind to localhost only
bind 127.0.0.1

# Set password
requirepass secure_redis_password

# Set max memory
maxmemory 256mb
maxmemory-policy allkeys-lru

# Enable persistence
save 900 1
save 300 10
save 60 10000
appendonly yes
```

### 2. Restart Redis

```bash
sudo systemctl restart redis
sudo systemctl enable redis
```

### 3. Test Redis Connection

```bash
redis-cli -a secure_redis_password ping
# Should return: PONG
```

### 4. Cache Laravel Configuration

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

---

## Asset Compilation

### 1. Build Production Assets

```bash
# Install dependencies
npm ci

# Build for production
npm run build

# Verify assets
ls -la public/build/
```

### 2. Configure CDN (Optional)

Update `.env`:
```env
ASSET_URL=https://cdn.yourdomain.com
```

Upload assets to CDN:
```bash
# Sync to S3/CDN
aws s3 sync public/build/ s3://your-bucket/build/ --delete
```

---

## Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/yourdomain.com`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/production/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;

    # Logging
    access_log /var/log/nginx/yourdomain.com-access.log;
    error_log /var/log/nginx/yourdomain.com-error.log;

    # Index
    index index.php index.html;

    # Character Set
    charset utf-8;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json application/javascript;

    # Root Location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/yourdomain.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

Create `/etc/apache2/sites-available/yourdomain.com.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/production/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    <Directory /var/www/production/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/yourdomain.com-error.log
    CustomLog ${APACHE_LOG_DIR}/yourdomain.com-access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite yourdomain.com
sudo a2enmod rewrite ssl headers
sudo systemctl reload apache2
```

---

## Security Hardening

### 1. SSL Certificate

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

### 2. Firewall Configuration

```bash
# Install UFW
sudo apt install ufw

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

### 3. Fail2Ban

```bash
# Install Fail2Ban
sudo apt install fail2ban

# Configure
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local
```

Add Laravel jail:
```ini
[laravel]
enabled = true
port = http,https
filter = laravel
logpath = /var/www/production/storage/logs/laravel.log
maxretry = 3
bantime = 3600
```

### 4. Disable Directory Listing

Nginx:
```nginx
autoindex off;
```

Apache:
```apache
Options -Indexes
```

### 5. Hide Server Information

Nginx (`/etc/nginx/nginx.conf`):
```nginx
server_tokens off;
```

Apache (`/etc/apache2/conf-available/security.conf`):
```apache
ServerTokens Prod
ServerSignature Off
```

---

## Performance Optimization

### 1. PHP Opcache

Enable in `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. PHP-FPM Tuning

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### 3. Database Optimization

```sql
-- Analyze tables
ANALYZE TABLE users, roles, permissions;

-- Optimize tables
OPTIMIZE TABLE users, roles, permissions;

-- Add indexes
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_created_at (created_at);
```

### 4. Queue Workers

```bash
# Install Supervisor
sudo apt install supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Supervisor configuration:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/production/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/production/storage/logs/worker.log
stopwaitsecs=3600
```

Start workers:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## Monitoring & Logging

### 1. Application Logging

Configure in `config/logging.php`:
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'error',
        'days' => 14,
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

### 2. Error Tracking

Install Sentry:
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn
```

### 3. Performance Monitoring

Install Laravel Telescope (development only):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 4. Uptime Monitoring

Use services like:
- UptimeRobot
- Pingdom
- StatusCake
- Laravel Envoyer

---

## Backup Strategy

### 1. Database Backups

Create backup script `/usr/local/bin/backup-db.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="production_db"
DB_USER="backup_user"
DB_PASS="backup_password"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
```

Schedule with cron:
```bash
# Edit crontab
sudo crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-db.sh
```

### 2. File Backups

```bash
# Backup storage directory
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/

# Sync to remote server
rsync -avz storage/ backup-server:/backups/storage/
```

### 3. Automated Backups

Use Laravel Backup package:
```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
php artisan backup:run
```

---

## Rollback Procedure

### 1. Quick Rollback

```bash
# Switch to previous release
cd /var/www
ln -sfn production-previous production

# Restart services
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

### 2. Database Rollback

```bash
# Restore from backup
mysql -u root -p production_db < backup_20260224_020000.sql

# Or rollback migrations
php artisan migrate:rollback --step=1
```

### 3. Cache Clear

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Troubleshooting

### Issue: 500 Internal Server Error

**Check logs:**
```bash
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

**Common causes:**
- File permissions
- Missing .env file
- Database connection
- PHP errors

### Issue: Slow Performance

**Check:**
```bash
# PHP-FPM status
sudo systemctl status php8.2-fpm

# Redis status
redis-cli ping

# Database connections
mysql -u root -p -e "SHOW PROCESSLIST;"
```

### Issue: Queue Not Processing

**Check workers:**
```bash
sudo supervisorctl status laravel-worker:*
sudo supervisorctl restart laravel-worker:*
```

### Issue: Cache Not Working

**Test Redis:**
```bash
redis-cli -a password ping
php artisan tinker
Cache::put('test', 'value', 60);
Cache::get('test');
```

---

## Post-Deployment

### 1. Smoke Tests

```bash
# Test homepage
curl -I https://yourdomain.com

# Test API
curl https://yourdomain.com/api/health

# Test database
php artisan tinker
User::count();
```

### 2. Performance Tests

```bash
# Run benchmarks
php artisan benchmark:run

# Load testing with Apache Bench
ab -n 1000 -c 10 https://yourdomain.com/
```

### 3. Security Scan

```bash
# SSL test
https://www.ssllabs.com/ssltest/analyze.html?d=yourdomain.com

# Security headers
https://securityheaders.com/?q=yourdomain.com
```

---

## See Also

- [Database Setup](database-setup.md)
- [Redis Setup](redis-setup.md)
- [Testing Guide](testing.md)
- [Best Practices](best-practices.md)
- [Performance Optimization](../features/performance.md)
- [Security Features](../features/security.md)

---

**Next**: [Best Practices](best-practices.md)
