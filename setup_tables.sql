-- Bikyensub Missing DB Tables Setup
-- Run this once on the bikyensub MySQL database (eduowrav_bikyensub)
-- Run via cPanel phpMyAdmin or MySQL CLI

-- ── Device tokens for FCM push notifications ─────────────────────────────────
CREATE TABLE IF NOT EXISTS device_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    email      VARCHAR(255) NOT NULL,
    fcm_token  TEXT NOT NULL,
    platform   ENUM('android','ios') DEFAULT 'android',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── In-app notifications ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications_tbl (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    message      TEXT NOT NULL,
    type         ENUM('info','success','warning','danger') DEFAULT 'info',
    target       ENUM('all','specific') DEFAULT 'all',
    target_email VARCHAR(255) NULL,
    created_by   VARCHAR(255) NULL,
    is_read_by   LONGTEXT NULL DEFAULT '[]',
    status       TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Referral tables ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS referal_tbl (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    referal     VARCHAR(255) NOT NULL,
    referee     VARCHAR(255) NOT NULL,
    date_refer  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_referal (referal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS referal_earn_transaction_tbl (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    referal_email VARCHAR(255) NOT NULL,
    buyer_email   VARCHAR(255) NOT NULL,
    earn_amount   DECIMAL(10,2) DEFAULT 0,
    date_trans    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status        TINYINT(1) DEFAULT 0,
    INDEX idx_referal_email (referal_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Add missing columns to users_tbl (run individually, ignore errors if column exists) ──
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS nin VARCHAR(11) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS bvn VARCHAR(11) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS finger TINYINT(1) DEFAULT 0;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS referal_token VARCHAR(100) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS token TEXT NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS acc_no VARCHAR(20) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS acc_name VARCHAR(100) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS acc_no2 VARCHAR(20) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS bank_name2 VARCHAR(100) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS acc_name2 VARCHAR(100) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS state VARCHAR(50) NULL;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS admin_role TINYINT(1) DEFAULT 0;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS super_admin TINYINT(1) DEFAULT 0;
ALTER TABLE users_tbl ADD COLUMN IF NOT EXISTS date_join TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- ── Backfill referral tokens for existing users that don't have one ────────────
UPDATE users_tbl SET referal_token = MD5(email) WHERE referal_token IS NULL OR referal_token = '';
