# Database Setup Guide

This guide will help you set up the database for the QuranAudio API.

## Prerequisites

- MySQL/MariaDB server running (XAMPP includes MySQL)
- Access to phpMyAdmin or MySQL command line

## Setup Instructions

### Option 1: Using phpMyAdmin

1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
2. Click on "Import" tab
3. Choose the file: `database/schema.sql`
4. Click "Go" to execute the SQL script

### Option 2: Using MySQL Command Line

```bash
# Navigate to the database directory
cd C:/xampp/htdocs/QuranAudio/database

# Import the schema
mysql -u root -p < schema.sql
```

### Option 3: Using the Migration Script

```bash
# From the project root
php database/migrate.php
```

## Database Structure

The database includes the following tables:

### 1. `reciters`
Stores information about Quran reciters.

**Columns:**
- `id` - Primary key
- `name` - Reciter's name in English
- `arabic_name` - Reciter's name in Arabic
- `relative_path` - Path to reciter's audio files
- `format` - Audio format (default: mp3)
- `files_size` - Total size of audio files
- `created_at`, `updated_at` - Timestamps

### 2. `recitations`
Stores recitation styles and metadata.

**Columns:**
- `id` - Primary key
- `reciter_id` - Foreign key to reciters table
- `reciter_name` - Reciter's name
- `style` - Recitation style (e.g., Murattal, Mujawwad)
- `translated_name` - JSON field for translated names
- `created_at`, `updated_at` - Timestamps

### 3. `audio_files`
Stores audio file metadata for chapters/verses.

**Columns:**
- `id` - Primary key
- `recitation_id` - Foreign key to recitations table
- `chapter_id` - Chapter number (1-114)
- `verse_number` - Verse number (optional, for ayah-level audio)
- `verse_key` - Verse identifier (e.g., "1:1")
- `file_size` - File size in bytes
- `format` - Audio format
- `audio_url` - URL to the audio file
- `duration` - Duration in milliseconds
- `juz_number` - Juz number (1-30)
- `page_number` - Page number (1-604)
- `hizb_number` - Hizb number (1-60)
- `rub_el_hizb_number` - Rub el Hizb number (1-240)
- `created_at`, `updated_at` - Timestamps

### 4. `timestamps`
Stores verse-level timing information.

**Columns:**
- `id` - Primary key
- `audio_file_id` - Foreign key to audio_files table
- `verse_key` - Verse identifier
- `timestamp_from` - Start time in milliseconds
- `timestamp_to` - End time in milliseconds
- `duration` - Duration in milliseconds
- `segments` - JSON field for word-level segments
- `created_at`, `updated_at` - Timestamps

## Sample Data

The schema includes sample data for 5 reciters and their recitations:

1. Mishary Rashid Alafasy (Murattal)
2. Abdul Basit (Murattal)
3. Mahmoud Khalil Al-Hussary (Murattal)
4. Saad Al-Ghamdi (Murattal)
5. Ahmed Al-Ajamy (Mujawwad)

## Adding Audio Files

To add actual audio files and timestamps, you need to:

1. Populate the `audio_files` table with your audio file metadata
2. Populate the `timestamps` table with verse timing information

Example:

```sql
-- Add audio file for Chapter 1 by Mishary Rashid Alafasy
INSERT INTO audio_files (recitation_id, chapter_id, file_size, format, audio_url, duration, juz_number, page_number, hizb_number, rub_el_hizb_number) 
VALUES (1, 1, 512000, 'mp3', 'https://cdn.quran.foundation/audio/reciter1/chapter1.mp3', 45000, 1, 1, 1, 1);

-- Add timestamps for verses in Chapter 1
INSERT INTO timestamps (audio_file_id, verse_key, timestamp_from, timestamp_to, duration, segments) 
VALUES 
(1, '1:1', 0, 5000, 5000, '[[1, 0, 1000], [2, 1000, 3000], [3, 3000, 5000]]'),
(1, '1:2', 5000, 10000, 5000, '[[1, 5000, 6000], [2, 6000, 8000], [3, 8000, 10000]]');
```

## Database Configuration

The database connection is configured in `src/config/database.php`. It automatically detects the environment:

- **Local Development**: Uses `root` user with no password
- **Production**: Uses configured credentials

Make sure to update the configuration file with your actual database credentials.

## Troubleshooting

### Connection Failed
- Ensure MySQL is running in XAMPP
- Check database credentials in `src/config/database.php`
- Verify the database name matches your configuration

### Tables Not Created
- Check MySQL error logs
- Ensure you have proper permissions
- Try creating the database manually first: `CREATE DATABASE fafutuk1_qurantafseer;`

### Foreign Key Constraints
- Ensure parent records exist before inserting child records
- Follow the order: reciters → recitations → audio_files → timestamps
