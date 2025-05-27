
create database property_management;
use property_management;
-- 1. USERS Table
create Table users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 2. PROPERTIES Table
create Table properties (
    property_id INT PRIMARY KEY AUTO_INCREMENT,
    property_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    address_line1 VARCHAR(100) NOT NULL,
    address_line2 VARCHAR(100),
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    bedrooms INT NOT NULL,
    bathrooms DECIMAL(3,1) NOT NULL,
    surface INT,
    construction_status ENUM('foundation', 'framing', 'roofing', 'plumbing', 'electrical', 'finishing', 'final_inspection') NOT NULL,
    completion_percentage INT DEFAULT 0,
    estimated_completion DATE,
    sale_status ENUM('available', 'under_contract', 'sold', 'on_hold') DEFAULT 'available',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 3. PROPERTY_IMAGES Table
create Table property_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- 4. PROPERTY_VIDEOS Table
create Table property_videos (
    video_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    title VARCHAR(100),
    description TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- 5. PROPERTY_UPDATES Table
create Table property_updates (
    update_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    content TEXT NOT NULL,
    completion_percentage INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 6. UPDATE_IMAGES Table
create Table update_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    update_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (update_id) REFERENCES property_updates(update_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- 7. UPDATE_VIDEOS Table
create Table update_videos (
    video_id INT PRIMARY KEY AUTO_INCREMENT,
    update_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    title VARCHAR(100),
    description TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (update_id) REFERENCES property_updates(update_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- 8. USER_REQUESTS Table
create Table user_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    property_id INT,
    request_type ENUM('info', 'visit', 'offer') NOT NULL,
    message TEXT,
    status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id)
);
