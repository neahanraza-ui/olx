-- database/migration_v2_part1.sql

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
