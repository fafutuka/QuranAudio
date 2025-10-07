# Quran Audio API

A RESTful API implementation for Quran audio recitations using PHP and Slim Framework 4.

## Features

- **Chapter Reciters**: List all available reciters with their details
- **Recitations**: Get recitation styles and metadata
- **Audio Files**: Access audio files by chapter, juz, page, hizb, rub el hizb, or ayah
- **Timestamps**: Word-level timing information for precise audio playback
- **Pagination**: Efficient data retrieval with pagination support
- **Multi-language**: Language parameter support for internationalization

## Installation

1. Install dependencies using Composer:
```bash
composer install
```

2. Configure your web server to point to the `public` directory, or use PHP's built-in server:
```bash
php -S localhost:8080 -t public
```

3. Access the API at `http://localhost:8080`

## API Endpoints

### Base URL
`http://localhost:8080`

### Available Endpoints

#### 1. List Chapter Reciters
```
GET /chapter-reciters?language=en
```
Returns a list of all available reciters.

**Query Parameters:**
- `language` (optional): Language code (default: 'en')

**Response:**
```json
{
  "reciters": [
    {
      "id": 1,
      "name": "Mishary Rashid Alafasy",
      "arabic_name": "مشاری راشد العفاسی",
      "relative_path": "/alafasy/",
      "format": "mp3",
      "files_size": 1024000
    }
  ]
}
```

#### 2. List Recitations
```
GET /recitations?language=en
```
Returns a list of all available recitations.

**Response:**
```json
{
  "recitations": [
    {
      "id": 1,
      "reciter_name": "Mishary Rashid Alafasy",
      "style": "Murattal",
      "translated_name": {
        "name": "Mishary Rashid Alafasy",
        "language_name": "en"
      }
    }
  ]
}
```

#### 3. Get Chapter Audio File
```
GET /reciters/{id}/chapters/{chapter_number}?segments=true
```
Get audio file for a specific chapter by a reciter.

**Path Parameters:**
- `id`: Reciter ID
- `chapter_number`: Chapter number (1-114)

**Query Parameters:**
- `segments` (optional): Include word-level segments (true/false)

**Response:**
```json
{
  "audio_file": {
    "id": 1001,
    "chapter_id": 1,
    "file_size": 512000,
    "format": "mp3",
    "audio_url": "https://cdn.quran.foundation/audio/reciter1/chapter1.mp3",
    "duration": 45000,
    "timestamps": [
      {
        "verse_key": "1:1",
        "timestamp_from": 0,
        "timestamp_to": 5000,
        "duration": 5000,
        "segments": [[1, 0, 1000], [2, 1000, 3000]]
      }
    ]
  }
}
```

#### 4. Get Reciter Audio Files
```
GET /reciters/{id}/audio-files?language=en
```
Get all audio files for a specific reciter.

#### 5. Get Recitation Audio Files
```
GET /recitation-audio-files/{recitation_id}?chapter_number=1&fields=id,audio_url
```
Get audio files for a recitation with optional filters.

**Query Parameters:**
- `chapter_number` (optional): Filter by chapter
- `juz_number` (optional): Filter by juz
- `page_number` (optional): Filter by page
- `hizb_number` (optional): Filter by hizb
- `rub_el_hizb_number` (optional): Filter by rub el hizb
- `fields` (optional): Comma-separated list of fields to return

#### 6. Get Surah Ayah Recitations
```
GET /resources/recitations/{recitation_id}/{chapter_number}?page=1&per_page=10
```
Get ayah-level recitations for a specific surah.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 10)

**Response:**
```json
{
  "audio_files": [...],
  "pagination": {
    "per_page": 10,
    "current_page": 1,
    "next_page": 2,
    "total_pages": 5,
    "total_records": 50
  }
}
```

#### 7. Get Juz Ayah Recitations
```
GET /resources/recitations/{recitation_id}/juz/{juz_number}?page=1&per_page=10
```

#### 8. Get Page Ayah Recitations
```
GET /resources/recitations/{recitation_id}/pages/{page_number}?page=1&per_page=10
```

#### 9. Get Rub el Hizb Ayah Recitations
```
GET /resources/recitations/{recitation_id}/rub-el-hizb/{rub_el_hizb_number}?page=1&per_page=10
```

#### 10. Get Hizb Ayah Recitations
```
GET /resources/recitations/{recitation_id}/hizb/{hizb_number}?page=1&per_page=10
```

#### 11. Get Ayah Recitation
```
GET /resources/ayah-recitation/{recitation_id}/{ayah_key}
```
Get recitation for a specific ayah (e.g., ayah_key: "1:1" for Al-Fatiha, verse 1).

## Project Structure

```
QuranAudio/
├── public/
│   ├── index.php          # Application entry point
│   └── .htaccess          # URL rewriting rules
├── src/
│   ├── Controllers/       # Request handlers
│   │   ├── AudioController.php
│   │   ├── ReciterController.php
│   │   └── RecitationController.php
│   ├── Models/           # Data models
│   │   ├── AudioFile.php
│   │   ├── Recitation.php
│   │   ├── Reciter.php
│   │   └── Timestamp.php
│   └── Services/         # Business logic
│       ├── AudioService.php
│       ├── RecitationService.php
│       └── ReciterService.php
├── composer.json         # Dependencies
└── README.md            # This file
```

## Architecture

### Models
- **Reciter**: Represents a Quran reciter with metadata
- **Recitation**: Represents a recitation style
- **AudioFile**: Represents an audio file with metadata
- **Timestamp**: Represents verse timing information with optional word segments

### Services
- **ReciterService**: Manages reciter data
- **RecitationService**: Manages recitation data
- **AudioService**: Handles audio file retrieval, filtering, and pagination

### Controllers
- **ReciterController**: Handles reciter-related requests
- **RecitationController**: Handles recitation-related requests
- **AudioController**: Handles audio file requests with various filters

## Data Structure

The API uses mock data for demonstration. In production, replace the service layer with database queries.

### Entity Relationships
- A **Reciter** has many **Recitations**
- A **Recitation** has many **AudioFiles**
- An **AudioFile** has many **Timestamps**
- A **Timestamp** may have **Segments** (word-level timing)

## Development

### Adding Real Data
Replace the mock data in service classes with database queries:

1. Set up a database with tables for reciters, recitations, audio_files, and timestamps
2. Update service classes to query the database instead of using arrays
3. Consider using an ORM like Eloquent or Doctrine for easier data management

### Testing
Test endpoints using curl or a tool like Postman:

```bash
# List reciters
curl http://localhost:8080/chapter-reciters

# Get chapter audio
curl http://localhost:8080/reciters/1/chapters/1?segments=true

# Get ayah recitations
curl http://localhost:8080/resources/recitations/1/1?page=1&per_page=10
```

## Requirements

- PHP 7.4 or higher
- Composer
- Apache/Nginx (or PHP built-in server for development)

## License

This is a demonstration project based on Quran Foundation API documentation.

## Credits

API design based on [Quran Foundation's API Documentation](https://api-docs.quran.foundation/)
