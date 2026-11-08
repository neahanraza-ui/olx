-- database/migration_v2_part2.sql

-- Ensure "Online" category exists
INSERT IGNORE INTO categories (name, icon, level) VALUES ('Online', 'fa-globe', 1);

-- Sample subcategories
INSERT IGNORE INTO categories (name, parent_id, level) 
SELECT 'Mobiles', id, 2 FROM categories WHERE name='Electronics' LIMIT 1;

INSERT IGNORE INTO categories (name, parent_id, level) 
SELECT 'Laptops', id, 2 FROM categories WHERE name='Electronics' LIMIT 1;

INSERT IGNORE INTO categories (name, parent_id, level) 
SELECT 'Cars', id, 2 FROM categories WHERE name='Vehicles' LIMIT 1;

-- Sample sub-subcategories
INSERT IGNORE INTO categories (name, parent_id, level) 
SELECT 'SUV', id, 3 FROM categories WHERE name='Cars' LIMIT 1;

INSERT IGNORE INTO categories (name, parent_id, level) 
SELECT 'Android', id, 3 FROM categories WHERE name='Mobiles' LIMIT 1;

INSERT IGNORE INTO categories (name, parent_id, level) 
SELECT 'iPhone', id, 3 FROM categories WHERE name='Mobiles' LIMIT 1;
