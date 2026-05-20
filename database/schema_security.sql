-- ============================================================
-- Plant Sphere - Security Schema Extensions
-- Run this AFTER the base schema.sql
-- ============================================================

USE plantsphere_secure_authentication_system_db;

-- 1. Add security columns to users table
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS is_locked       TINYINT(1)   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS failed_attempts TINYINT(3)   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS locked_until    DATETIME     NULL,
    ADD COLUMN IF NOT EXISTS last_login      DATETIME     NULL,
    ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS data_classification ENUM('public','internal','confidential') NOT NULL DEFAULT 'internal';

-- 2. Add admin role to users ENUM
ALTER TABLE users
    MODIFY COLUMN role ENUM(
        'admin',
        'community_organizer',
        'community_affairs_worker',
        'agricultural_technologist',
        'mao'
    ) NOT NULL DEFAULT 'community_organizer';

-- 3. Login attempt logs
CREATE TABLE IF NOT EXISTS login_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NULL,
    email       VARCHAR(100) NOT NULL,
    ip_address  VARCHAR(45)  NOT NULL,
    user_agent  VARCHAR(255) NOT NULL,
    status      ENUM('success','failed','locked') NOT NULL,
    reason      VARCHAR(100) NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 4. Admin / general activity logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    action      VARCHAR(100) NOT NULL,
    module      VARCHAR(60)  NOT NULL,
    description TEXT         NOT NULL,
    ip_address  VARCHAR(45)  NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Session tracking (for timeout enforcement)
CREATE TABLE IF NOT EXISTS user_sessions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address   VARCHAR(45)  NOT NULL,
    user_agent   VARCHAR(255) NOT NULL,
    last_activity DATETIME    NOT NULL,
    expires_at   DATETIME     NOT NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Data export / download restriction log
CREATE TABLE IF NOT EXISTS export_attempts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    action     VARCHAR(100) NOT NULL,
    blocked    TINYINT(1)   NOT NULL DEFAULT 0,
    ip_address VARCHAR(45)  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Default admin account (password: Admin@1234)
-- Hash generated via: password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12])
INSERT IGNORE INTO users (firstname, lastname, email, password, role, data_classification)
VALUES (
    'System', 'Administrator',
    'admin@plantsphere.local',
    '$2y$12$VNQwsRla3359rybPt/JImeQK/y/8dzgGjHPYOuhCGwvyp7EgdY76.',
    'admin',
    'confidential'
);
