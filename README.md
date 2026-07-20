# London Labels 🏷️

> **Style Without Borders** — A full-stack boutique e-commerce platform built with PHP, MySQL, and vanilla JavaScript, deployed on Railway.

London Labels is a production-ready online fashion store for a Lagos-based retail brand. Built entirely from scratch without frameworks, it demonstrates full-stack capability from database design to live cloud deployment.

---

## 🌐 Live Demo

**[View Live Site →](https://londonlabels.up.railway.app/)**

---

## 📸 Screenshots

> <img width="958" height="410" alt="Screenshot 2026-07-20 160900" src="https://github.com/user-attachments/assets/2c7aec5c-339c-4a8c-b738-0774229c1312" />
<img width="959" height="412" alt="Screenshot 2026-07-20 160938" src="https://github.com/user-attachments/assets/6e0b64fc-4731-468c-aa95-ed93c8792c14" />


---

## ✨ Features

### Storefront
- **Product catalogue** with category filtering, search suggestions, and pagination
- **Product detail pages** with multi-image gallery, thumbnail strip, and fullscreen lightbox
- **Shopping cart** with real-time quantity updates (no page reload)
- **Wishlist** — persistent for logged-in users, session-based for guests
- **Checkout flow** with Paystack payment integration and webhook handling
- **Order confirmation** emails via PHPMailer (SMTP)

### Authentication & Security
- Full **user registration & login** with email verification
- **Google OAuth** sign-in via OAuth 2.0
- **Two-factor authentication** with trusted device management
- **CSRF protection** on all forms
- **Password reset** via secure tokenised email links
- Role-based access control (`customer`, `admin`, `super_admin`)

### Admin Dashboard
- **Revenue, Orders, Products, and Customers** KPI stat cards
- **Recent orders table** with responsive mobile card layout
- **Low stock warnings** with direct links to edit pages
- **Homepage curation** — manage featured products and new arrivals
- Full **order management** (view, edit status, fulfil)
- Full **product management** (add, edit, upload images, delete)
- **Category management** with cover image uploads
- **User management** with role editing and account controls
- **Customer messages** inbox with read/unread tracking
- **Product reviews** moderation panel
- Super admin protection — primary owner account cannot be demoted or deleted

---

## 🛠️ Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | PHP 8.x (no framework) |
| **Database** | MySQL via PDO |
| **Frontend** | Vanilla HTML, CSS, JavaScript |
| **Payments** | Paystack (NGN) |
| **Email** | PHPMailer + SMTP |
| **Authentication** | Google OAuth 2.0 |
| **Deployment** | Railway (Docker + Apache) |
| **Local Dev** | XAMPP |

---

## 🗂️ Project Structure

```
LondonLabels/
├── admin/              # Admin-only pages (dashboard, orders, products, users...)
├── account/            # Customer account pages (wishlist, profile...)
├── assets/             # CSS, JS, images, fonts
├── auth/               # OAuth handlers (Google)
├── includes/           # Reusable PHP components (product card, empty state...)
├── legal/              # Privacy policy, terms pages
├── tools/              # CLI utility scripts (orphan cleanup, media audit)
├── Uploads/            # User-uploaded product/category images (gitignored)
├── db_functions.php    # All database query functions
├── functions.php       # Core utility, security, and media helpers
├── mailer.php          # Email templates and sending logic
├── config.php          # App configuration (gitignored — uses env vars)
├── Dockerfile          # Docker config for Railway deployment
└── railway.toml        # Railway deployment config
```

---

## 🚀 Local Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.x)
- A MySQL database called `londonlabels`

### Steps

1. **Clone the repo**
   ```bash
   git clone https://github.com/InyeneUsoroh/London-Labels.git
   cd London-Labels
   ```

2. **Create `config.php`** from the template below and place it in the project root:
   ```php
   <?php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'londonlabels');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   // Add remaining constants as needed
   ```

3. **Import the database schema** via phpMyAdmin or MySQL CLI.

4. **Visit** `http://localhost/LondonLabels` in your browser.

---

## ⚙️ Environment Variables (Production)

All secrets are injected at runtime via environment variables on Railway — never hardcoded:

| Variable | Description |
| :--- | :--- |
| `MYSQLHOST` | Database host |
| `MYSQLDATABASE` | Database name |
| `MYSQLUSER` | Database user |
| `MYSQLPASSWORD` | Database password |
| `PAYSTACK_SECRET_KEY` | Paystack secret key |
| `PAYSTACK_PUBLIC_KEY` | Paystack public key |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret |
| `MAIL_HOST` | SMTP host |
| `MAIL_USERNAME` | SMTP username |
| `MAIL_PASSWORD` | SMTP password |
| `BASE_URL` | App base URL |

---

## 👤 Author

**Inyene Usoroh** — Full-Stack Developer  
[GitHub](https://github.com/InyeneUsoroh)

---

## 🎓 Academic Context

This project was developed as a **third-level academic placement project substitute**. Unable to secure an industry placement, the requirement was fulfilled by independently designing, building, and deploying a fully functional web application from scratch.

The project demonstrates the practical application of full-stack web development skills acquired throughout the programme — covering database design, server-side programming, client-side interactivity, authentication, payment integration, and cloud deployment — all without the use of application frameworks.

---

## 📄 License

This project is open for viewing and portfolio purposes. Please do not redistribute or repurpose without permission.

