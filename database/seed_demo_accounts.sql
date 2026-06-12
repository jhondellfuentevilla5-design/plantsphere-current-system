-- ============================================================
-- PlantSphere Demo Accounts
-- Run this in phpMyAdmin to insert pre-registered organizer accounts
-- Password for ALL accounts: Password@123
-- ============================================================

USE plantsphere_secure_authentication_system_db;

-- ── Demo Organizer Accounts ───────────────────────────────
INSERT IGNORE INTO users (firstname, lastname, email, password, role, is_active, created_at) VALUES
('Maria',   'Santos',    'maria.santos@demo.com',    '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Jose',    'Reyes',     'jose.reyes@demo.com',      '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Ana',     'Cruz',      'ana.cruz@demo.com',        '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Pedro',   'Lim',       'pedro.lim@demo.com',       '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW()),
('Rosa',    'Garcia',    'rosa.garcia@demo.com',     '$2y$12$q5eCuMDNBcSP6uXR3J6sduQqm9Ij5QOTT0KagnTcB1r.WyYptB0f.', 'community_organizer', 1, NOW());

-- ── RSBSA Records (pre-verified) ─────────────────────────
INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000001', 'Toril',     'Davao City', 'Davao del Sur', 1.50, 'Fruit Trees',   '2024-01-15', 'verified' FROM users WHERE email = 'maria.santos@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000002', 'Catalunan', 'Davao City', 'Davao del Sur', 2.00, 'Corn',          '2024-02-10', 'verified' FROM users WHERE email = 'jose.reyes@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000003', 'Matina',    'Davao City', 'Davao del Sur', 0.75, 'Vegetables',    '2024-03-05', 'verified' FROM users WHERE email = 'ana.cruz@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000004', 'Buhangin',  'Davao City', 'Davao del Sur', 3.25, 'Agroforestry',  '2024-04-20', 'verified' FROM users WHERE email = 'pedro.lim@demo.com';

INSERT IGNORE INTO rsbsa_registry (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date, status)
SELECT id, '01-073-001-000005', 'Calinan',   'Davao City', 'Davao del Sur', 1.80, 'Mixed Farming', '2024-05-12', 'verified' FROM users WHERE email = 'rosa.garcia@demo.com';
