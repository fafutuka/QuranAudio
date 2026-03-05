-- QuranAudio Database Schema
-- This schema supports the Quran Audio API with reciters, recitations, audio files, and timestamps

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS fafutuk1_qurantafseer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fafutuk1_qurantafseer;

-- Table: reciters
-- Stores information about Quran reciters
CREATE TABLE IF NOT EXISTS reciters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    arabic_name VARCHAR(255) DEFAULT NULL,
    relative_path VARCHAR(255) DEFAULT NULL,
    format VARCHAR(10) DEFAULT 'mp3',
    files_size BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: recitations
-- Stores recitation styles and metadata
CREATE TABLE IF NOT EXISTS recitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reciter_id INT NOT NULL,
    reciter_name VARCHAR(255) NOT NULL,
    style VARCHAR(100) DEFAULT NULL,
    translated_name JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reciter_id) REFERENCES reciters(id) ON DELETE CASCADE,
    INDEX idx_reciter_id (reciter_id),
    INDEX idx_style (style)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audio_files
-- Stores audio file metadata for chapters/verses
CREATE TABLE IF NOT EXISTS audio_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recitation_id INT NOT NULL,
    chapter_id INT NOT NULL,
    verse_number INT DEFAULT NULL,
    verse_key VARCHAR(20) DEFAULT NULL,
    file_size BIGINT DEFAULT 0,
    format VARCHAR(10) DEFAULT 'mp3',
    audio_url TEXT NOT NULL,
    duration INT DEFAULT 0,
    juz_number INT DEFAULT NULL,
    page_number INT DEFAULT NULL,
    hizb_number INT DEFAULT NULL,
    rub_el_hizb_number INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recitation_id) REFERENCES recitations(id) ON DELETE CASCADE,
    INDEX idx_recitation_chapter (recitation_id, chapter_id),
    INDEX idx_verse_key (verse_key),
    INDEX idx_juz (juz_number),
    INDEX idx_page (page_number),
    INDEX idx_hizb (hizb_number),
    INDEX idx_rub_el_hizb (rub_el_hizb_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: timestamps
-- Stores verse-level timing information
CREATE TABLE IF NOT EXISTS timestamps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audio_file_id INT NOT NULL,
    verse_key VARCHAR(20) NOT NULL,
    timestamp_from INT NOT NULL,
    timestamp_to INT NOT NULL,
    duration INT NOT NULL,
    segments JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audio_file_id) REFERENCES audio_files(id) ON DELETE CASCADE,
    INDEX idx_audio_file (audio_file_id),
    INDEX idx_verse_key (verse_key)
);

-- Table: roles
-- Stores user roles for RBAC
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: users
-- Stores user accounts with role association
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed roles
INSERT INTO roles (name, slug) VALUES 
('Super Admin', 'superadmin'),
('Admin', 'admin'),
('Moderator', 'moderator');


-- Insert sample data for reciters
INSERT INTO reciters (id, name, arabic_name, relative_path, format, files_size) VALUES
(1, 'Mishary Rashid Alafasy', 'مشاری راشد العفاسی', '/alafasy/', 'mp3', 1024000),
(2, 'Abdul Basit', 'عبد الباسط عبد الصمد', '/abdul_basit/', 'mp3', 950000),
(3, 'Mahmoud Khalil Al-Hussary', 'محمود خليل الحصري', '/hussary/', 'mp3', 1100000),
(4, 'Saad Al-Ghamdi', 'سعد الغامدي', '/ghamdi/', 'mp3', 980000),
(5, 'Ahmed Al-Ajamy', 'أحمد العجمي', '/ajamy/', 'mp3', 1050000);

