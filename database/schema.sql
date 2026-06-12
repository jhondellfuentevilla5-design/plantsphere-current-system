CREATE DATABASE IF NOT EXISTS plantsphere_secure_authentication_system_db;
USE plantsphere_secure_authentication_system_db;

-- Users table with role support
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('community_organizer','community_affairs_worker','agricultural_technologist','mao') NOT NULL DEFAULT 'community_organizer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- RSBSA Registry
CREATE TABLE IF NOT EXISTS rsbsa_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rsbsa_number VARCHAR(50) UNIQUE NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    municipality VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    farm_size DECIMAL(10,2) NOT NULL,
    crop_type VARCHAR(100) NOT NULL,
    registration_date DATE NOT NULL,
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Planting Materials Inventory
CREATE TABLE IF NOT EXISTS planting_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_name VARCHAR(100) NOT NULL,
    material_type VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(30) NOT NULL DEFAULT 'packs',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Service Requests (tree planting activity requests)
CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(30) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    activity_name VARCHAR(150) NOT NULL,
    target_location VARCHAR(200) NOT NULL,
    target_date DATE NOT NULL,
    number_of_participants INT NOT NULL,
    seedling_type VARCHAR(100) NOT NULL,
    quantity_requested INT NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending','barangay_approved','formal_request_submitted','under_review','for_validation','validated','slip_prepared','slip_validated','approved','finalized','rejected','routed','released') DEFAULT 'pending',
    referred_by INT NULL,
    remarks TEXT NULL,
    request_letter VARCHAR(255) NULL,
    proponent_name VARCHAR(150) NULL,
    association_name VARCHAR(150) NULL,
    recipient_name VARCHAR(150) NULL,
    recipient_position VARCHAR(150) NULL,
    activity_time VARCHAR(20) NULL,
    quantity_released INT NULL,
    released_at TIMESTAMP NULL,
    released_by INT NULL,
    stock_verified TINYINT(1) DEFAULT 0,
    verified_material VARCHAR(100) NULL,
    verified_quantity INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (referred_by) REFERENCES users(id)
);

-- Site Validation Reports
CREATE TABLE IF NOT EXISTS validation_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    technologist_id INT NOT NULL,
    site_location VARCHAR(200) NOT NULL,
    site_area DECIMAL(10,2) NOT NULL,
    validation_date DATE NOT NULL,
    schedule_date DATE NOT NULL,
    soil_condition VARCHAR(100) NOT NULL,
    accessibility VARCHAR(100) NOT NULL,
    recommended_species TEXT NOT NULL,
    seed_packs_counted INT NOT NULL,
    available_seedlings INT NOT NULL,
    findings TEXT NOT NULL,
    recommendation TEXT NOT NULL,
    site_photos TEXT NULL COMMENT 'JSON array of uploaded photo paths',
    site_lat DECIMAL(10,7) NULL,
    site_lng DECIMAL(10,7) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (technologist_id) REFERENCES users(id)
);

