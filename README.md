# Advanced Point Of Sale

A comprehensive Point of Sale (POS) system designed for retail shops with multi-store support, built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### 🔐 User Roles & Access Control

1. **Super Admin**
   - Manages multiple stores
   - Creates Admin accounts per store
   - Views analytics across all stores
   - Enables/disables stores
   - Resets admin passwords
   - Default credentials: `vivekan` / `vivekan1409`

2. **Admin (Store-Specific)**
   - Manages products and categories
   - Manages cashiers for their store
   - Views sales reports
   - Manages inventory
   - Sets tax and discount rules

3. **Cashier (Store-Specific)**
   - Performs sales transactions
   - Scans or selects products
   - Applies discounts
   - Generates and prints receipts

### 🏬 Multi-Store Support

- One Super Admin → Multiple Stores
- One Store → Multiple Admins & Cashiers
- Complete data isolation between stores
- Store ID enforced at login, sessions, and database queries

### 🧱 Core Modules

- **Authentication & Authorization**: Secure login with password hashing, role-based access, session management
- **Store Management**: Create, edit, activate/deactivate stores (Super Admin only)
- **Product Management**: Products with SKU, barcode, categories, pricing, tax, stock tracking, **product images**
- **Sales & POS Interface**: Fast POS screen with product search, **visual product cards with images**, cart system, tax calculation, multiple payment methods
- **Reports & Analytics**: Daily/monthly sales, best-selling products, cashier performance, profit calculation

### 🖼️ Product Image Management

- **Image Upload**: Upload product images (JPG, PNG, WEBP, max 2MB)
- **Image Update**: Replace existing product images
- **Image Delete**: Remove product images
- **POS Display**: Beautiful product cards with images in POS interface
- **Fallback Images**: Automatic fallback for missing images
- **Security**: MIME type validation, file size limits, secure file naming

## Installation

### Prerequisites

- XAMPP (or similar) with PHP 7.4+ and MySQL 5.7+ / MariaDB 10.4+
- Web server (Apache)
- Modern web browser
- PHP extensions: mysqli, gd (for image handling)

### Setup Steps