-- Insert sample data for recitations
INSERT INTO recitations (id, reciter_id, reciter_name, style, translated_name) VALUES
(1, 1, 'Mishary Rashid Alafasy', 'Murattal', '{"name": "Mishary Rashid Alafasy", "language_name": "en"}'),
(2, 2, 'Abdul Basit', 'Murattal', '{"name": "Abdul Basit", "language_name": "en"}'),
(3, 3, 'Mahmoud Khalil Al-Hussary', 'Murattal', '{"name": "Mahmoud Khalil Al-Hussary", "language_name": "en"}'),
(4, 4, 'Saad Al-Ghamdi', 'Murattal', '{"name": "Saad Al-Ghamdi", "language_name": "en"}'),
(5, 5, 'Ahmed Al-Ajamy', 'Mujawwad', '{"name": "Ahmed Al-Ajamy", "language_name": "en"}');

-- Note: Audio files and timestamps should be populated based on actual audio data
-- The following is a sample structure for reference

-- Sample audio file for Chapter 1 (Al-Fatiha) by Mishary Rashid Alafasy
-- INSERT INTO audio_files (recitation_id, chapter_id, file_size, format, audio_url, duration, juz_number, page_number, hizb_number, rub_el_hizb_number) VALUES
-- (1, 1, 512000, 'mp3', 'https://cdn.quran.foundation/audio/reciter1/chapter1.mp3', 45000, 1, 1, 1, 1);

-- Sample timestamps for verses in Chapter 1
-- INSERT INTO timestamps (audio_file_id, verse_key, timestamp_from, timestamp_to, duration, segments) VALUES
-- (1, '1:1', 0, 5000, 5000, '[[1, 0, 1000], [2, 1000, 3000], [3, 3000, 5000]]');

-- Tafseer Module Tables
-- Mufassers table (equivalent to reciters for tafseer)
CREATE TABLE IF NOT EXISTS mufassers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    arabic_name VARCHAR(255),
    relative_path VARCHAR(500),
    format VARCHAR(10) DEFAULT 'mp3',
    files_size BIGINT DEFAULT 0,
    biography TEXT,
    birth_year INT,
    death_year INT,
    -- Image fields for Cloudinary integration
    avatar_url VARCHAR(1000) NULL,
    avatar_cloudinary_id VARCHAR(255) NULL,
    background_url VARCHAR(1000) NULL,
    background_cloudinary_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_avatar_cloudinary_id (avatar_cloudinary_id),
    INDEX idx_background_cloudinary_id (background_cloudinary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tafseers table (equivalent to recitations)
CREATE TABLE IF NOT EXISTS tafseers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mufasser_id INT NOT NULL,
    year INT,
    language VARCHAR(10) DEFAULT 'ar',
    translated_name JSON,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mufasser_id) REFERENCES mufassers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audio Tafseers table (equivalent to audio_files but with verse ranges)
