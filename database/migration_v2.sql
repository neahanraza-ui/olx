-- database/migration_v2.sql

-- Update Categories table
ALTER TABLE categories ADD COLUMN IF NOT EXISTS level INT DEFAULT 1;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Update Products table (using 'products' as the ad table)
ALTER TABLE products ADD COLUMN IF NOT EXISTS subcategory_id INT DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS sub_subcategory_id INT DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS country VARCHAR(50) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS `condition` ENUM('New', 'Used') DEFAULT 'Used';
ALTER TABLE products ADD COLUMN IF NOT EXISTS online_product BOOLEAN DEFAULT FALSE;
ALTER TABLE products ADD COLUMN IF NOT EXISTS manual_location TEXT DEFAULT NULL;

-- Add Foreign Keys for depth
ALTER TABLE products ADD CONSTRAINT fk_subcategory FOREIGN KEY (subcategory_id) REFERENCES categories(id) ON DELETE SET NULL;
ALTER TABLE products ADD CONSTRAINT fk_sub_subcategory FOREIGN KEY (sub_subcategory_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Create Transactions table (Win System)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    agreed_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Reviews table (Online Products Only)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ensure "Online" category exists
INSERT IGNORE INTO categories (name, icon, level) VALUES ('Online', 'fa-globe', 1);

-- Sample subcategories using a temporary variable/join approach for better compatibility
SET @electronics_id = (SELECT id FROM categories WHERE name='Electronics' LIMIT 1);
SET @vehicles_id = (SELECT id FROM categories WHERE name='Vehicles' LIMIT 1);

INSERT IGNORE INTO categories (name, parent_id, level) VALUES 
('Mobiles', @electronics_id, 2),
('Laptops', @electronics_id, 2),
('Cars', @vehicles_id, 2);

-- Sample sub-subcategories
SET @mobiles_id = (SELECT id FROM categories WHERE name='Mobiles' AND parent_id = @electronics_id LIMIT 1);
SET @cars_id = (SELECT id FROM categories WHERE name='Cars' AND parent_id = @vehicles_id LIMIT 1);

INSERT IGNORE INTO categories (name, parent_id, level) VALUES 
('SUV', @cars_id, 3),
('Android', @mobiles_id, 3),
('iPhone', @mobiles_id, 3);
