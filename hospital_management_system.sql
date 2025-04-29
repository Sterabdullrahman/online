-- Create the database
CREATE DATABASE IF NOT EXISTS hospital_management_system;
USE hospital_management_system;

-- Users Table (for all user types)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('super_admin', 'admin', 'doctor', 'patient') NOT NULL,
    hospital_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Hospitals Table
CREATE TABLE hospitals (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    logo_url VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Doctor Profiles Table (updated with graduation year and medical certificates)
CREATE TABLE doctor_profiles (
    doctor_id INT PRIMARY KEY,
    dept_id INT NOT NULL,
    qualification VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    graduation_year YEAR NOT NULL,
    medical_certificate_url VARCHAR(255) NULL,
    bio TEXT,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id)
);

-- Doctor Ratings Table (new table for star ratings)
CREATE TABLE doctor_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (patient_id) REFERENCES users(user_id),
    CONSTRAINT unique_rating_per_patient UNIQUE (doctor_id, patient_id)
);

-- Doctor Schedules Table
CREATE TABLE doctor_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_per_patient INT DEFAULT 15,
    max_patients INT DEFAULT 10,
    is_recurring BOOLEAN DEFAULT TRUE,
    valid_from DATE NOT NULL,
    valid_to DATE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id)
);

-- Appointments Table
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    schedule_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (schedule_id) REFERENCES doctor_schedules(schedule_id)
);

INSERT INTO users (
    username, 
    email, 
    password, 
    full_name, 
    user_type, 
    is_active, 
    created_at
) VALUES (
    'superadmin',  -- Choose a username
    'superadmin@hospital.com',  -- Admin email
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Hashed "password"
    'System Super Admin',  -- Full name
    'super_admin',  -- User type
    1,  -- is_active (1 for true)
    NOW()  -- Creation timestamp
);
ALTER TABLE appointments
DROP FOREIGN KEY appointments_ibfk_3;

