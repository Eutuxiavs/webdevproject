-- ============================================================
-- YONZON CLAIM — Database Schema
-- Paste this whole file into phpMyAdmin (SQL tab) and run it,
-- or: mysql -u root -p < database.sql
--
-- If you already created "yonzon_claim" or an older "webdevproject"
-- database before, drop it first so this creates a clean one:
--   DROP DATABASE IF EXISTS yonzon_claim;
--   DROP DATABASE IF EXISTS webdevproject;
-- then run this whole file again.
-- ============================================================

CREATE DATABASE IF NOT EXISTS webdevproject
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE webdevproject;

-- ---------- Users ----------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar_path VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  business VARCHAR(150) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Items / Claims ----------
CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  claim_id VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(80) NOT NULL,
  serial_number VARCHAR(100) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  photo_path VARCHAR(255) DEFAULT NULL,
  status ENUM('owned','warranty','lost','for_sale','sold') NOT NULL DEFAULT 'owned',
  price DECIMAL(10,2) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Conversations (one per buyer+listing pair) ----------
CREATE TABLE IF NOT EXISTS conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_conversation (item_id, buyer_id),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Messages ----------
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- No seed data on purpose — register a real account through the app
-- so the password hash is generated correctly by PHP's password_hash().
