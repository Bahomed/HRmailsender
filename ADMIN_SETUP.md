# Admin Panel Setup & Usage Guide

## Overview
This Laravel application includes a complete admin panel with:
- Admin authentication
- Users CRUD management
- Orders management with SKU scanning
- Barcode scanning functionality
- PDF print capabilities

## Installation & Setup

### 1. Database Configuration
Make sure your `.env` file is configured with the correct database credentials.

### 2. Run Migrations
```bash
php artisan migrate
```

### 3. Create Storage Link
```bash
php artisan storage:link
```

### 4. Seed Admin User
```bash
php artisan db:seed --class=AdminSeeder
```

**Default Admin Credentials:**
- Email: `admin@example.com`
- Password: `password`

**IMPORTANT:** Change the default password after first login!

## Admin Panel Features

### 1. Admin Login
- URL: `/admin/login`
- Login with admin credentials
- Session-based authentication

### 2. Dashboard
- URL: `/admin/dashboard`
- Overview of:
  - Total Users
  - Total Orders
  - Pending Orders
  - Completed Orders
- Quick action buttons

### 3. Users Management (CRUD)
- **List Users:** `/admin/users`
  - View all users
  - See admin status
  - Pagination

- **Create User:** `/admin/users/create`
  - Name, Email, Password
  - Admin checkbox
  - Password confirmation

- **Edit User:** `/admin/users/{id}/edit`
  - Update user details
  - Change admin status
  - Optional password change

- **Delete User:**
  - Cannot delete your own account

### 4. Orders Management
- **List Orders:** `/admin/orders`
  - View all orders
  - See SKU, status, scan time
  - View uploaded files
  - Print PDF
  - Delete orders

### 5. Scan SKU Label - Step 1
- **URL:** `/admin/orders/scan-step1`
- **Features:**
  - Scan or manually enter SKU
  - Real-time duplicate detection
  - If SKU exists: Shows error message
  - If SKU available: Shows success and file upload option
  - Optional file upload (PDF, JPG, PNG - max 10MB)
  - Auto-reset after successful save

**Workflow:**
1. Focus is on SKU input field
2. Scan barcode or type SKU
3. System checks if SKU exists (500ms debounce)
4. If duplicate: Red error message "SKU already exists!"
5. If available: Green success message, file upload appears
6. Optionally upload a file
7. Click "Save Order"
8. Success message shows, form resets for next scan

### 6. Scan & Print Page
- **URL:** `/admin/orders/scan-print`
- **Features:**
  - Scan barcode to find order
  - Display order details
  - Print PDF option
  - View attached files

**Workflow:**
1. Focus is on barcode input
2. Scan barcode or type SKU and press Enter
3. If found: Order details display
4. Click "Print PDF" to open printable page
5. Click "Scan Another" to reset

### 7. PDF Print View
- **URL:** `/admin/orders/{id}/pdf`
- **Features:**
  - Professional order printout
  - Order ID, SKU, Status, Scan time
  - Large SKU display for easy reading
  - Print and Close buttons
  - Print-optimized layout

## Navigation Menu
The admin panel includes a top navigation bar with:
- Dashboard
- Users
- Orders
- Scan Label (Step 1)
- Scan & Print
- Logout

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       ├── AuthController.php
│   │       ├── DashboardController.php
│   │       ├── UserController.php
│   │       └── OrderController.php
│   └── Middleware/
│       └── AdminAuth.php
├── Models/
│   ├── User.php
│   └── Order.php

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php (with is_admin field)
│   └── 2026_01_08_120011_create_orders_table.php
└── seeders/
    └── AdminSeeder.php

resources/
└── views/
    └── admin/
        ├── layout.blade.php
        ├── login.blade.php
        ├── dashboard.blade.php
        ├── users/
        │   ├── index.blade.php
        │   ├── create.blade.php
        │   └── edit.blade.php
        └── orders/
            ├── index.blade.php
            ├── scan-step1.blade.php
            ├── scan-print.blade.php
            └── pdf.blade.php

routes/
└── web.php (admin routes)
```

## Database Schema

### Users Table
- id
- name
- email
- password
- is_admin (boolean, default: false)
- timestamps

### Orders Table
- id
- sku (unique)
- upload_file (nullable)
- status (default: 'pending')
- scanned_at (nullable)
- timestamps

## API Endpoints

### Check SKU Duplicate
- **POST** `/admin/orders/check-sku`
- Body: `{ "sku": "SKU-12345" }`
- Returns: `{ "exists": true/false, "message": "..." }`

### Store Scanned Order
- **POST** `/admin/orders/store-scan`
- Body: FormData with `sku` and optional `upload_file`
- Returns: `{ "success": true, "message": "...", "order": {...} }`

### Find Order by SKU
- **POST** `/admin/orders/find-by-sku`
- Body: `{ "sku": "SKU-12345" }`
- Returns: `{ "success": true/false, "order": {...} }`

## Security Features
- Session-based authentication
- CSRF protection on all forms
- Admin middleware for protected routes
- Password hashing
- File upload validation (type and size)

## Usage Tips

### For Barcode Scanning:
1. Use a USB barcode scanner configured to send Enter after scan
2. The scan fields auto-focus for quick scanning
3. Both scan pages support manual entry as fallback

### For File Uploads:
- Maximum file size: 10MB
- Allowed types: PDF, JPG, JPEG, PNG
- Files stored in `storage/app/public/orders/`
- Accessible via `public/storage/orders/`

### For Printing:
- PDF view opens in new tab
- Print button triggers browser print dialog
- Layout optimized for standard paper sizes
- Large SKU display for scanning

## Customization

### Changing Admin Password:
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('email', 'admin@example.com')->first();
$user->password = bcrypt('new-password');
$user->save();
```

### Creating Additional Admins:
Use the Users CRUD interface or tinker:
```php
App\Models\User::create([
    'name' => 'Admin Name',
    'email' => 'admin2@example.com',
    'password' => bcrypt('password'),
    'is_admin' => true,
]);
```

## Troubleshooting

### Storage Link Not Working:
```bash
php artisan storage:link
```

### Migrations Already Run:
```bash
php artisan migrate:fresh --seed
```
**WARNING:** This will delete all data!

### Session Issues:
```bash
php artisan config:clear
php artisan cache:clear
```

## Development Server
```bash
php artisan serve
```

Access admin panel at: `http://localhost:8000/admin/login`

## Production Deployment
1. Set `APP_ENV=production` in `.env`
2. Run `php artisan config:cache`
3. Run `php artisan route:cache`
4. Run `php artisan view:cache`
5. Ensure proper file permissions on `storage/` directory
6. Configure web server (Nginx/Apache) to serve `public/` directory

---

**Created:** January 8, 2026
**Laravel Version:** 12.x
