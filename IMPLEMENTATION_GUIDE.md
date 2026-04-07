# London Labels E-Commerce Web Application
## Implementation & Setup Guide

**Project:** Design and Development of a PHP-Based E-Commerce Web Application for London Labels  
**Date:** February 26, 2026  
**Status:** Foundation Complete - Ready for Testing  

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Database Setup](#database-setup)
4. [File Structure](#file-structure)
5. [Key Features](#key-features)
6. [User Roles & Access Control](#user-roles--access-control)
7. [Setup Instructions](#setup-instructions)
8. [Testing the Application](#testing-the-application)
9. [Next Steps & Future Enhancements](#next-steps--future-enhancements)

---

## Project Overview

London Labels is a real small-to-medium retail business in Lagos, Nigeria. This web application provides:

- **For Customers:** Browse products, search by category, manage shopping cart, checkout, order history
- **For Admins:** Manage products, categories, orders, and users
- **Security:** CSRF protection, password hashing, role-based access control, session management
- **Database:** MySQL with 6 normalized tables (Users, Categories, Products, Product_Images, Orders, Order_Items)

---

## System Architecture

### Tech Stack
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3 (custom responsive design)
- **Session Management:** PHP Sessions
- **Authentication:** Password hashing (bcrypt), CSRF tokens, 2FA ready

### Core Components

1. **Database Layer** (`db.php`, `db_functions.php`)
   - PDO connection management
   - Helper functions for all CRUD operations
   - Normalized 3NF schema

2. **Authentication** (`functions.php`, `login.php`, `register.php`)
   - User registration and login
   - Session-based authentication
   - Password hashing with bcrypt
   - CSRF protection on all forms

3. **Frontend** (`index.php`, `shop.php`, `categories.php`, `product.php`)
   - Public storefront with search and filtering
   - Responsive product grid layout
   - Dynamic navigation based on user roles

4. **Shopping System** (`cart.php`, `checkout.php`, `order-confirmation.php`)
   - Session-based shopping cart
   - Order creation and management
   - Order history for customers

5. **Admin Panel** (`admin/dashboard.php`, `admin/products.php`, etc.)
   - Complete CRUD for products, categories, orders
   - User management
   - Order status tracking
   - Statistics dashboard

---

## Database Setup

### Creating the Database

1. **Open phpMyAdmin** or MySQL command line
2. **Create database:**
   ```sql
   CREATE DATABASE londonlabels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Run the schema:**
   - Copy contents of `schema.sql`
   - Paste and execute in phpMyAdmin
   - OR from terminal: `mysql -u root londolabels < schema.sql`

### Default Sample Data Created

**Admin Account:**
- Username: `admin`
- Email: `admin@londonlabels.com`
- Password: `admin123`

**Customer Account:**
- Username: `customer`
- Email: `customer@example.com`
- Password: `customer123`

**Categories:** Fashion, Technology, Accessories  
**Sample Products:** 5 products across categories

---

## File Structure

```
LondonLabels/
├── index.php                    # Homepage
├── register.php                 # User registration
├── login.php                    # User login
├── logout.php                   # Logout
├── shop.php                     # Shopping with filtering
├── categories.php               # Category listing
├── product.php                  # Product details
├── cart.php                     # Shopping cart management
├── checkout.php                 # Checkout process
├── order-confirmation.php       # Order confirmation
├── contact.php                  # Contact form
│
├── account/                     # Customer account pages
│   ├── profile.php              # Profile view
│   └── orders.php               # Order history
│
├── admin/                       # Admin dashboard
│   ├── dashboard.php            # Admin home
│   ├── products.php             # Product list
│   ├── product-add.php          # Add product
│   ├── product-edit.php         # Edit product
│   ├── product-delete.php       # Delete product
│   ├── categories.php           # Category management
│   ├── users.php                # User list
│   ├── user-edit.php            # Edit user
│   ├── user-delete.php          # Delete user
│   ├── orders.php               # Order list
│   └── order-edit.php           # Order details & status
│
├── legal/                       # Legal & informational
│   ├── about.php                # About page
│   ├── privacy.php              # Privacy policy
│   └── terms.php                # Terms of service
│
├── config.php                   # Configuration (DB, mail, URLs)
├── db.php                       # Database connection
├── db_functions.php             # Database helper functions
├── functions.php                # General helpers (auth, csrf, etc.)
├── mailer.php                   # Email configuration
│
├── inc_header.php               # Header/navigation template
├── inc_footer.php               # Footer template
│
├── assets/
│   └── style.css                # Main stylesheet
│
├── schema.sql                   # Database schema
│
├── Uploads/
│   └── Products/                # Product image directory
│
└── PHPMailer/                   # Email sending library
```

---

## Key Features

### 1. User Authentication
- **Registration:** Email and username validation, password confirmation
- **Login:** Secure password verification, session creation
- **Role-Based Access:** Admin vs. Customer roles with access control
- **CSRF Protection:** All forms protected with CSRF tokens
- **Session Management:** Regeneration on login, destruction on logout

### 2. Product Management
- **Browse:** All products with pagination
- **Search:** Full-text search by product name/description
- **Filter:** By category
- **Details:** Product page with images, description, stock status
- **Admin CRUD:** Create, read, update, delete products

### 3. Shopping & Orders
- **Cart:** Session-based cart management, update quantities, remove items
- **Checkout:** Multi-step checkout process
- **Order Confirmation:** Immediate confirmation with order details
- **Order History:** Customers can view past orders
- **Admin Tracking:** View all orders, update status (pending/completed/cancelled)

### 4. Admin Dashboard
- **Statistics:** Total users, products, orders, revenue
- **Quick Actions:** Links to all management sections
- **Product Management:** Full CRUD interface
- **Order Management:** View and update order statuses
- **User Management:** View users, filter by role, delete if needed
- **Category Management:** Add and delete categories

### 5. Responsive Design
- **Mobile-Friendly:** Grid layouts adapt to screen size
- **Navigation:** Persistent header with dropdown menus
- **Tables:** Scrollable on mobile, full on desktop
- **Forms:** Full-width inputs, clear labels and validation

---

## User Roles & Access Control

### Public (Not Logged In)
- View homepage
- View categories and products
- View legal pages (About, Privacy, Terms)
- Access login/register pages

### Customer (Logged In, role='customer')
- Browse shop with full functionality
- Add to cart and checkout
- View order history and details
- Update profile

### Admin (Logged In, role='admin')
- Access admin dashboard
- Full CRUD access to products, categories, orders, users
- View site statistics
- Manage order statuses

---

## Setup Instructions

### 1. Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PHPMailer library (included in `/PHPMailer`)

### 2. Installation Steps

**Step 1: Create Database**
```bash
mysql -u root -p < schema.sql
```

**Step 2: Configure Settings**
- Edit `config.php`:
  - Update `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` if needed
  - Update `BASE_URL` to your installation path (default: `/LondonLabels`)

**Step 3: Set File Permissions**
```bash
chmod 755 Uploads/
chmod 755 Uploads/Products/
```

**Step 4: Test Connection**
- Visit `http://localhost/LondonLabels/index.php`
- You should see the homepage (redirects to login if not logged in)

### 3. First Time Usage

**Login as Admin:**
1. Go to `http://localhost/LondonLabels/login.php`
2. Email: `admin@londonlabels.com`
3. Password: `admin123`
4. You'll be redirected to admin dashboard

**Create Test Products:**
1. Go to Admin Dashboard
2. Click "Admin Products" or navigate to `admin/products.php`
3. Click "Add Product"
4. Fill in details and save

**Browse as Customer:**
1. Logout and create a new customer account
2. Or login: customer@example.com / customer123
3. Browse products, add to cart, checkout

---

## Testing the Application

### Test Scenarios

**Scenario 1: Customer Registration & Shopping**
1. Register new account
2. Browse shop with categories
3. Search for product
4. View product details
5. Add to cart
6. Update cart quantities
7. Proceed to checkout
8. View order confirmation
9. Check order history

**Scenario 2: Admin Product Management**
1. Login as admin
2. Add new product
3. Edit existing product
4. Delete product
5. View all products

**Scenario 3: Order Management**
1. Login as admin
2. View all orders
3. View customer details
4. Update order status
5. Filter by status

**Scenario 4: Security**
1. Test CSRF protection (disable token or use wrong token)
2. Test role-based access (logged-out user tries accessing `/admin/`)
3. Test password validation on registration
4. Test duplicate username/email prevention

### Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Database connection error | Check `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` in config.php |
| "Access Denied" on admin pages | Login as admin (admin@londonlabels.com) |
| Products not showing | Ensure sample data was inserted during schema creation |
| Cart is empty after refresh | Check session configuration, sessions must be enabled |
| Images show as placeholder | Image upload feature implemented in phase 2 |

---

## Next Steps & Future Enhancements

### Phase 2 Recommended Features

1. **Product Images**
   - Upload product images during product creation
   - Image gallery on product details page
   - Thumbnail generation

2. **Payment Integration**
   - Stripe/PayPal integration
   - Payment status tracking
   - Invoice generation

3. **Email Notifications**
   - Order confirmation emails
   - Shipping notifications
   - Password recovery emails
   - Contact form responses

4. **Enhanced Admin Panel**
   - Bulk product upload (CSV)
   - Sales reports and analytics
   - Dashboard charts
   - Email templates

5. **Customer Features**
   - Wishlist/favorites
   - Product ratings and reviews
   - Customer notifications
   - Account settings

6. **Advanced Search**
   - Price range filtering
   - Stock status filtering
   - Sorting options (price, newest, popularity)

7. **Inventory Management**
   - Low stock alerts
   - Automatic reorder points
   - Stock history tracking

### Code Quality Improvements

1. Add unit tests (PHPUnit)
2. Add integration tests
3. Implement logging system
4. Add input sanitization layer
5. Create API endpoints for AJAX
6. Implement caching strategy

---

## Support & Documentation

### Key Files to Review

- **Database Schema:** `schema.sql` (all table definitions)
- **Database Functions:** `db_functions.php` (all queries)
- **Configuration:** `config.php` (database, mail, site settings)
- **Authentication:** `functions.php` (auth helpers)

### Contact & Feedback

For questions about the implementation or to report issues, contact the development team.

---

**End of Implementation Guide**  
**Ready for Testing: February 26, 2026**