-- Request Slips
CREATE TABLE IF NOT EXISTS request_slips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slip_number VARCHAR(30) UNIQUE NOT NULL,
    request_id INT NOT NULL,
    validation_id INT NOT NULL,
    prepared_by INT NOT NULL,
    materials_requested TEXT NOT NULL,
    quantity_approved INT NOT NULL,
    status ENUM('prepared','reviewed','approved','rejected') DEFAULT 'prepared',
    mao_remarks TEXT NULL,
    endorsement_office VARCHAR(150) NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    finalized_status ENUM('pending','finalized','rejected') DEFAULT 'pending',
    dept_head_id INT NULL,
    dept_head_remarks TEXT NULL,
    finalized_at TIMESTAMP NULL,
    endorsement_ref_number VARCHAR(100) NULL,
    filing_date DATE NULL,
    technologist_validated TINYINT(1) DEFAULT 0,
    technologist_validated_at TIMESTAMP NULL,
    technologist_validated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (validation_id) REFERENCES validation_reports(id),
    FOREIGN KEY (prepared_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Intervention Inventory — Planting Materials Only (City Agriculturist's Office — Toril District)
INSERT IGNORE INTO planting_materials (material_name, material_type, quantity, unit, description) VALUES
('Banana Lakatan', 'Fruit Tree',  0, 'seedlings', 'Lakatan banana planting materials'),
('Avocado',        'Fruit Tree',  0, 'seedlings', 'Avocado fruit tree seedlings'),
('Marang',         'Fruit Tree',  0, 'seedlings', 'Marang fruit tree seedlings'),
('Durian',         'Fruit Tree',  0, 'seedlings', 'Durian fruit tree seedlings'),
('Lanzones',       'Fruit Tree',  0, 'seedlings', 'Lanzones fruit tree seedlings'),
('Rambutan',       'Fruit Tree',  0, 'seedlings', 'Rambutan fruit tree seedlings'),
('Jackfruit',      'Fruit Tree',  0, 'seedlings', 'Jackfruit tree seedlings'),
('Corn',           'Crop',        0, 'packs',     'Corn seeds for planting'),
('Coffee',         'Cash Crop',   0, 'seedlings', 'Coffee plant seedlings'),
('Cacao',          'Cash Crop',   0, 'seedlings', 'Cacao plant seedlings'),
('Vegetable Seeds','Crop',        0, 'packs',     'Assorted vegetable seeds');

-- ============================================================
-- DEMO ACCOUNTS — Pre-registered Organizers with RSBSA
-- Password for all accounts: Password@123
-- ============================================================

-- Users (organizers)
INSERT IGNORE INTO users (firstname, lastname, email, password, role, is_active, created_at) VALUES
('Maria',   'Santos',    'maria.santos@demo.com',    '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Jose',    'Reyes',     'jose.reyes@demo.com',      '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Ana',     'Cruz',      'ana.cruz@demo.com',        '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Pedro',   'Lim',       'pedro.lim@demo.com',       '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Rosa',    'Garcia',    'rosa.garcia@demo.com',     '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW());

-- RSBSA Records (verified) for each demo organizer
-- Note: user_id will depend on auto-increment; use subquery to link by email
INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000001', 'Toril',        'Davao City', 'Davao del Sur', 1.50, 'Fruit Trees',   '2024-01-15', 'verified' FROM users WHERE email = 'maria.santos@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000002', 'Catalunan',    'Davao City', 'Davao del Sur', 2.00, 'Corn',          '2024-02-10', 'verified' FROM users WHERE email = 'jose.reyes@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000003', 'Matina',       'Davao City', 'Davao del Sur', 0.75, 'Vegetables',    '2024-03-05', 'verified' FROM users WHERE email = 'ana.cruz@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000004', 'Buhangin',     'Davao City', 'Davao del Sur', 3.25, 'Agroforestry',  '2024-04-20', 'verified' FROM users WHERE email = 'pedro.lim@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000005', 'Calinan',      'Davao City', 'Davao del Sur', 1.80, 'Mixed Farming', '2024-05-12', 'verified' FROM users WHERE email = 'rosa.garcia@demo.com';

-- UPDATE QUANTITIES for planting materials (run in phpMyAdmin)
UPDATE planting_materials SET quantity = 500 WHERE material_name IN ('Banana Lakatan','Avocado','Marang','Durian','Lanzones','Rambutan','Jackfruit','Corn','Coffee','Cacao','Vegetable Seeds');
DELETE FROM planting_materials WHERE material_name IN ('Tilapia Fingerlings','Hito Fingerling','Trichogramma','Fertilizer');

-- Formal Requests (Process 3 — generated after Barangay Captain approval)
CREATE TABLE IF NOT EXISTS formal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    proponent_name VARCHAR(150) NOT NULL,
    association_name VARCHAR(150) NULL,
    recipient_name VARCHAR(150) NOT NULL,
    recipient_position VARCHAR(150) NOT NULL,
    seedling_variety VARCHAR(100) NOT NULL,
    quantity_requested INT NOT NULL,
    activity_date DATE NOT NULL,
    activity_time VARCHAR(20) NULL,
    activity_location VARCHAR(200) NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('submitted','received') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ALTER for existing databases
ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending','barangay_approved','formal_request_submitted','under_review','for_validation','validated','slip_prepared','slip_validated','approved','finalized','rejected','routed','released') DEFAULT 'pending';

-- ─────────────────────────────────────────────────────────────
-- NEW TABLES (added for full process coverage)
-- ─────────────────────────────────────────────────────────────

-- Barangay Approvals
CREATE TABLE IF NOT EXISTS barangay_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    captain_id INT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    remarks TEXT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (captain_id) REFERENCES users(id)
);

-- Seed Releases
CREATE TABLE IF NOT EXISTS seed_releases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    slip_id INT NOT NULL,
    released_by INT NOT NULL,
    quantity_released INT NOT NULL,
    release_date DATE NOT NULL,
    recipient_name VARCHAR(150) NOT NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (slip_id) REFERENCES request_slips(id),
    FOREIGN KEY (released_by) REFERENCES users(id)
);

-- Guidance Logs
CREATE TABLE IF NOT EXISTS guidance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    release_id INT NOT NULL,
    logged_by INT NOT NULL,
    delivery_date DATE NOT NULL,
    attendance_count INT NOT NULL DEFAULT 0,
    completion_status ENUM('completed','partial','not_delivered') DEFAULT 'completed',
    guidance_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (release_id) REFERENCES seed_releases(id),
    FOREIGN KEY (logged_by) REFERENCES users(id)
);

