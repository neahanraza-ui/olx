-- database/migration_v3_locations.sql

CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT DEFAULT NULL,
    type ENUM('country', 'city') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Seed Countries
INSERT INTO locations (name, type) VALUES ('Pakistan', 'country'); -- ID 1
INSERT INTO locations (name, type) VALUES ('Afghanistan', 'country'); -- ID 2

-- Seed Cities for Pakistan (parent_id = 1)
INSERT INTO locations (name, parent_id, type) VALUES ('Karachi', 1, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Lahore', 1, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Islamabad', 1, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Faisalabad', 1, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Rawalpindi', 1, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Multan', 1, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Peshawar', 1, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Quetta', 1, 'city');

-- Seed Cities for Afghanistan (parent_id = 2)
INSERT INTO locations (name, parent_id, type) VALUES ('Kabul', 2, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Kandahar', 2, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Herat', 2, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Mazar-i-Sharif', 2, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Jalalabad', 2, 'city');
INSERT INTO locations (name, parent_id, type) VALUES ('Kunduz', 2, 'city');
