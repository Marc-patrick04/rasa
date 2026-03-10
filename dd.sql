-- Create database
CREATE DATABASE rasa_db;

-- Connect to database
\c rasa_db;

-- Users table (admin only)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Positions table
CREATE TABLE positions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Candidates table (for current nominations)
CREATE TABLE candidates (
    id SERIAL PRIMARY KEY,
    position_id INTEGER REFERENCES positions(id) ON DELETE CASCADE,
    full_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(50),
    year_of_study INTEGER,
    phone_number VARCHAR(20),
    email VARCHAR(100),
    manifesto TEXT,
    nomination_type VARCHAR(20) CHECK (nomination_type IN ('self', 'other')),
    nominated_by VARCHAR(100),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Previous leaders table
CREATE TABLE previous_leaders (
    id SERIAL PRIMARY KEY,
    position_id INTEGER REFERENCES positions(id) ON DELETE SET NULL,
    full_name VARCHAR(100) NOT NULL,
    year_served VARCHAR(20),
    achievements TEXT,
    photo_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$a69LVWZbLpiSHgFER934p.2lv3zEWF.bhFX1m/tZ1a1ErsLuQbLhW'); -- You'll hash this via PHP