-- MediStore Seed Data
-- Import AFTER schema.sql

USE medistore;

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('expiry_warning_days', '60'),
('store_name', 'MediStore Pharmacy'),
('store_address', '123 Health Street, Wellness City'),
('currency_symbol', '$');

-- Roles
INSERT INTO roles (role_name) VALUES ('admin'), ('manager'), ('cashier');

-- Users (passwords: admin123, manager123, cashier123)
INSERT INTO users (role_id, username, password, full_name, status) VALUES
(1, 'admin',    '$2y$10$AVLRXkdfvZSGsXGOn4wOauDTr5bBwUINHybBB0xte2cImpuLVNvUC', 'System Admin',    'active'),
(2, 'manager',  '$2y$10$uzLUhH7vjVgMI7ZfRZd.SeSzOIJhKHv4wAQg8jXEl0M4xdLSHQrKS', 'Store Manager',   'active'),
(3, 'cashier',  '$2y$10$CyhLvLvvlo0/7ouJ577pe.JUN94pdC1e5bQE8oua76vhjuh49DmHG', 'Front Cashier',   'active');

-- Categories
INSERT INTO categories (category_name, description, status) VALUES
('Pain Relief', 'Analgesics and antipyretics', 'active'),
('Antibiotics', 'Antibacterial medicines', 'active'),
('Vitamins', 'Vitamin and mineral supplements', 'active'),
('Cold & Flu', 'Cold, cough and flu remedies', 'active'),
('Digestive', 'Antacids and digestive aids', 'active'),
('Skin Care', 'Topical dermatological products', 'active');

-- Medicines
-- name, generic_name, requires_prescription, selling_price, reorder_level
INSERT INTO medicines (category_id, name, generic_name, requires_prescription, selling_price, reorder_level, status) VALUES
(1, 'Paracetamol 500mg', 'Paracetamol', 0, 2.50, 20, 'active'),
(2, 'Amoxicillin 500mg', 'Amoxicillin', 1, 8.00, 15, 'active'),   -- prescription required
(3, 'Vitamin C 1000mg', 'Ascorbic Acid', 0, 5.00, 30, 'active'),
(4, 'Cetirizine 10mg', 'Cetirizine', 0, 3.00, 25, 'active'),
(5, 'Omeprazole 20mg', 'Omeprazole', 0, 6.50, 10, 'active'),   -- low stock demo
(6, 'Hydrocortisone Cream 1%', 'Hydrocortisone', 0, 7.00, 12, 'active'),
(1, 'Ibuprofen 400mg', 'Ibuprofen', 0, 3.50, 20, 'active'),
(2, 'Azithromycin 500mg', 'Azithromycin', 1, 12.00, 10, 'active'), -- prescription required
(3, 'Multivitamin', 'Multivitamin', 0, 9.00, 15, 'active'),
(4, 'Cough Syrup', 'Dextromethorphan', 0, 4.50, 18, 'active'),
(5, 'Loperamide 2mg', 'Loperamide', 0, 3.20, 25, 'active'),
(6, 'Aloe Vera Gel', 'Aloe Vera', 0, 6.00, 10, 'active'),        -- low stock demo
(1, 'Aspirin 325mg', 'Aspirin', 0, 2.00, 30, 'active'),
(2, 'Ciprofloxacin 500mg', 'Ciprofloxacin', 1, 10.00, 8, 'active'), -- prescription required, low stock
(3, 'Vitamin D3 2000IU', 'Cholecalciferol', 0, 11.00, 20, 'active'),
(4, 'Paracetamol Cold', 'Paracetamol + Phenylephrine', 0, 4.00, 22, 'active'),
(5, 'Simethicone', 'Simethicone', 0, 4.80, 15, 'active'),
(6, 'Clotrimazole Cream', 'Clotrimazole', 0, 6.50, 14, 'active'),
(1, 'Naproxen 250mg', 'Naproxen', 0, 4.20, 20, 'active'),
(2, 'Doxycycline 100mg', 'Doxycycline', 1, 9.50, 12, 'active'), -- prescription required
(3, 'Calcium 500mg', 'Calcium Carbonate', 0, 5.50, 25, 'active'),
(4, 'Lozenges', 'Benzocaine + Menthol', 0, 3.80, 20, 'active'),
(5, 'Domperidone 10mg', 'Domperidone', 0, 5.20, 15, 'active'),
(6, 'Sunscreen SPF 50', 'Octinoxate + Zinc Oxide', 0, 14.00, 10, 'active'), -- low stock demo
(1, 'Paracetamol 650mg', 'Paracetamol', 0, 3.00, 18, 'active'); -- FEFO demo medicine (two batches)

