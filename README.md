# HRMailSender - Secure Email Relay Service

A standalone Laravel application that provides email sending service via API with built-in security features. This service is designed to work with injazathr on droplets where SMTP ports are blocked.

## Purpose

Digital Ocean blocks SMTP ports (25, 465, 587) on new droplets. This service runs on a droplet where SMTP ports are open and provides a secure HTTP API for other applications to send emails.

## Security Features

- API Key authentication
- IP whitelisting support
- Rate limiting (60 requests/minute)
- Request validation
- Secure file handling
- Automatic temporary file cleanup
- Comprehensive logging

## Installation

### 1. Server Requirements

- PHP 8.2 or higher
- Composer
- Web server (Nginx/Apache)
- Open SMTP ports (25, 465, 587)

### 2. Setup

```bash
# Navigate to web root
cd /var/www

# Upload/clone this application
cd hrmailsender

# Install dependencies
composer install --optimize-autoloader --no-dev

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Configure environment
php artisan key:generate
```

### 3. Configure Environment

Edit `.env`:

```env
APP_NAME=HRMailSender
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

# Security
API_KEY=your-super-secret-api-key-here-change-this
ALLOWED_IPS=your-injazathr-server-ip,another-ip

# Default SMTP settings (optional - can be overridden per request)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 4. Web Server Configuration

#### Nginx Configuration

Create `/etc/nginx/sites-available/hrmailsender`:

```nginx
server {
    listen 80;
    server_name your-server-ip;
    root /var/www/hrmailsender/public;

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

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/hrmailsender /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Test the Installation

```bash
curl http://your-server-ip/api/health
```

Expected response:

```json
{
    "status": "ok",
    "service": "hrmailsender",
    "timestamp": "2026-01-06 12:00:00"
}
```

## API Documentation

### Authentication

All API requests (except health check) require authentication via API key:

```bash
-H "X-API-Key: your-api-key-here"
```

### Endpoints

#### 1. Health Check (No Auth Required)

```http
GET /api/health
```

**Response:**

```json
{
    "status": "ok",
    "service": "hrmailsender",
    "timestamp": "2026-01-06 12:00:00"
}
```

#### 2. Send Email (Auth Required)

```http
POST /api/send-email
Content-Type: application/json
X-API-Key: your-api-key-here
```

**Request Body:**

```json
{
    "to": "recipient@example.com",
    "subject": "Email Subject",
    "body": "<h1>Hello</h1><p>This is the email body</p>",
    "attachment_base64": "base64_encoded_file_content",
    "attachment_name": "document.pdf",
    "smtp_settings": {
        "host": "smtp.gmail.com",
        "port": 587,
        "username": "your-email@gmail.com",
        "password": "your-app-password",
        "encryption": "tls",
        "from_email": "your-email@gmail.com",
        "from_name": "HR System"
    }
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| to | string | Yes | Recipient email address |
| subject | string | Yes | Email subject |
| body | string | Yes | Email body (HTML supported) |
| attachment_base64 | string | No | Base64 encoded file content |
| attachment_name | string | No | Attachment filename |
| smtp_settings | object | No | Custom SMTP settings for this email |

**Success Response (200):**

```json
{
    "success": true,
    "message": "Email sent successfully"
}
```

**Error Response (401 - Unauthorized):**

```json
{
    "error": "Unauthorized"
}
```

**Error Response (403 - IP Not Allowed):**

```json
{
    "error": "IP address not allowed"
}
```

**Error Response (422 - Validation Error):**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "to": ["The to field is required."]
    }
}
```

**Error Response (429 - Rate Limit):**

```json
{
    "message": "Too Many Attempts."
}
```

## Usage Examples

### With API Key

```bash
curl -X POST http://your-server-ip/api/send-email \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key-here" \
  -d '{
    "to": "user@example.com",
    "subject": "Test Email",
    "body": "<p>This is a test email</p>",
    "smtp_settings": {
        "host": "smtp.gmail.com",
        "port": 587,
        "username": "your-email@gmail.com",
        "password": "your-app-password",
        "encryption": "tls",
        "from_email": "your-email@gmail.com",
        "from_name": "HR System"
    }
  }'
```

### PHP Example (injazathr Integration)

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'X-API-Key' => config('mail.external_sender_api_key')
])->post(config('mail.external_sender_url') . '/api/send-email', [
    'to' => 'user@example.com',
    'subject' => 'Document',
    'body' => '<p>Please find attached</p>',
    'smtp_settings' => [
        'host' => $smtpSettings->mail_host,
        'port' => $smtpSettings->mail_port,
        'username' => $smtpSettings->mail_username,
        'password' => $smtpSettings->mail_password,
        'encryption' => 'tls',
        'from_email' => $smtpSettings->mail_from_email,
        'from_name' => $smtpSettings->mail_from_name
    ]
]);
```

## Integration with injazathr

In your injazathr application `.env`:

```env
USE_EXTERNAL_MAIL_SENDER=true
EXTERNAL_MAIL_SENDER_URL=http://your-old-droplet-ip
EXTERNAL_MAIL_SENDER_API_KEY=your-api-key-here
```

The ProcessEmail job in injazathr will automatically use this service when configured.

## Security Best Practices

### 1. Use HTTPS (Highly Recommended)

Install SSL certificate:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

Update injazathr `.env`:

```env
EXTERNAL_MAIL_SENDER_URL=https://yourdomain.com
```

### 2. Strong API Key

Generate a strong API key:

```bash
php artisan tinker
>>> Str::random(64)
```

Use this in your `.env`:

```env
API_KEY=the-generated-64-character-key
```

### 3. IP Whitelisting

In hrmailsender `.env`:

```env
ALLOWED_IPS=192.168.1.100,192.168.1.101
```

Leave empty to allow all IPs (not recommended for production).

### 4. Firewall Configuration

```bash
# Allow HTTP/HTTPS only
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 5. Regular Updates

```bash
composer update
php artisan optimize
```

## Monitoring

### Check Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Failed email attempts
grep "Email sending failed" storage/logs/laravel.log

# Unauthorized access attempts
grep "Unauthorized" storage/logs/laravel.log
```

### Monitor Disk Space

```bash
# Check temp directory
du -sh storage/app/temp/

# Clean old files (older than 1 day)
find storage/app/temp/ -type f -mtime +1 -delete
```

## Troubleshooting

### Email Not Sending

1. Check API key in request headers
2. Verify SMTP credentials
3. Check Laravel logs: `storage/logs/laravel.log`
4. Test SMTP connection: `telnet smtp.gmail.com 587`

### 401 Unauthorized

1. Verify API key is correct in both applications
2. Check API key header name: `X-API-Key`
3. Ensure no trailing spaces in API key

### 403 IP Not Allowed

1. Check ALLOWED_IPS in `.env`
2. Verify requesting server IP
3. Clear config cache: `php artisan config:clear`

### 429 Rate Limit

1. Current limit: 60 requests/minute
2. Implement queue system in injazathr
3. Adjust rate limit in middleware if needed

## Performance Tuning

### Enable OPcache

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### Optimize Composer Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

### Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
```

## Maintenance

### Daily Tasks

- Monitor disk space
- Check error logs
- Verify service is responding

### Weekly Tasks

- Review access logs
- Clean old temp files
- Check for composer updates

### Monthly Tasks

- Update dependencies
- Review security logs
- Backup configuration

## License

Private internal service for injazathr email relay.