CREATE TABLE IF NOT EXISTS audio_tafseers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tafseer_id INT NOT NULL,
    file_size BIGINT DEFAULT 0,
    format VARCHAR(10) DEFAULT 'mp3',
    audio_url VARCHAR(1000) NOT NULL,
    duration INT DEFAULT 0,
    verse_range_from VARCHAR(20) NOT NULL, -- Format: "chapter:verse" e.g., "1:1"
    verse_range_to VARCHAR(20) NOT NULL,   -- Format: "chapter:verse" e.g., "1:7"
    segments JSON,
    -- Cloudinary fields
    cloudinary_public_id VARCHAR(255) NULL,
    audio_format VARCHAR(10) DEFAULT 'mp3',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tafseer_id) REFERENCES tafseers(id) ON DELETE CASCADE,
    INDEX idx_verse_range (verse_range_from, verse_range_to),
    INDEX idx_tafseer_id (tafseer_id),
    INDEX idx_cloudinary_public_id (cloudinary_public_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tafseer Timestamps table (for word-level timing within audio tafseers)
CREATE TABLE IF NOT EXISTS tafseer_timestamps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audio_tafseer_id INT NOT NULL,
    verse_key VARCHAR(20) NOT NULL, -- Format: "chapter:verse"
    timestamp_ms INT NOT NULL,
    duration_ms INT DEFAULT 0,
    segments JSON, -- Word-level timing data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audio_tafseer_id) REFERENCES audio_tafseers(id) ON DELETE CASCADE,
    INDEX idx_audio_tafseer_id (audio_tafseer_id),
    INDEX idx_verse_key (verse_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for mufassers
INSERT INTO mufassers (name, arabic_name, relative_path, biography, birth_year, death_year) VALUES
('Ibn Kathir', 'ابن كثير', 'ibn_kathir', 'Famous Islamic scholar and historian known for his tafseer of the Quran', 1300, 1373),
('Al-Tabari', 'الطبري', 'al_tabari', 'Persian Islamic scholar and historian, author of comprehensive Quranic commentary', 838, 923),
('Al-Qurtubi', 'القرطبي', 'al_qurtubi', 'Andalusian Islamic jurist and Quranic exegete', 1214, 1273),
('As-Saadi', 'السعدي', 'as_saadi', 'Saudi Arabian Islamic scholar known for his clear and concise tafseer', 1889, 1956),
('Ibn Abbas', 'ابن عباس', 'ibn_abbas', 'Companion of Prophet Muhammad and renowned interpreter of the Quran', 619, 687);

-- Sample data for tafseers
INSERT INTO tafseers (mufasser_id, year, language, translated_name, description) VALUES
(1, 1365, 'ar', '{"name": "Tafseer Ibn Kathir", "language_name": "Arabic"}', 'Comprehensive commentary on the Quran by Ibn Kathir'),
(2, 915, 'ar', '{"name": "Jami al-Bayan", "language_name": "Arabic"}', 'Detailed exegesis of the Quran by Al-Tabari'),
(3, 1260, 'ar', '{"name": "Al-Jami li-Ahkam al-Quran", "language_name": "Arabic"}', 'Legal commentary on the Quran by Al-Qurtubi'),
(4, 1950, 'ar', '{"name": "Taysir al-Karim ar-Rahman", "language_name": "Arabic"}', 'Clear and accessible tafseer by As-Saadi'),
(5, 680, 'ar', '{"name": "Tanwir al-Miqbas", "language_name": "Arabic"}', 'Classical commentary attributed to Ibn Abbas');

-- Sample audio tafseer entries
INSERT INTO audio_tafseers (tafseer_id, audio_url, duration, verse_range_from, verse_range_to, file_size) VALUES
(1, 'https://example.com/audio/ibn_kathir/001_001-007.mp3', 1200, '1:1', '1:7', 2400000),
(1, 'https://example.com/audio/ibn_kathir/001_008-020.mp3', 1800, '1:8', '1:20', 3600000),
(2, 'https://example.com/audio/al_tabari/001_001-007.mp3', 1500, '1:1', '1:7', 3000000),
(3, 'https://example.com/audio/al_qurtubi/002_001-005.mp3', 2100, '2:1', '2:5', 4200000),
(4, 'https://example.com/audio/as_saadi/001_001-007.mp3', 900, '1:1', '1:7', 1800000);

-- Sample timestamp entries
INSERT INTO tafseer_timestamps (audio_tafseer_id, verse_key, timestamp_ms, duration_ms, segments) VALUES
(1, '1:1', 0, 15000, '{"words": [{"text": "بِسْمِ", "start": 0, "end": 2000}, {"text": "اللَّهِ", "start": 2000, "end": 4000}]}'),
(1, '1:2', 15000, 12000, '{"words": [{"text": "الْحَمْدُ", "start": 15000, "end": 17000}, {"text": "لِلَّهِ", "start": 17000, "end": 19000}]}'),
(2, '1:1', 0, 18000, '{"words": [{"text": "بِسْمِ", "start": 0, "end": 3000}, {"text": "اللَّهِ", "start": 3000, "end": 6000}]}');