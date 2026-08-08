-- Secure Payment Gateway - Database Schema
-- Run: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS secure_payment_gateway
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE secure_payment_gateway;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency VARCHAR(3) NOT NULL DEFAULT 'INR',
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_user_wallet (user_id)
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('payment','refund') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  reference_id VARCHAR(64) NOT NULL UNIQUE,
  related_transaction_id INT NULL,
  description VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (related_transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
);

CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  details TEXT,
  ip_address VARCHAR(45),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Seed one admin (password: Admin@12345 -- CHANGE after first login)
-- Hash generated with password_hash('Admin@12345', PASSWORD_BCRYPT)
INSERT INTO users (full_name, email, password_hash, role)
VALUES ('Admin', 'admin@example.com', '$2y$10$examplehashreplaceatsetup000000000000000000000000000', 'admin');
