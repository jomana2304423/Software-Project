-- Pharmacy Management System Database Schema
-- Import this into phpMyAdmin to create the database and tables

-- Create database
CREATE DATABASE IF NOT EXISTS pms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pms_db;

-- Users and roles
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150),
  role_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Suppliers
CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_name VARCHAR(150),
  phone VARCHAR(50),
  email VARCHAR(150),
  address VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Medicines
CREATE TABLE medicines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  category VARCHAR(100),
  description TEXT,
  reorder_level INT NOT NULL DEFAULT 20,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Batches (batch number, expiry, price, qty)
CREATE TABLE medicine_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medicine_id INT NOT NULL,
  batch_number VARCHAR(100) NOT NULL,
  expiry_date DATE NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_batch (medicine_id, batch_number),
  FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- Customers (optional but useful for invoices)
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(50),
  email VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sales (invoice header)
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(50) NOT NULL UNIQUE,
  customer_id INT NULL,
  pharmacist_id INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (pharmacist_id) REFERENCES users(id)
);

-- Sales items (lines)
CREATE TABLE sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  medicine_batch_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (medicine_batch_id) REFERENCES medicine_batches(id)
);

-- Purchase Orders to suppliers
CREATE TABLE purchase_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  po_number VARCHAR(50) NOT NULL UNIQUE,
  supplier_id INT NOT NULL,
  status ENUM('Pending','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE purchase_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_order_id INT NOT NULL,
  medicine_id INT NOT NULL,
  requested_qty INT NOT NULL,
  FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
  FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- Prescriptions upload
CREATE TABLE prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- Notifications (low stock, expiry, system)
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('LowStock','Expiry','System') NOT NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity and error logs
CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(150) NOT NULL,
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE error_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  context VARCHAR(150),
  message TEXT NOT NULL,
  stack TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed roles and an admin user
INSERT INTO roles (name) VALUES ('Admin'), ('Pharmacist'), ('Supplier'), ('Customer');

-- Example password: admin123 (use PHP password_hash in app; temp hash for import)
INSERT INTO users (username, password_hash, full_name, email, role_id, is_active)
VALUES ('admin', '$2y$10$bcE3kqf9m2WqQm3b9c7U.eS5fGGCm5k1mQqA4bN6iDY/7mFK8a7Si', 'System Admin', 'admin@example.com', 1, 1);

-- Add sample data for testing
INSERT INTO suppliers (name, contact_name, phone, email, address) VALUES
('MedSupply Co.', 'John Smith', '9876543210', 'john@medsupply.com', '123 Medical St, City'),
('PharmaDist Ltd.', 'Sarah Johnson', '9876543211', 'sarah@pharmadist.com', '456 Health Ave, City');

INSERT INTO medicines (name, category, description, reorder_level) VALUES
('Paracetamol 500mg', 'Pain Relief', 'Pain and fever relief medication', 50),
('Amoxicillin 250mg', 'Antibiotic', 'Broad spectrum antibiotic', 30),
('Metformin 500mg', 'Diabetes', 'Type 2 diabetes medication', 40),
('Omeprazole 20mg', 'Gastric', 'Acid reflux medication', 25);

INSERT INTO medicine_batches (medicine_id, batch_number, expiry_date, unit_price, quantity) VALUES
(1, 'PAR001', '2025-12-31', 5.00, 100),
(1, 'PAR002', '2026-01-15', 5.50, 80),
(2, 'AMX001', '2025-11-30', 12.00, 60),
(2, 'AMX002', '2026-02-28', 12.50, 45),
(3, 'MET001', '2025-10-31', 8.00, 70),
(3, 'MET002', '2026-03-15', 8.50, 55),
(4, 'OME001', '2025-09-30', 15.00, 40),
(4, 'OME002', '2026-01-31', 15.50, 35);

INSERT INTO customers (name, phone, email) VALUES
('John Doe', '9876543210', 'john@example.com'),
('Jane Smith', '9876543211', 'jane@example.com'),
('Mike Johnson', '9876543212', 'mike@example.com');

-- Add some sample notifications
INSERT INTO notifications (type, title, message) VALUES
('LowStock', 'Low Stock Alert', 'Paracetamol 500mg is running low (20 units remaining)'),
('Expiry', 'Expiry Alert', 'Amoxicillin 250mg batch AMX001 expires in 15 days'),
('System', 'System Update', 'Database backup completed successfully');
