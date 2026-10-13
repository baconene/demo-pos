# DEMO POS - Food & Beverage Ordering System

A modern, production-ready Food & Beverage Ordering System built with Laravel 13, Vue 3, and modern technologies for restaurant/café operations.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [API Documentation](#api-documentation)
- [User Roles & Permissions](#user-roles--permissions)
- [Architecture](#architecture)
- [Deployment](#deployment)
- [Support](#support)

## Features

### Core Features
- **POS System**: Fast, intuitive point-of-sale interface with cart management
- **Kitchen Queue Management**: Real-time kitchen display system with order tracking
- **Inventory Management**: Automatic inventory deduction, low stock alerts, recipe management
- **Billing & Payments**: Multiple payment methods, receipts, refunds, split payments
- **Reporting System**: Daily/Monthly sales, product analytics, inventory movement
- **Audit Trail**: Comprehensive activity logging and user tracking
- **Real-time Updates**: WebSocket support for live queue updates and notifications

### User Roles
1. **Cashier**: Create orders, process payments, view menu, manage discounts
2. **Kitchen/Queue Staff**: View incoming orders, update preparation status, queue management
3. **Auditor**: Monitor inventory, approve stock adjustments, view reports, audit logs
4. **Administrator**: Full system access, user management, configuration

### Additional Features
- Role-based access control (RBAC)
- Dark mode support
- Responsive design (mobile, tablet, desktop)
- Barcode scanner support ready
- Thermal printer support ready
- Multi-branch architecture
- Email receipts
- Backup & restore capabilities

## Tech Stack

### Backend
- **Framework**: Laravel 13
- **PHP**: 8.3+
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **Authorization**: Spatie Laravel Permission
- **Queue**: Laravel Queue/Jobs
- **Broadcasting**: Pusher / Laravel Echo

### Frontend
- **Framework**: Vue 3 with Composition API
- **Build Tool**: Vite
- **State Management**: Pinia
- **UI Components**: Tailwind CSS + Custom components
- **HTTP Client**: Axios
- **Real-time**: Laravel Echo

### DevOps
- **Server**: Apache/Nginx
- **Cache**: Redis (recommended)
- **Job Processing**: Supervisor
- **Monitoring**: Laravel Telescope (optional)

## Installation

### Prerequisites
- PHP 8.3 or higher
- Composer
- Node.js 18+ and npm/pnpm
- MySQL 8.0+
- Git

### Step 1: Clone Repository
```bash
git clone <repository-url>
cd bypassgrill
```

### Step 2: Install PHP Dependencies
```bash
composer install
```

### Step 3: Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### Step 4: Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bypassgrill
DB_USERNAME=root
DB_PASSWORD=
```

### Step 5: Run Migrations & Seeders
```bash
php artisan migrate
php artisan db:seed
```

This will create:
- All database tables
- User roles and permissions
- Demo users (see credentials below)
- Sample products and categories
- Ingredients and recipes

### Step 6: Install Frontend Dependencies
```bash
npm install
# or
pnpm install
```

### Step 7: Build Assets
```bash
npm run build
# For development with hot reload:
npm run dev
```

## Configuration

### Broadcasting Setup (Optional - for real-time features)

#### Using Pusher
1. Get credentials from [Pusher.com](https://pusher.com)
2. Update `.env`:
```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

#### Using WebSocket Server
```bash
npm install -g laravel-echo-server
laravel-echo-server init
laravel-echo-server start
```

### Mail Configuration
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@bypassgrill.local
```

### Queue Configuration
```env
QUEUE_CONNECTION=redis
```

Start queue worker:
```bash
php artisan queue:work redis
```

## Database Setup

### Schema Overview

#### Users & Authentication
- `users`: User accounts with email and password
- `roles`: User roles (cashier, kitchen, auditor, admin)
- `permissions`: System permissions
- `model_has_roles`: User role assignments
- `model_has_permissions`: Direct user permissions

#### Products & Catalog
- `categories`: Product categories
- `products`: Menu items
- `modifiers`: Add-ons (Extra Cheese, Bacon, etc.)
- `product_modifier`: Product to modifier relationship

#### Orders & Transactions
- `orders`: Customer orders
- `order_items`: Individual items in orders
- `order_item_modifiers`: Modifiers applied to order items
- `queue_numbers`: Queue number tracking
- `payments`: Payment transactions
- `refunds`: Refund records

#### Inventory
- `ingredients`: Raw materials
- `recipes`: Product recipes (product composition)
- `suppliers`: Supplier information
- `purchase_orders`: Purchase orders from suppliers
- `purchase_order_items`: Items in purchase orders
- `inventory_transactions`: Stock movement records

#### Audit & Reporting
- `audit_logs`: Activity tracking

## API Documentation

### Base URL
```
http://localhost/api/v1
```

### Authentication
All protected endpoints require Bearer token:
```
Authorization: Bearer {token}
```

### Endpoints

#### Public Endpoints

**Get Categories**
```
GET /categories
```

**Get Products**
```
GET /products
GET /products/category/{categoryId}
GET /products/search?q=query
GET /products/{productId}
```

#### Orders (Protected)

**Create Order**
```
POST /orders
Content-Type: application/json

{
  "order_type": "dine_in",
  "table_number": "5",
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "modifiers": [1, 2]
    }
  ],
  "notes": "No onions"
}
```

**Get Orders**
```
GET /orders
GET /orders/{orderId}
GET /orders/active
GET /orders/queue
```

**Update Order Status**
```
PATCH /orders/{orderId}/status
{
  "status": "preparing"
}
```

**Cancel Order**
```
POST /orders/{orderId}/cancel
{
  "reason": "Customer requested cancellation"
}
```

#### Payments (Protected)

**Process Payment**
```
POST /payments
{
  "order_id": 1,
  "amount": 500.00,
  "method": "cash",
  "reference": "Receipt123"
}
```

**Get Order Payments**
```
GET /orders/{orderId}/payments
```

**Refund Payment**
```
POST /payments/{paymentId}/refund
{
  "amount": 500.00,
  "reason": "Customer returned items"
}
```

#### Inventory (Protected)

**Get Inventory**
```
GET /inventory
GET /inventory/low-stock
```

**Adjust Inventory**
```
POST /inventory/adjust
{
  "ingredient_id": 1,
  "quantity": 10,
  "type": "stock_in",
  "notes": "Received from supplier"
}
```

**Get Ingredient Transactions**
```
GET /inventory/{ingredientId}/transactions
```

#### Reports (Protected)

**Daily Sales Report**
```
GET /reports/daily-sales?date=2025-05-12
```

**Monthly Sales Report**
```
GET /reports/monthly-sales?year=2025&month=5
```

**Product Sales Report**
```
GET /reports/product-sales?start_date=2025-05-01&end_date=2025-05-12
```

**Inventory Valuation**
```
GET /reports/inventory-valuation
```

## User Roles & Permissions

### Demo User Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@demo-pos.invalid | password |
| Cashier 1 | maria@bypassgrill.local | password |
| Cashier 2 | john@bypassgrill.local | password |
| Kitchen 1 | rosa@bypassgrill.local | password |
| Kitchen 2 | pedro@bypassgrill.local | password |
| Auditor | anna@bypassgrill.local | password |

### Permissions Matrix

#### Cashier Permissions
- ✅ Create orders
- ✅ View orders
- ✅ Update pending orders
- ✅ Process payments
- ✅ View inventory
- ❌ Manage inventory
- ❌ View reports

#### Kitchen Permissions
- ✅ View orders
- ✅ Update order status
- ✅ View inventory
- ❌ Create orders
- ❌ Process payments
- ❌ Manage inventory

#### Auditor Permissions
- ✅ View orders
- ✅ View inventory
- ✅ Manage inventory
- ✅ View reports
- ✅ Export reports
- ❌ Create orders
- ❌ Process payments

#### Admin Permissions
- ✅ All permissions

## Architecture

### Folder Structure

```
app/
├── Enums/              # Status enums
├── Events/             # Broadcasting events
├── Http/
│   ├── Controllers/Api/V1/    # API controllers
│   ├── Requests/       # Form request validation
│   └── Resources/      # API response formatters
├── Jobs/               # Queue jobs
├── Models/             # Eloquent models
├── Notifications/      # Notification classes
├── Repositories/       # Data access layer
└── Services/           # Business logic

resources/js/
├── composables/        # Vue composables
├── pages/              # Vue page components
├── stores/             # Pinia stores
├── utils/              # Utility functions
└── components/         # Reusable components

database/
├── migrations/         # Database migrations
└── seeders/            # Database seeders
```

### Design Patterns

1. **Repository Pattern**: Data access abstraction
2. **Service Layer**: Business logic encapsulation
3. **Form Requests**: Input validation
4. **API Resources**: Response transformation
5. **Events**: Broadcasting and notifications
6. **Jobs**: Asynchronous processing

## Deployment

### Production Checklist

- [ ] Update `.env` for production
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Generate new `APP_KEY`
- [ ] Configure database with production credentials
- [ ] Setup Redis for caching and queues
- [ ] Configure Pusher or WebSocket server
- [ ] Setup mail service
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed data: `php artisan db:seed`
- [ ] Optimize performance: `php artisan optimize`
- [ ] Cache configuration: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`

### Using Docker

```dockerfile
FROM php:8.3-fpm
# ... additional setup ...
```

### Systemd Service (Queue Worker)

Create `/etc/systemd/system/bypassgrill-queue.service`:

```ini
[Unit]
Description=DEMO POS Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/bypassgrill
ExecStart=/usr/bin/php artisan queue:work redis --tries=3
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Then:
```bash
sudo systemctl enable bypassgrill-queue
sudo systemctl start bypassgrill-queue
```

## Running the Application

### Development
```bash
# In separate terminals:
php artisan serve          # Backend
npm run dev               # Frontend  
php artisan queue:listen  # Queue worker
```

Or use the combined command:
```bash
composer run dev
```

### Production
```bash
php artisan serve --host=0.0.0.0 --port=8000
npm run build
php artisan queue:work redis --daemon
```

## Testing

Run tests:
```bash
php artisan test
```

Run tests with coverage:
```bash
php artisan test --coverage
```

## Common Tasks

### Create a new user
```bash
php artisan tinker
>>> $user = User::create(['name' => 'John', 'email' => 'john@test.local', 'password' => bcrypt('password')]);
>>> $user->assignRole('cashier');
```

### Revert migrations
```bash
php artisan migrate:rollback
```

### Clear cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### View database
```bash
php artisan tinker
# or
mysql -u root bypassgrill
```

## Troubleshooting

### "SQLSTATE[HY000] [2002] Connection refused"
- Check MySQL is running
- Verify DB_* credentials in `.env`

### "Spatie\Permission\Exceptions\UnauthorizedException"
- Run `php artisan migrate`
- Run `php artisan db:seed`

### Queue jobs not processing
- Check `QUEUE_CONNECTION` in `.env`
- Ensure queue worker is running
- Check logs: `storage/logs/laravel.log`

### Real-time updates not working
- Check Pusher credentials
- Verify Broadcasting configuration
- Check browser console for WebSocket errors

## Support

For issues and feature requests:
1. Check existing documentation
2. Review code comments
3. Check Laravel and Vue.js official documentation
4. Submit issues with detailed information

## License

MIT License - see LICENSE file for details

## Contributors

- Development Team

## Changelog

### Version 1.0.0
- Initial release
- Full POS system
- Kitchen queue management
- Inventory management
- Reporting system
- RBAC implementation
- Real-time features
