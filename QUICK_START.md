# Quick Start Guide

## 5-Minute Setup

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Create Database
```bash
mysql -u root -p -e "CREATE DATABASE bypassgrill CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set:
```env
DB_DATABASE=bypassgrill
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

### 4. Run Migrations & Seeds
```bash
php artisan migrate
php artisan db:seed
```

### 5. Build & Run
```bash
npm run build
php artisan serve
```

Visit: `http://localhost:8000`

## Test Users

After seeding, login with any of these:

**Admin**
- Email: `admin@demo-pos.invalid`
- Password: `password`

**Cashier**
- Email: `maria@bypassgrill.local`
- Password: `password`

**Kitchen**
- Email: `rosa@bypassgrill.local`
- Password: `password`

**Auditor**
- Email: `anna@bypassgrill.local`
- Password: `password`

## Development Workflow

### Terminal 1: PHP Server
```bash
php artisan serve
```

### Terminal 2: Frontend Dev Server  
```bash
npm run dev
```

### Terminal 3: Queue Worker (Optional)
```bash
php artisan queue:listen
```

## First Time Checklist

- [ ] Database created
- [ ] Migrations run
- [ ] Seeds applied
- [ ] Laravel serve running on port 8000
- [ ] NPM dev server running on port 5173
- [ ] Can login with test credentials
- [ ] POS page loads correctly
- [ ] Products display in cart

## Troubleshooting

**Port 8000 already in use:**
```bash
php artisan serve --port=8001
```

**npm run dev fails:**
```bash
rm -rf node_modules package-lock.json
npm install
npm run dev
```

**Database connection error:**
- Ensure MySQL is running
- Check DB credentials in `.env`
- Try: `mysql -u root -p -e "SHOW DATABASES;"`

**Permission denied errors:**
```bash
chmod -R 755 storage bootstrap/cache
```

## Next Steps

1. Read [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md) for full features
2. Review API endpoints in documentation
3. Explore Vue components in `resources/js/pages/`
4. Check user roles and permissions in seeder
5. Configure real-time features (Pusher/WebSockets)

## Quick Links

- Dashboard: `http://localhost:8000`
- API Docs: See SYSTEM_DOCUMENTATION.md
- Database: `mysql bypassgrill -u root -p`
- Queue Monitoring: `http://localhost:8000/jobs`
- Logs: `storage/logs/laravel.log`
