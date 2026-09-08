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
  failed_login_count INT NOT NULL DEFAULT 0,
  locked_until TIMESTAMP NULL DEFAULT NULL,
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
  status ENUM('owned','warranty','lost','for_sale','reserved','sold') NOT NULL DEFAULT 'owned',
  price DECIMAL(10,2) DEFAULT NULL,
  condition_status ENUM('new','like_new','good','fair') DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Offers (cash, trade, or trade + cash — with mutual confirmation) ----------
CREATE TABLE IF NOT EXISTS offers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  offer_type ENUM('cash','trade','trade_cash') NOT NULL DEFAULT 'cash',
  offer_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  trade_item_id INT DEFAULT NULL,
  message VARCHAR(500) DEFAULT NULL,
  status ENUM('pending','accepted','declined','cancelled','completed','expired','disputed') NOT NULL DEFAULT 'pending',
  buyer_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  seller_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  meetup_note VARCHAR(300) DEFAULT NULL,
  expires_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (trade_item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Ownership history (who owned an item before, and when it moved) ----------
CREATE TABLE IF NOT EXISTS ownership_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  previous_owner_id INT NOT NULL,
  new_owner_id INT NOT NULL,
  offer_id INT DEFAULT NULL,
  transferred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (previous_owner_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (new_owner_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Reviews (left after a completed deal, one per person per offer) ----------
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  offer_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  reviewee_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review (offer_id, reviewer_id),
  FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
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
  read_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Password resets ----------
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Reports (flagging a user or item) ----------
CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT NOT NULL,
  reported_user_id INT DEFAULT NULL,
  reported_item_id INT DEFAULT NULL,
  reason VARCHAR(500) NOT NULL,
  status ENUM('open','reviewed','dismissed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reported_item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Blocks (mutual messaging/offers prevention) ----------
CREATE TABLE IF NOT EXISTS blocks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  blocker_id INT NOT NULL,
  blocked_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_block (blocker_id, blocked_id),
  FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- No seed data on purpose — register a real account through the app
-- so the password hash is generated correctly by PHP's password_hash().