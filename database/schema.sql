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
    status ENUM('pending','barangay_approved','formal_request_submitted','under_review','for_validation','validated','approved','rejected','routed','released') DEFAULT 'pending',
    referred_by INT NULL,
    remarks TEXT NULL,
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

-- Intervention Inventory (City Agriculturist's Office — Toril District)
INSERT IGNORE INTO planting_materials (material_name, material_type, quantity, unit, description) VALUES
('Banana Lakatan',      'Fruit Tree',         0, 'seedlings',   'Lakatan banana planting materials'),
('Avocado',             'Fruit Tree',         0, 'seedlings',   'Avocado fruit tree seedlings'),
('Marang',              'Fruit Tree',         0, 'seedlings',   'Marang fruit tree seedlings'),
('Durian',              'Fruit Tree',         0, 'seedlings',   'Durian fruit tree seedlings'),
('Lanzones',            'Fruit Tree',         0, 'seedlings',   'Lanzones fruit tree seedlings'),
('Rambutan',            'Fruit Tree',         0, 'seedlings',   'Rambutan fruit tree seedlings'),
('Jackfruit',           'Fruit Tree',         0, 'seedlings',   'Jackfruit tree seedlings'),
('Corn',                'Crop',               0, 'packs',       'Corn seeds for planting'),
('Coffee',              'Cash Crop',          0, 'seedlings',   'Coffee plant seedlings'),
('Cacao',               'Cash Crop',          0, 'seedlings',   'Cacao plant seedlings'),
('Tilapia Fingerlings', 'Aquaculture',        0, 'fingerlings', 'Tilapia fingerlings for aquaculture'),
('Hito Fingerling',     'Aquaculture',        0, 'fingerlings', 'Hito (catfish) fingerlings for aquaculture'),
('Trichogramma',        'Biological Control', 0, 'packs',       'Trichogramma biological pest control'),
('Fertilizer',          'Input/Supply',       0, 'bags',        'Agricultural fertilizer'),
('Vegetable Seeds',     'Crop',               0, 'packs',       'Assorted vegetable seeds');

-- UPDATE QUANTITIES — i-copy tanan, i-paste sa phpMyAdmin SQL tab, click Go
UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Banana Lakatan';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Avocado';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Marang';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Durian';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Lanzones';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Rambutan';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Jackfruit';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Corn';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Coffee';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Cacao';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Tilapia Fingerlings';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Hito Fingerling';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Trichogramma';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Fertilizer';UPDATE planting_materials SET quantity = 500 WHERE material_name = 'Vegetable Seeds';

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
ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending','barangay_approved','formal_request_submitted','under_review','for_validation','validated','approved','rejected','routed','released') DEFAULT 'pending';
