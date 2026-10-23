# MediStore

MediStore is a PHP and MySQL application for medical store inventory, billing, prescriptions, returns, and reports.

**Stack:** PHP 8, PDO, MySQL/MariaDB, HTML5, CSS3, vanilla JavaScript. Runs on XAMPP.

## Setup

1. Copy this folder to `C:\xampp\htdocs\medistore`.
2. Start Apache and MySQL in XAMPP.
3. Import `database/schema.sql` in phpMyAdmin.
4. Import `database/seed.sql`.
5. Open http://localhost/medistore.

Demo logins: `admin / admin123`, `manager / manager123`, `cashier / cashier123`.

Run CLI checks with `php tests/run_tests.php` after preparing the separate test database.

## Features (SRS)

- Authentication and role access: admin, manager, cashier.
- Medicine, category, supplier, purchase, and inventory management.
- Daily customer queue, POS billing, FEFO batch selection, and prescriptions.
- Returns, stock ledger, expiry/low-stock monitoring, and sales reporting.
- Administration, audit trail, and printable report pages.

## Folder structure

`admin/`, `assets/`, `billing/`, `categories/`, `config/`, `database/`, `includes/`, `inventory/`, `medicines/`, `prescriptions/`, `purchases/`, `reports/`, `returns/`, `suppliers/`, `tests/`.
