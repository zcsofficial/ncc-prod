CREATE DATABASE IF NOT EXISTS ncc_prod;
USE ncc_prod;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cadets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE, 
    full_name VARCHAR(255) NOT NULL,
    dob DATE NOT NULL,
    `rank` VARCHAR(50) NOT NULL, 
    rank_priority INT,
    email VARCHAR(100) NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    emergency_contact_number VARCHAR(15) NOT NULL,
    profile_picture VARCHAR(255),
    cadet_batch VARCHAR(50) NULL,  -- Added this field
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rest of the schema remains unchanged
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    author_id INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cadet_id INT NOT NULL,
    event_id INT NOT NULL,
    status ENUM('present', 'absent') DEFAULT 'absent',
    FOREIGN KEY (cadet_id) REFERENCES cadets(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE (cadet_id, event_id)  
);

CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cadet_id INT NOT NULL,
    achievement_name VARCHAR(255) NOT NULL,
    achievement_date DATE NOT NULL,
    certificate VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (cadet_id) REFERENCES cadets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS camps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camp_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    camp_details TEXT NOT NULL,
    camp_date DATE NOT NULL,
    eligibility TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS carousel_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, 
    message TEXT NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    `rank` VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE notifications
ADD COLUMN `read` TINYINT DEFAULT 0;

