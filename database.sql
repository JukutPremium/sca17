-- Database: school_complaint

CREATE DATABASE IF NOT EXISTS school_complaint;
USE school_complaint;

-- Tabel Users (Admin & Siswa)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'siswa') DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kategori
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT '📂',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Aspirasi/Pengaduan
CREATE TABLE IF NOT EXISTS aspirations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'proses', 'selesai') DEFAULT 'pending',
    progress TEXT,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Insert data sample categories
INSERT INTO categories (name, description, icon) VALUES
('Ruang Kelas', 'Pengaduan terkait fasilitas ruang kelas', '🏫'),
('Toilet', 'Pengaduan terkait kebersihan dan fasilitas toilet', '🚽'),
('Laboratorium', 'Pengaduan terkait peralatan dan fasilitas laboratorium', '🔬'),
('Perpustakaan', 'Pengaduan terkait buku dan fasilitas perpustakaan', '📚'),
('Lapangan', 'Pengaduan terkait lapangan olahraga dan area outdoor', '⚽'),
('Lainnya', 'Pengaduan lainnya yang tidak termasuk kategori di atas', '📋');

-- Insert data sample
-- Password: admin123 dan siswa123 (hashed)
INSERT INTO users (username, password, name, role) VALUES
('admin', '$2a$12$jKuV9apcnSeOdvJzKsrAjuXSlgZ6EHU7DMTUGrKLn1a5xqtGvm.H.', 'Administrator', 'admin'),
('siswa1', '$2a$12$.nxbcv8obchOGm2pTX8sqO9.mNPebUN5ZzN6QgdHU36oSDfXUQ7UG', 'Budi Santoso', 'siswa'),
('siswa2', '$2a$12$.nxbcv8obchOGm2pTX8sqO9.mNPebUN5ZzN6QgdHU36oSDfXUQ7UG', 'Siti Nurhaliza', 'siswa');

-- Insert sample aspirations
INSERT INTO aspirations (user_id, category_id, title, description, status, feedback, progress) VALUES
(2, 1, 'Kipas Angin Rusak', 'Kipas angin di kelas XII IPA 1 tidak berfungsi', 'selesai', 'Sudah diperbaiki oleh teknisi', 'Kipas angin sudah diganti dengan yang baru'),
(2, 2, 'Kran Air Bocor', 'Kran air di toilet lantai 2 bocor terus', 'proses', 'Sedang dalam proses perbaikan', 'Teknisi sedang mengecek'),
(3, 4, 'AC Tidak Dingin', 'AC di perpustakaan tidak dingin', 'pending', NULL, NULL);
