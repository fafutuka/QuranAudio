# Tafseer Module Documentation

## Overview

The Tafseer module extends the Quran Audio API to support Islamic commentary (Tafseer) audio files. Unlike the recitation module which ties audio to individual verses, the Tafseer module handles audio files that span verse ranges, potentially across multiple chapters (surahs).

## Key Differences from Recitation Module

| Feature | Recitation Module | Tafseer Module |
|---------|------------------|----------------|
| **Audio Scope** | Single verse/chapter | Verse ranges (can span chapters) |
| **Primary Entity** | Reciter | Mufasser (Commentator) |
| **Audio Type** | Recitation | Tafseer |
| **Audio File** | AudioFile | AudioTafseer |
| **Verse Binding** | `verse_key` (1:1) | `verse_range_from` & `verse_range_to` (1:1 to 1:7) |

## Database Schema

### Tables

1. **mufassers** - Islamic scholars who provide Quranic commentary
2. **tafseers** - Commentary works by mufassers
3. **audio_tafseers** - Audio files containing tafseer for verse ranges
4. **tafseer_timestamps** - Word-level timing data within audio files

### Key Fields

#### audio_tafseers
- `verse_range_from`: Starting verse (format: "chapter:verse", e.g., "1:1")
- `verse_range_to`: Ending verse (format: "chapter:verse", e.g., "1:7")
- `tafseer_id`: Reference to the tafseer work
- `audio_url`: URL to the audio file

## API Endpoints

### Mufassers (Commentators)

```
GET    /mufassers                    # Get all mufassers
GET    /mufassers/{id}               # Get specific mufasser
GET    /mufassers/{id}/tafseers      # Get tafseers by mufasser
POST   /mufassers                    # Create mufasser (protected)
PUT    /mufassers/{id}               # Update mufasser (protected)
DELETE /mufassers/{id}               # Delete mufasser (protected)
```

### Tafseers (Commentary Works)

```
GET    /tafseers                     # Get all tafseers
GET    /tafseers/{id}                # Get specific tafseer
GET    /tafseers/{id}/audio-files    # Get audio files for tafseer
GET    /tafseers/verses/{verse_from} # Get tafseer for single verse
GET    /tafseers/verses/{verse_from}/{verse_to} # Get tafseer for verse range
POST   /tafseers                     # Create tafseer (protected)
PUT    /tafseers/{id}                # Update tafseer (protected)
DELETE /tafseers/{id}                # Delete tafseer (protected)
```

### Audio Tafseers

```
GET    /audio-tafseers/{id}          # Get specific audio tafseer
GET    /audio-tafseers/verses/{verse_from} # Get audio for single verse
GET    /audio-tafseers/verses/{verse_from}/{verse_to} # Get audio for verse range
POST   /audio-tafseers               # Create audio tafseer (protected)
PUT    /audio-tafseers/{id}          # Update audio tafseer (protected)
DELETE /audio-tafseers/{id}          # Delete audio tafseer (protected)
```

## Query Parameters

### Common Parameters
- `language`: Language preference (default: 'en')
- `page`: Page number for pagination (default: 1)
- `per_page`: Items per page (default: 10)
- `segments`: Include word-level timing data (boolean, default: false)

### Tafseer-Specific Parameters
- `tafseer_ids`: Comma-separated list of tafseer IDs to filter by

## Usage Examples

### 1. Get All Mufassers
```bash
curl "http://localhost/QuranAudio/api/mufassers"
```

### 2. Get Tafseer for Verse Range
```bash
curl "http://localhost/QuranAudio/api/tafseers/verses/1:1/1:7?segments=true"
```

### 3. Get Audio Tafseer with Specific Commentators
```bash
curl "http://localhost/QuranAudio/api/audio-tafseers/verses/2:1/2:5?tafseer_ids=1,2&segments=true"
```

### 4. Create Audio Tafseer (Protected)
```bash
curl -X POST "http://localhost/QuranAudio/api/audio-tafseers" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tafseer_id": 1,
    "audio_url": "https://example.com/audio/tafseer.mp3",
    "verse_range_from": "1:1",
    "verse_range_to": "1:7",
    "duration": 1200,
    "file_size": 2400000
  }'
```