-- Survival Analytics (aggregate)
CREATE TABLE IF NOT EXISTS survival_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    monitored_by INT NOT NULL,
    monitoring_date DATE NOT NULL,
    seedlings_planted INT NOT NULL,
    seedlings_survived INT NOT NULL,
    observations TEXT NULL,
    service_rating TINYINT NULL,
    next_monitoring DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (monitored_by) REFERENCES users(id)
);

-- Survival Participants (per-participant tracking for P16)
CREATE TABLE IF NOT EXISTS survival_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survival_id INT NOT NULL,
    participant_name VARCHAR(150) NOT NULL,
    completion_status ENUM('completed','partial','not_completed') DEFAULT 'completed',
    signature_data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survival_id) REFERENCES survival_analytics(id)
);

-- Task Assignment Log (P15 — stakeholder importance)
CREATE TABLE IF NOT EXISTS task_assignment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guidance_id INT NOT NULL,
    request_id INT NOT NULL,
    assessed_by INT NOT NULL,
    stakeholder_name VARCHAR(150) NOT NULL,
    importance_score INT NOT NULL DEFAULT 0 COMMENT '1-10 score',
    role_description TEXT NULL,
    assigned_task TEXT NULL,
    assessment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guidance_id) REFERENCES guidance_logs(id),
    FOREIGN KEY (request_id) REFERENCES service_requests(id),
    FOREIGN KEY (assessed_by) REFERENCES users(id)
);

-- ─────────────────────────────────────────────────────────────
-- ALTER STATEMENTS for existing databases (sync schema)
-- ─────────────────────────────────────────────────────────────

ALTER TABLE request_slips
    ADD COLUMN IF NOT EXISTS finalized_status ENUM('pending','finalized','rejected') DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS dept_head_id INT NULL,
    ADD COLUMN IF NOT EXISTS dept_head_remarks TEXT NULL,
    ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS endorsement_ref_number VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS filing_date DATE NULL,
    ADD COLUMN IF NOT EXISTS endorsed_planting_site VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS endorsed_quantity INT NULL,
    ADD COLUMN IF NOT EXISTS technologist_validated TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS technologist_validated_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS technologist_validated_by INT NULL;

ALTER TABLE service_requests
    ADD COLUMN IF NOT EXISTS request_letter VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS proponent_name VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS association_name VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS recipient_name VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS recipient_position VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS activity_time VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS quantity_released INT NULL,
    ADD COLUMN IF NOT EXISTS released_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS released_by INT NULL,
    ADD COLUMN IF NOT EXISTS stock_verified TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS verified_material VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS verified_quantity INT NULL;

-- Add site_photos and coordinates to validation_reports
ALTER TABLE validation_reports
    ADD COLUMN IF NOT EXISTS site_photos TEXT NULL COMMENT 'JSON array of uploaded photo paths',
    ADD COLUMN IF NOT EXISTS site_lat DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS site_lng DECIMAL(10,7) NULL;
