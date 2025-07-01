# Railway Deployment Guide - KING Shopletix

## Pre-Deployment Checklist

### 1. Railway Account Setup
- Create a Railway account at https://railway.app
- Install Railway CLI: `npm install -g @railway/cli`
- Login: `railway login`

### 2. Project Setup
- Connect your GitHub repository to Railway
- Create a new project: `railway new`

### 3. Required Services
Add these services to your Railway project:

#### Database (MySQL)
```bash
railway add mysql
```

#### Redis (for caching and queues)
```bash
railway add redis
```

## Environment Variables

Set these environment variables in Railway dashboard:

### Application Settings
```
APP_NAME=KING - Shopletix
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_URL=https://your-railway-domain.railway.app
```

### Database (Auto-configured by Railway)
Railway automatically provides:
- `DATABASE_URL`
- `MYSQL_URL` 
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USERNAME`
- `MYSQL_PASSWORD`

Additional settings:
```
DB_CONNECTION=mysql
DB_HOST=${MYSQL_HOST}
DB_PORT=${MYSQL_PORT}
DB_DATABASE=${MYSQL_DATABASE}
DB_USERNAME=${MYSQL_USERNAME}
DB_PASSWORD=${MYSQL_PASSWORD}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### Redis (Auto-configured by Railway)
Railway automatically provides:
- `REDIS_URL`
- `REDIS_HOST`
- `REDIS_PORT`
- `REDIS_PASSWORD`

Additional settings:
```
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_PREFIX=king_prod
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
```

### Security
```
APP_KEY=base64:your-32-character-secret-key-here
BCRYPT_ROUNDS=12
FORCE_HTTPS=true
```

### External Services
```
SHOPIFY_CLIENT_ID=your-shopify-client-id
SHOPIFY_CLIENT_SECRET=your-shopify-client-secret
SHOPIFY_WEBHOOK_SECRET=your-shopify-webhook-secret

STRIPE_PUBLIC_KEY=pk_live_your-stripe-public-key
STRIPE_SECRET_KEY=sk_live_your-stripe-secret-key
STRIPE_WEBHOOK_SECRET=whsec_your-stripe-webhook-secret
```

### Mail Configuration
```
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=KING - Shopletix
```

### Performance & Monitoring
```
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=30
SENTRY_LARAVEL_DSN=your-sentry-dsn-here
```

## Deployment Steps

### 1. Deploy to Railway
```bash
# Connect to your project
railway link

# Deploy
railway up
```

### 2. Run Migrations
```bash
railway run php artisan migrate --force
```

### 3. Seed Initial Data
```bash
railway run php artisan db:seed --class=SuperAdminSeeder
railway run php artisan db:seed --class=PermissionsAndRolesSeeder
```

### 4. Generate Application Key
```bash
railway run php artisan key:generate --force
```

### 5. Cache Configuration
```bash
railway run php artisan config:cache
railway run php artisan route:cache
railway run php artisan view:cache
```

### 6. Storage Link
```bash
railway run php artisan storage:link
```

## Post-Deployment Configuration

### 1. Custom Domain Setup
- Add your custom domain in Railway dashboard
- Update `APP_URL` environment variable
- Configure DNS records

### 2. SSL Certificate
Railway provides automatic SSL certificates for custom domains.

### 3. Worker Processes
Enable worker processes for background jobs:
- Go to Railway dashboard
- Add new service with start command: `php artisan queue:work --sleep=3 --tries=3`

### 4. Scheduled Tasks
Enable cron jobs for scheduled tasks:
- Add new service with start command: `php artisan schedule:work`

## Performance Optimization

### 1. Caching Strategy
- Config cache: `php artisan config:cache`
- Route cache: `php artisan route:cache`
- View cache: `php artisan view:cache`

### 2. Database Optimization
- Enable query caching
- Use database connection pooling
- Optimize database queries

### 3. Redis Configuration
- Use Redis for sessions, cache, and queues
- Configure Redis persistence

## Monitoring & Logging

### 1. Application Monitoring
- Set up Sentry for error tracking
- Use Laravel Telescope for debugging (disable in production)
- Monitor performance metrics

### 2. Health Checks
Access health check endpoint: `https://your-domain.com/health`

### 3. Log Management
- Logs are automatically collected by Railway
- Access logs via Railway dashboard
- Set up log rotation (30 days)

## Security Checklist

- [ ] HTTPS enforced
- [ ] Environment variables secured
- [ ] Database credentials protected
- [ ] API keys secured
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] Input validation implemented
- [ ] SQL injection protection
- [ ] XSS protection enabled

## Backup Strategy

### 1. Database Backups
Railway provides automatic database backups.

### 2. File Backups
Consider using S3 or similar for file storage backups.

## Troubleshooting

### Common Issues
1. **Database Connection**: Check `DATABASE_URL` format
2. **Redis Connection**: Verify `REDIS_URL` configuration
3. **File Permissions**: Ensure storage directories are writable
4. **Memory Limits**: Adjust PHP memory limits if needed

### Debugging
```bash
# View logs
railway logs

# SSH into container
railway shell

# Check application status
railway status
```

## Maintenance Commands

### Update Application
```bash
# Pull latest changes
git pull origin main

# Deploy updates
railway up
```

### Database Maintenance
```bash
# Run new migrations
railway run php artisan migrate --force

# Clear caches
railway run php artisan cache:clear
railway run php artisan config:clear
```

## Support

For issues and support:
- Check Railway documentation
- Review Laravel logs
- Contact development team 