-- Suppliers
INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, status) VALUES
('MediSupply Inc.', 'John Davis', '+1-555-0101', 'john@medisupply.com', '100 Industrial Ave', 'active'),
('PharmaDist Co.', 'Sarah Lee', '+1-555-0102', 'sarah@pharmadist.com', '200 Trade Blvd', 'active'),
('HealthDirect Ltd.', 'Mike Ross', '+1-555-0103', 'mike@healthdirect.com', '300 Supply Rd', 'active');

-- Batches (designed to prove business rules)
-- Paracetamol 500mg (medicine_id=1): two valid batches different expiry -> FEFO demo
INSERT INTO batches (medicine_id, batch_no, purchase_price, available_qty, expiry_date) VALUES
(1, 'BATCH-PCT-001', 1.50, 50, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'active'),  -- expires later
(1, 'BATCH-PCT-002', 1.40, 30, DATE_ADD(CURDATE(), INTERVAL 2 MONTH), 'active'),  -- expires earlier -> FEFO picks this first
-- Amoxicillin 500mg (medicine_id=2): one expired batch
(2, 'BATCH-AMX-001', 4.00, 20, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'expired'),   -- expired
-- Vitamin C 1000mg (medicine_id=3): expiring within 30 days
(3, 'BATCH-VIT-001', 2.50, 40, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'active'),    -- near expiry
-- Omeprazole 20mg (medicine_id=5): low stock at/below reorder level (10)
(5, 'BATCH-OME-001', 3.00, 8, DATE_ADD(CURDATE(), INTERVAL 8 MONTH), 'active'),    -- low stock (8 <= 10)
-- Hydrocortisone Cream (medicine_id=6): low stock (reorder 12)
(6, 'BATCH-HYD-001', 3.50, 10, DATE_ADD(CURDATE(), INTERVAL 7 MONTH), 'active'),   -- low stock (10 <= 12)
-- Ibuprofen 400mg (medicine_id=7): healthy stock
(7, 'BATCH-IBU-001', 1.80, 60, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active'),
-- Azithromycin 500mg (medicine_id=8): prescription, healthy
(8, 'BATCH-AZI-001', 6.00, 25, DATE_ADD(CURDATE(), INTERVAL 9 MONTH), 'active'),
-- Multivitamin (medicine_id=9)
(9, 'BATCH-MUL-001', 4.50, 45, DATE_ADD(CURDATE(), INTERVAL 10 MONTH), 'active'),
-- Cough Syrup (medicine_id=10)
(10, 'BATCH-COU-001', 2.20, 55, DATE_ADD(CURDATE(), INTERVAL 11 MONTH), 'active'),
-- Loperamide 2mg (medicine_id=11)
(11, 'BATCH-LOP-001', 1.60, 70, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active'),
-- Aloe Vera Gel (medicine_id=12): low stock (reorder 10)
(12, 'BATCH-ALO-001', 3.00, 9, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'active'),    -- low stock (9 <= 10)
-- Aspirin 325mg (medicine_id=13)
(13, 'BATCH-ASP-001', 1.00, 80, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active'),
-- Ciprofloxacin 500mg (medicine_id=14): prescription, low stock (reorder 8)
(14, 'BATCH-CIP-001', 5.00, 6, DATE_ADD(CURDATE(), INTERVAL 8 MONTH), 'active'),    -- low stock (6 <= 8)
-- Vitamin D3 2000IU (medicine_id=15)
(15, 'BATCH-VID-001', 5.50, 50, DATE_ADD(CURDATE(), INTERVAL 9 MONTH), 'active'),
-- Paracetamol Cold (medicine_id=16)
(16, 'BATCH-COL-001', 2.00, 65, DATE_ADD(CURDATE(), INTERVAL 10 MONTH), 'active'),
-- Simethicone (medicine_id=17)
(17, 'BATCH-SIM-001', 2.40, 40, DATE_ADD(CURDATE(), INTERVAL 9 MONTH), 'active'),
-- Clotrimazole Cream (medicine_id=18)
(18, 'BATCH-CLO-001', 3.20, 45, DATE_ADD(CURDATE(), INTERVAL 8 MONTH), 'active'),
-- Naproxen 250mg (medicine_id=19)
(19, 'BATCH-NAP-001', 2.10, 55, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active'),
-- Doxycycline 100mg (medicine_id=20): prescription
(20, 'BATCH-DOX-001', 4.80, 30, DATE_ADD(CURDATE(), INTERVAL 9 MONTH), 'active'),
-- Calcium 500mg (medicine_id=21)
(21, 'BATCH-CAL-001', 2.80, 60, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active'),
-- Lozenges (medicine_id=22)
(22, 'BATCH-LOZ-001', 1.90, 50, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active'),
-- Domperidone 10mg (medicine_id=23)
(23, 'BATCH-DOM-001', 2.60, 40, DATE_ADD(CURDATE(), INTERVAL 10 MONTH), 'active'),
-- Sunscreen SPF 50 (medicine_id=24): low stock (reorder 10)
(24, 'BATCH-SUN-001', 7.00, 8, DATE_ADD(CURDATE(), INTERVAL 7 MONTH), 'active'),    -- low stock (8 <= 10)
-- Paracetamol 650mg (medicine_id=25): FEFO demo medicine (two batches)
(25, 'BATCH-PCT650-001', 1.80, 25, DATE_ADD(CURDATE(), INTERVAL 5 MONTH), 'active'),
(25, 'BATCH-PCT650-002', 1.70, 20, DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'active'); -- expires earlier

-- Purchase Orders
INSERT INTO purchase_orders (supplier_id, po_no, status, created_at) VALUES
(1, 'PO-2026-0001', 'received', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(2, 'PO-2026-0002', 'pending', CURDATE());

-- Purchase Items
INSERT INTO purchase_items (po_id, medicine_id, qty_ordered, qty_received, purchase_price) VALUES
(1, 1, 50, 50, 1.50),
(1, 3, 40, 40, 2.50),
(1, 7, 60, 60, 1.80),
(2, 2, 30, 0, 4.00),   -- pending, not received yet
(2, 8, 25, 0, 6.00);   -- pending, not received yet

-- Sample Invoices
INSERT INTO invoices (invoice_no, queue_id, cashier_id, customer_name, total_amount, status, created_at) VALUES
('INV-20260101-0001', NULL, 3, 'Walk-in Customer', 10.50, 'completed', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
('INV-20260101-0002', NULL, 3, 'Walk-in Customer', 24.00, 'completed', DATE_SUB(CURDATE(), INTERVAL 1 DAY));

-- Invoice Items
INSERT INTO invoice_items (invoice_id, medicine_id, medicine_name, qty, unit_price, line_total) VALUES
(1, 1, 'Paracetamol 500mg', 4, 2.50, 10.00),
(1, 4, 'Cetirizine 10mg', 1, 0.50, 0.50),
(2, 8, 'Azithromycin 500mg', 2, 12.00, 24.00);

-- Sale Allocations (matching the sample invoices)
INSERT INTO sale_allocations (invoice_id, invoice_item_id, batch_id, qty) VALUES
(1, 1, 1, 4),
(1, 2, 1, 1),
(2, 3, 8, 2);   -- Azithromycin from BATCH-AZI-001

-- Ledger Entries for sample sales
INSERT INTO ledger_entries (entry_type, reference_id, medicine_id, qty_change, amount, note, created_at) VALUES
('sale', 1, 1, -4, 10.00, 'Sale INV-20260101-0001', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
('sale', 1, 4, -1, 0.50, 'Sale INV-20260101-0001', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
('sale', 2, 8, -2, 24.00, 'Sale INV-20260101-0002', DATE_SUB(CURDATE(), INTERVAL 1 DAY));

-- Queue Entries (sample)
INSERT INTO queue_entries (token_no, customer_name, status, created_at) VALUES
(1, 'Alice Smith', 'completed', DATE_SUB(CURDATE(), INTERVAL 1 HOUR)),
(2, 'Bob Jones', 'waiting', CURDATE()),
(3, 'Carol White', 'waiting', CURDATE());
