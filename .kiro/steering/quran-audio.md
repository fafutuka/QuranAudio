---
inclusion: always
---

# Quran Audio API - Development Guidelines

## Project Overview

RESTful API for Quran audio recitations using PHP 8.2+ and Slim Framework 4. Provides access to reciter metadata, recitation styles, audio files, and word-level timestamps organized by Quranic structures (chapters, juz, pages, hizb, rub el hizb, ayahs).

## Architecture Pattern

**Three-Layer Architecture:**
- **Controllers** (`src/Controllers/`): Handle HTTP requests/responses, parameter validation, status codes
- **Services** (`src/Services/`): Business logic, database queries, data transformation
- **Models** (`src/Models/`): Data structures representing entities (Reciter, Recitation, AudioFile, Timestamp)

**Dependency Injection:** Use PHP-DI container for service instantiation. All dependencies injected via constructor.

## Code Style & Conventions

### PHP Standards
- **PHP Version:** 8.2+ required
- **PSR-4 Autoloading:** Namespace `App\` maps to `src/`
- **Type Declarations:** Use strict types for method parameters and return types where possible
- **Visibility:** Always declare property/method visibility (public, private, protected)

### Naming Conventions
- **Classes:** PascalCase (e.g., `AudioController`, `ReciterService`)
- **Methods:** camelCase (e.g., `getChapterAudio`, `validateToken`)
- **Variables:** camelCase (e.g., `$reciterId`, `$audioFiles`)
- **Database Tables:** snake_case (e.g., `audio_files`, `rub_el_hizb_number`)
- **Route Parameters:** snake_case (e.g., `{chapter_number}`, `{rub_el_hizb_number}`)

### File Organization
```
src/
├── Controllers/     # HTTP request handlers
├── Services/        # Business logic & DB queries
├── Models/          # Data structures
├── Middleware/      # Request/response interceptors
├── routes/          # Route definitions by resource
└── config/          # Configuration files
```

## Database Patterns

### Query Execution
- Use `DatabaseService` for all database operations
- **Read operations:** `$db->read()`, `$db->readById()`, `$db->runQuery()`
- **Write operations:** `$db->create()`, `$db->update()`, `$db->delete()`
- **Prepared statements:** Always use parameterized queries to prevent SQL injection

### JSON Fields
- Store complex data (segments, translated_name) as JSON in database
- Decode JSON strings in services: `json_decode($data['segments'], true)`
- Models receive decoded arrays, not JSON strings

### Foreign Keys
- Enforce referential integrity with `ON DELETE CASCADE`
- Join tables for related data (e.g., `audio_files` JOIN `recitations`)

## Controller Patterns

### Request Handling
```php
public function methodName(Request $request, Response $response, array $args): Response {
    // 1. Extract and cast parameters
    $id = (int)$args['id'];
    $queryParam = $request->getQueryParams()['param'] ?? 'default';
    
    // 2. Call service layer
    $result = $this->service->getData($id, $queryParam);
    
    // 3. Handle not found
    if (!$result) {
        $response->getBody()->write(json_encode(['error' => 'Not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    
    // 4. Return JSON response
    $response->getBody()->write(json_encode(['data' => $result]));
    return $response->withHeader('Content-Type', 'application/json');
}
```

### Status Codes
- **200 OK:** Successful GET/PUT
- **201 Created:** Successful POST
- **400 Bad Request:** Validation errors
- **401 Unauthorized:** Missing/invalid JWT token
- **403 Forbidden:** Insufficient permissions
- **404 Not Found:** Resource doesn't exist

## Service Layer Patterns

### Data Retrieval
- Return Model instances or arrays of Models
- Apply filters via SQL WHERE clauses with parameterized values
- Handle pagination with LIMIT/OFFSET
- Return null for single items not found, empty array for collections

### Pagination Structure
```php
[
    'audio_files' => [...],
    'pagination' => [
        'per_page' => 10,
        'current_page' => 1,
        'next_page' => 2,
        'total_pages' => 5,
        'total_records' => 50
    ]
]
```

### Timestamps with Segments
- Fetch timestamps separately after audio file retrieval
- Decode JSON segments field
- Conditionally include segments based on query parameter
- Attach timestamps array to AudioFile model

## Model Patterns

### Constructor Pattern
```php
public function __construct($data) {
    $this->id = $data['id'] ?? null;
    $this->name = $data['name'] ?? '';
    $this->optional_field = $data['optional_field'] ?? null;
}
```

### Properties
- Use public properties for simple data structures
- No getters/setters unless business logic required
- Initialize arrays to empty: `public $timestamps = [];`

## Authentication & Authorization

### JWT Authentication
- **Library:** firebase/php-jwt
- **Token Generation:** Include user ID, email, role in payload
- **Token Validation:** Use `JwtMiddleware` for protected routes
- **Token Expiry:** 24 hours (configurable in `config/app.php`)

### Middleware Usage
```php
// In route files
$app->get('/protected', [Controller::class, 'method'])
    ->add($container->get('App\Middleware\JwtMiddleware'));
```

### Role-Based Access Control (RBAC)
- **Roles:** superadmin, admin, moderator (stored in `roles` table)
- **Role Middleware:** `RoleMiddleware` checks user role from JWT
- **User Model:** Include role_slug and role_name from JOIN with roles table

## API Endpoint Patterns

### Resource Naming
- Use plural nouns: `/chapter-reciters`, `/recitations`, `/audio-files`
- Nested resources: `/reciters/{id}/chapters/{chapter_number}`
- Action-based: `/resources/recitations/{id}/{chapter_number}`

### Query Parameters
- **Filtering:** `?chapter_number=1&juz_number=2`
- **Pagination:** `?page=1&per_page=10`
- **Field Selection:** `?fields=id,audio_url,duration`
- **Language:** `?language=en`
- **Segments:** `?segments=true` (boolean for word-level timing)

### Response Format
Always return JSON with appropriate structure:
```php
// Single resource
['audio_file' => $audioFile]

// Collection
['audio_files' => [...]]

// With metadata
['audio_files' => [...], 'meta' => ['reciter_name' => '...']]

// With pagination
['audio_files' => [...], 'pagination' => [...]]

// Errors
['error' => 'Error message']
```

## Configuration Management

### Environment Detection
- **Local:** Detected by `localhost` or `127.0.0.1` in `HTTP_HOST`
- **Production:** All other hosts
- **Environment Variables:** Use `getenv()` with fallback defaults

### Configuration Files
- **app.php:** Frontend URLs, API host, JWT settings
- **database.php:** Database credentials
- Use helper functions: `getFrontendUrl()`, `getApiHost()`

### Base Path Handling
- Extract base path from `api_host` config
- Set via `$app->setBasePath()` for subdirectory deployments
- Example: `http://localhost/QuranAudio` → base path `/QuranAudio`

## Testing Patterns

### Manual Testing
- Use Curl: Using curl to test the actual url endpoint
- - Health check: `GET /health` for database connectivity

### Test Data
- Sample data in `database/schema.sql`
- 5 reciters with different styles (Murattal, Mujawwad)
- Covers all 114 chapters (surahs)

## Error Handling

### Service Layer
- Return associative arrays with 'error' key for failures
- Return null for not found (single items)
- Return empty arrays for not found (collections)

### Controller Layer
- Check for 'error' key in service responses
- Map errors to appropriate HTTP status codes
- Always include error message in JSON response

### Database Errors
- Catch exceptions in DatabaseService
- Log errors for debugging
- Return generic error messages to clients (don't expose SQL)

## Security Best Practices

### Input Validation
- Cast route parameters: `(int)$args['id']`
- Validate required fields before database operations
- Use `filter_var()` for boolean query parameters

### SQL Injection Prevention
- Always use parameterized queries
- Never concatenate user input into SQL strings
- Use `?` placeholders with parameter arrays

### Password Security
- Hash passwords with `password_hash($password, PASSWORD_BCRYPT)`
- Verify with `password_verify($input, $hash)`
- Never store plain text passwords

### JWT Security
- Store secret in environment variable (not in code)
- Use strong secret key in production
- Validate token signature and expiry
- Include minimal data in payload (no sensitive info)

## Quranic Structure Reference

### Chapter (Surah)
- 114 chapters total
- Identified by `chapter_id` or `chapter_number` (1-114)

### Verse (Ayah)
- Identified by `verse_key` format: `{chapter}:{verse}` (e.g., "1:1")
- Variable count per chapter

### Juz
- 30 divisions of the Quran
- Identified by `juz_number` (1-30)

### Page
- Based on Madani Mushaf pagination
- Identified by `page_number` (1-604)

### Hizb
- 60 divisions (2 per juz)
- Identified by `hizb_number` (1-60)

### Rub el Hizb
- 240 divisions (4 per hizb)
- Identified by `rub_el_hizb_number` (1-240)

## Common Pitfalls to Avoid

1. **Don't mix business logic in controllers** - Keep controllers thin, move logic to services
2. **Don't forget to decode JSON fields** - Database returns JSON as strings
3. **Don't skip parameter casting** - Route args are strings, cast to int when needed
4. **Don't return raw database arrays** - Always instantiate Model objects
5. **Don't forget Content-Type header** - Always set `application/json` for API responses
6. **Don't expose database errors** - Return generic error messages to clients
7. **Don't hardcode URLs** - Use `getApiHost()` and `getFrontendUrl()` helpers
8. **Don't skip JWT validation** - Use middleware for all protected routes

## Development Workflow

1. **Define route** in appropriate file (`src/routes/*.php`)
2. **Create/update controller method** with proper type hints
3. **Implement service method** with database queries
4. **Test with Postman** or `test-api.php`
5. **Check health endpoint** to verify database connectivity
6. **Add authentication** if endpoint requires protection

## API Documentation Reference

Based on Quran Foundation API: https://api-docs.quran.foundation/