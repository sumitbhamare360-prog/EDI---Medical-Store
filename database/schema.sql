-- MediStore Database Schema
-- MySQL / MariaDB, InnoDB, utf8mb4
-- Run this file first, then seed.sql

DROP DATABASE IF EXISTS medistore;
CREATE DATABASE medistore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medistore;

-- Settings (key-value store for system configuration)
CREATE TABLE settings (
    setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Roles
CREATE TABLE roles (
    role_id   TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Users
CREATE TABLE users (
    user_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    role_id    TINYINT UNSIGNED NOT NULL,
    username   VARCHAR(50) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_users_status ON users(status);

-- Categories
CREATE TABLE categories (
    category_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description   VARCHAR(255),
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE INDEX idx_categories_status ON categories(status);

-- Suppliers
CREATE TABLE suppliers (
    supplier_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL UNIQUE,
    contact_person VARCHAR(100),
    phone         VARCHAR(20),
    email         VARCHAR(100),
    address       VARCHAR(255),
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE INDEX idx_suppliers_status ON suppliers(status);

-- Medicines
CREATE TABLE medicines (
    medicine_id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_id          INT UNSIGNED NOT NULL,
    name                 VARCHAR(150) NOT NULL,
    generic_name         VARCHAR(150),
    requires_prescription TINYINT UNSIGNED NOT NULL DEFAULT 0,
    selling_price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reorder_level        INT UNSIGNED NOT NULL DEFAULT 0,
    status               ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_medicines_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_medicines_category ON medicines(category_id);
CREATE INDEX idx_medicines_status ON medicines(status);
CREATE INDEX idx_medicines_name ON medicines(name);

-- Batches (stock with expiry tracking)
CREATE TABLE batches (
    batch_id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    medicine_id      INT UNSIGNED NOT NULL,
    batch_no         VARCHAR(50) NOT NULL,
    purchase_price   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    available_qty    INT UNSIGNED NOT NULL DEFAULT 0,
    expiry_date      DATE NOT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_batches_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE RESTRICT,
    CONSTRAINT chk_batches_qty CHECK (available_qty >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_batches_medicine_expiry ON batches(medicine_id, expiry_date);
CREATE INDEX idx_batches_expiry ON batches(expiry_date);

-- Purchase Orders
CREATE TABLE purchase_orders (
    po_id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    supplier_id   INT UNSIGNED NOT NULL,
    po_no         VARCHAR(50) NOT NULL UNIQUE,
    status        ENUM('pending','received','cancelled') NOT NULL DEFAULT 'pending',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_at   TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_purchase_orders_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_purchase_orders_status ON purchase_orders(status);
CREATE INDEX idx_purchase_orders_created ON purchase_orders(created_at);

-- Purchase Order Items
CREATE TABLE purchase_items (
    item_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    po_id        INT UNSIGNED NOT NULL,
    medicine_id  INT UNSIGNED NOT NULL,
    qty_ordered  INT UNSIGNED NOT NULL,
    qty_received INT UNSIGNED NOT NULL DEFAULT 0,
    purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_purchase_items_po FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_items_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE RESTRICT,
    CONSTRAINT chk_purchase_items_received CHECK (qty_received <= qty_ordered)
) ENGINE=InnoDB;

CREATE INDEX idx_purchase_items_po ON purchase_items(po_id);

-- Customer Queue
CREATE TABLE queue_entries (
    queue_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    token_no    INT UNSIGNED NOT NULL,
    customer_name VARCHAR(100),
    status      ENUM('waiting','serving','completed','cancelled') NOT NULL DEFAULT 'waiting',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    served_at   TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

CREATE INDEX idx_queue_status_token ON queue_entries(status, token_no);
CREATE INDEX idx_queue_created ON queue_entries(created_at);

-- Prescriptions
CREATE TABLE prescriptions (
    prescription_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    patient_name    VARCHAR(100) NOT NULL,
    ref_no          VARCHAR(50) NOT NULL UNIQUE,
    file_path       VARCHAR(255),
    issued_by_cashier INT UNSIGNED,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prescriptions_cashier FOREIGN KEY (issued_by_cashier) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_prescriptions_ref ON prescriptions(ref_no);

-- Invoices
CREATE TABLE invoices (
    invoice_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_no   VARCHAR(50) NOT NULL UNIQUE,
    queue_id     INT UNSIGNED,
    cashier_id   INT UNSIGNED NOT NULL,
    prescription_id INT UNSIGNED NULL,
    customer_name VARCHAR(100),
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status       ENUM('completed','returned','cancelled') NOT NULL DEFAULT 'completed',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_queue FOREIGN KEY (queue_id) REFERENCES queue_entries(queue_id) ON DELETE RESTRICT,
    CONSTRAINT fk_invoices_cashier FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_invoices_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_invoices_created ON invoices(created_at);
CREATE INDEX idx_invoices_no ON invoices(invoice_no);
CREATE INDEX idx_invoices_status ON invoices(status);

-- Invoice Items
CREATE TABLE invoice_items (
    item_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_id   INT UNSIGNED NOT NULL,
    medicine_id  INT UNSIGNED NOT NULL,
    medicine_name VARCHAR(150) NOT NULL,
    qty          INT UNSIGNED NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    line_total   DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE RESTRICT,
    CONSTRAINT fk_invoice_items_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_invoice_items_invoice ON invoice_items(invoice_id);

-- Sale Allocations (which batch supplied which qty)
CREATE TABLE sale_allocations (
    allocation_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_id    INT UNSIGNED NOT NULL,
    invoice_item_id INT UNSIGNED NOT NULL,
    batch_id      INT UNSIGNED NOT NULL,
    qty           INT UNSIGNED NOT NULL,
    CONSTRAINT fk_sale_allocations_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE RESTRICT,
    CONSTRAINT fk_sale_allocations_item FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(item_id) ON DELETE RESTRICT,
    CONSTRAINT fk_sale_allocations_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_sale_allocations_invoice ON sale_allocations(invoice_id);
CREATE INDEX idx_sale_allocations_item ON sale_allocations(invoice_item_id);
CREATE INDEX idx_sale_allocations_batch ON sale_allocations(batch_id);

-- Returns
CREATE TABLE returns (
    return_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_id    INT UNSIGNED NOT NULL,
    return_no     VARCHAR(50) NOT NULL UNIQUE,
    reason        VARCHAR(255) NOT NULL,
    total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    restocked     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_returns_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_returns_invoice ON returns(invoice_id);

-- Return Items
CREATE TABLE return_items (
    return_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    return_id      INT UNSIGNED NOT NULL,
    invoice_item_id INT UNSIGNED NOT NULL,
    qty            INT UNSIGNED NOT NULL,
    restock_batch_id INT UNSIGNED NULL,
    CONSTRAINT fk_return_items_return FOREIGN KEY (return_id) REFERENCES returns(return_id) ON DELETE RESTRICT,
    CONSTRAINT fk_return_items_invoice_item FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(item_id) ON DELETE RESTRICT,
    CONSTRAINT fk_return_items_restock_batch FOREIGN KEY (restock_batch_id) REFERENCES batches(batch_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_return_items_return ON return_items(return_id);

CREATE TABLE return_restocks (
    restock_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    return_item_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NOT NULL,
    qty INT UNSIGNED NOT NULL,
    CONSTRAINT fk_return_restocks_item FOREIGN KEY (return_item_id) REFERENCES return_items(return_item_id) ON DELETE RESTRICT,
    CONSTRAINT fk_return_restocks_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Ledger Entries (stock + money movement log)
CREATE TABLE ledger_entries (
    ledger_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entry_type   ENUM('sale','return','receive','adjustment') NOT NULL,
    reference_id INT UNSIGNED,
    medicine_id  INT UNSIGNED,
    qty_change   INT NOT NULL,
    amount       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    note         VARCHAR(255),
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ledger_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_ledger_type ON ledger_entries(entry_type);
CREATE INDEX idx_ledger_created ON ledger_entries(created_at);

-- Audit Logs
CREATE TABLE audit_logs (
    audit_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED,
    action       VARCHAR(100) NOT NULL,
    entity_type  VARCHAR(50),
    entity_id    INT UNSIGNED,
    details      VARCHAR(500),
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_audit_user ON audit_logs(user_id);
CREATE INDEX idx_audit_created ON audit_logs(created_at);
