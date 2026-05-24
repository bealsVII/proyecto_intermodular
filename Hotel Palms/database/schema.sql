-- =============================================
-- Hotel Reservation System - Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS hotel_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hotel_db;

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'receptionist', 'guest') DEFAULT 'guest',
    phone VARCHAR(20),
   dni VARCHAR(12) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_dni (dni)
) ENGINE=InnoDB;

-- =============================================
-- ROOMS TABLE
-- =============================================
CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type ENUM('single', 'double', 'triple', 'suite', 'family') NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL DEFAULT 1,
    price_per_night DECIMAL(10, 2) NOT NULL,
    description TEXT,
    amenities JSON,
    floor TINYINT UNSIGNED,
    is_available BOOLEAN DEFAULT TRUE,
    images JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type (room_type),
    INDEX idx_price (price_per_night),
    INDEX idx_availability (is_available)
) ENGINE=InnoDB;

-- =============================================
-- BOOKINGS TABLE
-- =============================================
CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests TINYINT UNSIGNED NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    special_requests TEXT,
    payment_method VARCHAR(50),
    confirmation_code VARCHAR(10) UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_room (room_id),
    INDEX idx_dates (check_in, check_out),
    INDEX idx_status (status),
    INDEX idx_confirmation (confirmation_code),
    CHECK (check_out > check_in),
    CHECK (guests <= capacity)
) ENGINE=InnoDB;

-- =============================================
-- REVIEWS TABLE (bonus)
-- =============================================
CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB;

-- =============================================
-- API KEYS TABLE (for public API)
-- =============================================
CREATE TABLE api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    key_hash VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    permissions JSON,
    rate_limit INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_key (key_hash),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =============================================
-- SEED DATA
-- =============================================

-- Default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password_hash, role, phone, dni, is_active)
VALUES
    ('Admin', 'System', 'admin@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '600000001', '12345678A', TRUE),
    ('Recepcionista', 'Hotel', 'reception@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', '600000002', '23456789B', TRUE),
    ('Juan', 'García', 'juan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guest', '600000003', '34567890C', TRUE);

-- Sample rooms
INSERT INTO rooms (room_number, room_type, capacity, price_per_night, description, amenities, floor, is_available)
VALUES
    ('101', 'single', 1, 45.00, 'Habitación individual con vistas al jardín', '["WiFi", "TV", "Climatización"]', 1, TRUE),
    ('102', 'single', 1, 50.00, 'Habitación individual con vistas a la ciudad', '["WiFi", "TV", "Climatización", "Minibar"]', 1, TRUE),
    ('201', 'double', 2, 75.00, 'Habitación doble con cama matrimonio', '["WiFi", "TV", "Climatización", "Minibar", "Baño privado"]', 2, TRUE),
    ('202', 'double', 2, 85.00, 'Habitación doble premium con bañera', '["WiFi", "TV", "Climatización", "Minibar", "Bañera", "Amantecado premium"]', 2, TRUE),
    ('203', 'double', 2, 80.00, 'Habitación doble con dos camas individuales', '["WiFi", "TV", "Climatización", "Minibar"]', 2, TRUE),
    ('301', 'triple', 3, 95.00, 'Habitación triple para familias', '["WiFi", "TV", "Climatización", "Minibar", "Baño privado"]', 3, TRUE),
    ('302', 'triple', 3, 105.00, 'Habitación triple superior', '["WiFi", "TV", "Climatización", "Minibar", "Terraza"]', 3, TRUE),
    ('401', 'suite', 2, 150.00, 'Suite ejecutiva con salón', '["WiFi", "TV", "Climatización", "Minibar", "Jacuzzi", "Amantecado premium", "Terraza"]', 4, TRUE),
    ('402', 'suite', 4, 200.00, 'Suite presidencial con vistas panorámicas', '["WiFi", "TV", "Climatización", "Minibar", "Jacuzzi", "Amantecado premium", "Terraza", "Desayuno incluido"]', 4, TRUE),
    ('103', 'family', 4, 120.00, 'Habitación familiar con dos dormitorios', '["WiFi", "TV", "Climatización", "Minibar", "Baño privado"]', 1, TRUE);

-- Sample bookings
INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, status, special_requests, confirmation_code)
VALUES
    (3, 1, '2025-02-01', '2025-02-03', 1, 90.00, 'confirmed', 'Cama firme', 'ABC12345'),
    (3, 2, '2025-02-05', '2025-02-08', 1, 150.00, 'confirmed', NULL, 'DEF67890'),
    (3, 3, '2025-02-10', '2025-02-12', 2, 150.00, 'pending', 'Piso alto', 'GHI11223');