## Verse Range Format

The module uses the standard Quranic verse key format:
- **Format**: `chapter:verse`
- **Examples**: 
  - `1:1` (Al-Fatiha, verse 1)
  - `2:255` (Al-Baqarah, verse 255)
  - `114:6` (An-Nas, verse 6)

### Cross-Chapter Ranges
Audio files can span multiple chapters:
- `verse_range_from`: `1:7` (Al-Fatiha, verse 7)
- `verse_range_to`: `2:3` (Al-Baqarah, verse 3)

## Response Format

### Single Resource
```json
{
  "mufasser": {
    "id": 1,
    "name": "Ibn Kathir",
    "arabic_name": "ابن كثير",
    "biography": "Famous Islamic scholar...",
    "birth_year": 1300,
    "death_year": 1373
  }
}
```

### Collection with Pagination
```json
{
  "tafseers": [...],
  "pagination": {
    "per_page": 10,
    "current_page": 1,
    "next_page": 2,
    "total_pages": 5,
    "total_records": 50
  }
}
```

### Audio Tafseer with Segments
```json
{
  "audio_tafseer": {
    "id": 1,
    "tafseer_id": 1,
    "audio_url": "https://example.com/audio.mp3",
    "verse_range_from": "1:1",
    "verse_range_to": "1:7",
    "chapter_start": 1,
    "verse_start": 1,
    "chapter_end": 1,
    "verse_end": 7,
    "duration": 1200,
    "timestamps": [
      {
        "verse_key": "1:1",
        "timestamp_ms": 0,
        "duration_ms": 15000,
        "segments": {
          "words": [
            {"text": "بِسْمِ", "start": 0, "end": 2000},
            {"text": "اللَّهِ", "start": 2000, "end": 4000}
          ]
        }
      }
    ]
  }
}
```

## Sample Data

The module includes sample data for 5 famous mufassers:
1. **Ibn Kathir** (1300-1373) - Comprehensive commentary
2. **Al-Tabari** (838-923) - Detailed exegesis
3. **Al-Qurtubi** (1214-1273) - Legal commentary
4. **As-Saadi** (1889-1956) - Clear and accessible tafseer
5. **Ibn Abbas** (619-687) - Classical commentary

## Integration with Main Application

### 1. Add Route Includes
Add these lines to your main route configuration:

```php
// In your main routes file
require __DIR__ . '/routes/mufasser.php';
require __DIR__ . '/routes/tafseer.php';
require __DIR__ . '/routes/audio_tafseer.php';
```

### 2. Register Services in DI Container
```php
// In your DI container configuration
$container->set('App\Services\MufasserService', function() use ($container) {
    return new App\Services\MufasserService($container->get('App\Services\DatabaseService'));
});

$container->set('App\Services\TafseerService', function() use ($container) {
    return new App\Services\TafseerService($container->get('App\Services\DatabaseService'));
});

$container->set('App\Services\AudioTafseerService', function() use ($container) {
    return new App\Services\AudioTafseerService($container->get('App\Services\DatabaseService'));
});
```

### 3. Run Database Migration
```bash
# Import the tafseer schema
mysql -u username -p database_name < database/tafseer_schema.sql
```

## Testing

Use the provided test script:
```bash
php test-tafseer-api.php
```

This will test all major endpoints and display the responses.

## Security

- All CREATE, UPDATE, DELETE operations require JWT authentication
- Input validation for verse range format
- SQL injection protection through parameterized queries
- JSON field encoding/decoding handled securely

## Performance Considerations

- Indexed on `verse_range_from`, `verse_range_to` for efficient range queries
- Indexed on `tafseer_id` for fast filtering
- Pagination support for large datasets
- Optional segments loading to reduce response size

## Future Enhancements

1. **Search Functionality**: Full-text search within tafseer content
2. **Bookmarking**: User bookmarks for favorite tafseers
3. **Playlists**: Custom tafseer playlists spanning multiple ranges
4. **Offline Support**: Download management for offline listening
5. **Multi-language Support**: Translated tafseers in different languages