1. **Clone/Download the project** to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\Pos
   ```

2. **Create the database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `pos_system`
   - Import the SQL dump file: `database/pos_system.sql`
   - This will create all tables with sample data including the `product_image` column

3. **Initialize Super Admin password**:
   - Access: http://localhost/Pos/setup/init_admin.php
   - This will set the correct password hash for the Super Admin
   - **IMPORTANT**: Delete `setup/init_admin.php` after running it for security!

4. **Configure database connection** (if needed):
   - Edit `config/database.php`
   - Update DB_HOST, DB_USER, DB_PASS, DB_NAME if different from defaults
   - Default settings:
     - Host: `localhost`
     - User: `root`
     - Password: `` (empty)
     - Database: `pos_system`

5. **Set up uploads directory** (if needed):
   - Ensure `uploads/products/` directory exists and is writable
   - The directory should have proper permissions for file uploads

6. **Access the application**:
   - Open browser: http://localhost/Pos/
   - Login with Super Admin credentials: `vivekan` / `contactforpqq`

## Database Schema

The system includes the following tables:

- `roles` - User roles (super_admin, admin, cashier)
- `stores` - Store information
- `users` - User accounts with role and store assignment
- `categories` - Product categories (store-specific)
- `products` - Products with pricing, stock, tax information
- `sales` - Sales transactions
- `sale_items` - Individual items in each sale
- `payments` - Payment information for each sale
- `inventory_logs` - Inventory transaction history

**Note**: The `products` table includes a `product_image` column for storing product image filenames.

## Project Structure

```
Pos/
├── api/
│   └── process_sale.php       # API endpoint for processing sales
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── main.js            # Main JavaScript file
├── admin/
│   ├── dashboard.php          # Admin dashboard
│   ├── products.php           # Product management
│   ├── categories.php          # Category management
│   ├── cashiers.php           # Cashier management
│   ├── inventory.php          # Inventory management
│   ├── reports.php            # Sales reports
│   └── settings.php           # Settings
├── cashier/
│   ├── pos.php                # Point of Sale interface
│   ├── sales.php              # Sales history
│   └── receipt.php            # Receipt print view
├── config/
│   ├── config.php             # Main configuration
│   └── database.php           # Database connection
├── database/
│   └── pos_system.sql         # Complete database dump with schema and data
├── includes/
│   ├── header.php             # Header template
│   ├── footer.php             # Footer template
│   └── upload_handler.php     # Image upload handler
├── uploads/
│   └── products/              # Product images directory
├── super_admin/
│   ├── dashboard.php          # Super Admin dashboard
│   ├── stores.php             # Store management
│   ├── admins.php             # Admin management
│   └── analytics.php          # Analytics across all stores
├── index.php                  # Login page
├── logout.php                 # Logout handler
└── README.md                  # This file
```

## Security Features

- ✅ Password hashing using `password_hash()`
- ✅ SQL injection protection with prepared statements
- ✅ Role-based page access control
- ✅ CSRF token protection
- ✅ Input validation and sanitization
- ✅ Session management with timeout
- ✅ Store isolation enforcement

## Usage Guide

### Super Admin

1. **Login** with default credentials: `vivekan` / `vivekan1409`
2. **Create Stores**: Go to Stores → Create New Store
3. **Create Admins**: Go to Admins → Create New Admin (assign to a store)
4. **View Analytics**: Check Analytics page for cross-store performance

### Admin

1. **Login** with admin credentials (created by Super Admin)
2. **Manage Products**: Create and manage products for your store
3. **Manage Categories**: Organize products into categories
4. **Manage Cashiers**: Create cashier accounts for your store
5. **View Reports**: Check sales reports and analytics
6. **Monitor Inventory**: Track stock levels and low stock alerts

### Cashier

1. **Login** with cashier credentials (created by Admin)
2. **POS Interface**: Use the POS screen to process sales
3. **Search Products**: Type product name, SKU, or scan barcode
4. **Add to Cart**: Click products or scan barcodes
5. **Apply Discounts**: Set discount percentage if needed
6. **Process Payment**: Select payment method and complete sale
7. **Print Receipt**: Option to print receipt after sale

## Default Credentials

- **Super Admin**: 
  - Username: `vivekan`
  - Password: `vivekan1409`

⚠️ **Important**: Change the Super Admin password after first login in production!

## Technical Details

- **Backend**: PHP (Procedural with some OOP concepts)
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Database**: MySQL / MariaDB
- **Session Management**: PHP Sessions with timeout (1 hour)
- **Password Hashing**: bcrypt via `password_hash()` with PASSWORD_DEFAULT
- **Security**: Prepared statements, CSRF tokens, input sanitization
- **File Uploads**: Secure image upload with MIME type validation
- **Libraries**: SweetAlert2 for notifications, Font Awesome for icons

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Edge (latest)
- Safari (latest)

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify XAMPP MySQL service is running
   - Check database credentials in `config/database.php`
   - Ensure database `pos_system` exists

2. **Login Not Working**
   - Run `setup/init_admin.php` to initialize Super Admin password
   - Clear browser cache and cookies
   - Check PHP error logs for details

3. **Image Upload Not Working**
   - Ensure `uploads/products/` directory exists
   - Check directory permissions (should be writable)
   - Verify PHP `upload_max_filesize` and `post_max_size` settings
   - Check PHP `gd` extension is enabled

4. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check `php.ini` session settings
   - Clear browser cookies

5. **Page Access Denied**
   - Verify you're logged in with correct role
   - Check session timeout (1 hour default)
   - Ensure store is active (for Admin/Cashier roles)

6. **Products Not Showing in POS**
   - Verify products are set to "active" status
   - Check store_id matches current user's store
   - Ensure products have stock quantity > 0

## License

This project is provided as-is for educational and commercial use.

## Support

For issues or questions, please refer to the code comments or contact the development team.

---

**Version**: 1.0.0  
**Last Updated**: December 2025

