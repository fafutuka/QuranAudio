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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
