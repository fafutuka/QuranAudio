---
trigger: always_on
---

# Quran Audio API - Architecture & Coding Standards

This document provides a comprehensive overview of the project's technical architecture, design patterns, and coding standards.

## Architecture Patterns

The project follows a **Layered Architecture** pattern, providing a clear separation of concerns:

```mermaid
graph TD
    Client[Client/Postman] <--> Routes[Routes (src/routes/)]
    Routes <--> Controllers[Controllers (src/Controllers/)]
    Controllers <--> Services[Services (src/Services/)]
    Services <--> Models[Models (src/Models/)]
    Services <--> DB[DatabaseService]
    DB <--> MySQL[(MySQL Database)]
```

### 1. Controllers (`src/Controllers/`)

- **Responsibility**: Handle incoming HTTP requests and return JSON responses.
- **Pattern**: Thin controllers that delegate business logic to services.
- **Dependency**: Inject services via the constructor.

### 2. Services (`src/Services/`)

- **Responsibility**: Implement core business logic and data orchestration.
- **Pattern**: Service classes that interact with the database wrapper.
- **State**: Typically stateless, managed as singletons by the DI container.

### 3. Models (`src/Models/`)

- **Responsibility**: Define the data structure for entities (DTOs).
- **Pattern**: Simple classes with public properties and a constructor for data mapping.

### 4. Database Layer (`src/Services/DatabaseService.php`)

- **Responsibility**: Abstract database operations using `mysqli`.
- **Pattern**: Custom DB wrapper implementing basic CRUD operations.
- **Security**: Mandatory use of **Prepared Statements** to prevent SQL injection.

---

## Technical Stack & Design Patterns

| Category                 | Technology/Pattern                                    |
| :----------------------- | :---------------------------------------------------- |
| **Framework**            | [Slim Framework 4](https://www.slimframework.com/)    |
| **Dependency Injection** | [PHP-DI](https://php-di.org/) (Constructor Injection) |
| **Autoloading**          | PSR-4 (configured in `composer.json`)                 |
| **Database Driver**      | MySQLi (wrapped in `DatabaseService`)                 |
| **Routing**              | Modular route definitions loaded in `index.php`       |

### Dependency Injection Pattern

The project uses a Centralized Container (`DI\Container`) to manage dependencies. Most classes are automatically instantiated and injected by PHP-DI.

```php
// Example from index.php
$container->set('App\Controllers\ReciterController', function($container) {
    return new App\Controllers\ReciterController($container->get('App\Services\ReciterService'));
});
```

---

## Coding Standards & Style

### General Rules

- **PHP Version**: 8.2+
- **Namespace**: Starts with `App\` (e.g., `App\Controllers`, `App\Services`, `App\Models`).
- **File Structure**: One class per file, matching the class name.

### Naming Conventions

| Item          | Convention | Example                                |
| :------------ | :--------- | :------------------------------------- |
| **Classes**   | PascalCase | `ReciterController`, `DatabaseService` |
| **Methods**   | camelCase  | `getAll()`, `readById()`               |
| **Variables** | camelCase  | `$reciterId`, `$dbConfig`              |
| **JSON Keys** | snake_case | `"arabic_name"`, `"relative_path"`     |

### Error Handling

- Uses Slim's `addErrorMiddleware(true, true, true)` for global error catching.
- Services return error arrays (e.g., `['error' => 'Message']`) which controllers translate to appropriate HTTP status codes (400, 404, etc.).

### API response Style

- All API endpoints return JSON.
- Standard success structure: `{"key": data}` (e.g., `{"reciters": [...]}`).
- Error structure: `{"error": "Description"}`.

### Security Best Practices

- All database operations MUST use prepared statements (enforced by DatabaseService)
- JWT tokens required for protected endpoints (via JwtMiddleware)
- Input sanitization through DatabaseService::sanitize() method
- Role-based access control via RoleMiddleware
- Environment-based configuration (production vs development)

### Testing API

Use curl to test API endpoints:

```bash
# Test health endpoint
curl http://localhost:8080/health

# List all reciters
curl http://localhost:8080/chapter-reciters?language=en

# Get specific reciter
curl http://localhost:8080/chapter-reciters/1

# Create new reciter (requires authentication)
curl -X POST http://localhost:8080/chapter-reciters \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"name":"New Reciter","arabic_name":"قارئ جديد"}'
```

---

## Detailed Design Patterns & Guidelines

### 1. Dependency Injection Pattern

All dependencies are managed through PHP-DI Container with explicit constructor injection:

```php
// Service with database dependency
class ReciterService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
}

// Controller with service dependency
class ReciterController {
    private $service;
    
    public function __construct(ReciterService $service) {
        $this->service = $service;
    }
}

// Container configuration in index.php
$container->set('App\Services\ReciterService', function ($container) {
    return new App\Services\ReciterService($container->get('DatabaseService'));
});
```

### 2. Repository Pattern (via DatabaseService)

DatabaseService acts as a data access layer with standardized CRUD operations:

```php
// Create
$id = $this->db->create('table_name', ['field' => 'value']);

// Read all
$records = $this->db->read('table_name');

// Read with conditions (array)
$records = $this->db->read('table_name', ['status' => 'active']);

// Read by ID
$record = $this->db->readById('table_name', $id);

// Update
$affected = $this->db->update('table_name', ['field' => 'new_value'], ['id' => $id]);

// Delete
$affected = $this->db->delete('table_name', ['id' => $id]);

// Custom query with prepared statements
$results = $this->db->runQuery('SELECT * FROM table WHERE field = ?', [$value]);
```

### 3. Data Transfer Object (DTO) Pattern

Models serve as DTOs with public properties and constructor mapping:

```php
class Reciter {
    public $id;
    public $name;
    public $arabic_name;
    public $relative_path;
    public $format = 'mp3';
    public $files_size;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->arabic_name = $data['arabic_name'] ?? null;
        $this->relative_path = $data['relative_path'] ?? '';
        $this->format = $data['format'] ?? 'mp3';
        $this->files_size = $data['files_size'] ?? 0;
    }
}
```

### 4. Middleware Pattern

Middleware for cross-cutting concerns (authentication, authorization):

```php
class JwtMiddleware {
    private AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function __invoke(Request $request, Handler $handler): Response {
        // Validate JWT token
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            return $this->unauthorizedResponse();
        }
        
        // Add user to request attributes
        $request = $request->withAttribute('user', $decoded);
        
        return $handler->handle($request);
    }
}
```

### 5. Service Layer Pattern

Services encapsulate business logic and coordinate between controllers and data layer:

```php
class ReciterService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($language = 'en') {
        $reciters = $this->db->read('reciters');
        
        if (!$reciters || empty($reciters)) {
            return [];
        }
        
        return array_map(fn($data) => new Reciter($data), $reciters);
    }

    public function create($data) {
        // Validation
        if (empty($data['name']) || empty($data['arabic_name'])) {
            return ['error' => 'Name and Arabic name are required'];
        }

        // Business logic
        $id = $this->db->create('reciters', $data);

        if (!$id) {
            return ['error' => 'Failed to create reciter'];
        }

        return $this->getById($id);
    }
}
```

---

## Code Organization Guidelines

### File Structure Rules

```
src/
├── Controllers/          # HTTP request handlers (thin layer)
│   ├── AudioController.php
│   ├── AuthController.php
│   ├── RecitationController.php
│   └── ReciterController.php
├── Services/            # Business logic layer
│   ├── AudioService.php
│   ├── AuthService.php
│   ├── DatabaseService.php
│   ├── RecitationService.php
│   └── ReciterService.php
├── Models/              # Data Transfer Objects
│   ├── AudioFile.php
│   ├── Recitation.php
│   ├── Reciter.php
│   ├── Timestamp.php
│   └── User.php
├── Middleware/          # Request/Response interceptors
│   ├── JwtMiddleware.php
│   └── RoleMiddleware.php
├── routes/              # Route definitions
│   ├── audio.php
│   ├── auth.php
│   ├── recitation.php
│   └── reciter.php
└── config/              # Configuration files
    ├── app.php
    └── database.php
```

### Controller Guidelines

Controllers should be thin and delegate to services:

```php
class ReciterController {
    private $service;

    public function __construct(ReciterService $service) {
        $this->service = $service;
    }

    public function getAll(Request $request, Response $response): Response {
        // Extract parameters
        $language = $request->getQueryParams()['language'] ?? 'en';
        
        // Delegate to service
        $reciters = $this->service->getAll($language);
        
        // Return JSON response
        $response->getBody()->write(json_encode(['reciters' => $reciters]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->service->create($data);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $response->getBody()->write(json_encode(['reciter' => $result]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
```

### Service Guidelines

Services contain business logic and validation:

```php
class ReciterService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Return data or empty array (never null for collections)
    public function getAll($language = 'en') {
        $reciters = $this->db->read('reciters');
        
        if (!$reciters || empty($reciters)) {
            return [];
        }
        
        return array_map(fn($data) => new Reciter($data), $reciters);
    }

    // Return data or null for single items
    public function getById($id) {
        $reciter = $this->db->readById('reciters', $id);

        if (!$reciter) {
            return null;
        }

        return new Reciter($reciter);
    }

    // Return data or error array
    public function create($data) {
        if (empty($data['name'])) {
            return ['error' => 'Name is required'];
        }

        $id = $this->db->create('reciters', $data);

        if (!$id) {
            return ['error' => 'Failed to create reciter'];
        }

        return $this->getById($id);
    }
}
```

### Model Guidelines

Models are simple DTOs with public properties:

```php
class Reciter {
    public $id;
    public $name;
    public $arabic_name;
    public $relative_path;
    public $format = 'mp3';
    public $files_size;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->arabic_name = $data['arabic_name'] ?? null;
        $this->relative_path = $data['relative_path'] ?? '';
        $this->format = $data['format'] ?? 'mp3';
        $this->files_size = $data['files_size'] ?? 0;
    }
}
```

---

## HTTP Response Standards

### Success Responses

```php
// Single resource
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(200);
// Body: {"reciter": {...}}

// Collection
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(200);
// Body: {"reciters": [...]}

// Created resource
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(201);
// Body: {"reciter": {...}}

// Paginated collection
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(200);
// Body: {"audio_files": [...], "pagination": {...}}
```

### Error Responses

```php
// Bad request (validation error)
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(400);
// Body: {"error": "Name is required"}

// Unauthorized
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(401);
// Body: {"error": "Unauthorized: Token missing"}

// Forbidden
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(403);
// Body: {"error": "Forbidden: Admin access required"}

// Not found
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(404);
// Body: {"error": "Reciter not found"}

// Internal server error
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(500);
// Body: {"error": "Internal server error"}
```

---

## Database Security Guidelines

### Always Use Prepared Statements

```php
// CORRECT: Using prepared statements
$results = $this->db->read('reciters', ['status' => 'active']);
$reciter = $this->db->readById('reciters', $id);
$id = $this->db->create('reciters', $data);
$affected = $this->db->update('reciters', $data, ['id' => $id]);

// CORRECT: Custom query with parameters
$results = $this->db->runQuery(
    'SELECT * FROM reciters WHERE name LIKE ?',
    ['%' . $searchTerm . '%']
);

// AVOID: Direct string concatenation (only for complex queries)
// If absolutely necessary, use escapeString()
$escaped = $this->db->escapeString($userInput);
```

### Input Sanitization

```php
// Sanitize user input before processing
$sanitized = $this->db->sanitize($userInput);

// For arrays
$sanitizedData = $this->db->sanitize($_POST);
```

---

## Configuration Management

### Environment Detection

```php
// database.php
$isProduction = isset($_SERVER['HTTP_HOST']) && 
                strpos($_SERVER['HTTP_HOST'], 'production-domain.com') !== false;

if ($isProduction) {
    return [
        'host' => 'prod-host',
        'user' => 'prod-user',
        'password' => 'prod-password',
        'database' => 'prod-database'
    ];
} else {
    return [
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'database' => 'local-database'
    ];
}
```

### Application Configuration

```php
// app.php
return [
    'api_host' => 'http://localhost:8080',
    'jwt_secret' => 'your-secret-key',
    'jwt_expiry' => 3600, // 1 hour
];
```

---

## Route Definition Standards

Routes should be organized in separate files by resource:

```php
// src/routes/reciter.php
return function ($app) {
    $app->get('/chapter-reciters', 'App\Controllers\ReciterController:getAll');
    $app->get('/chapter-reciters/{id}', 'App\Controllers\ReciterController:getById');
    $app->post('/chapter-reciters', 'App\Controllers\ReciterController:create')
        ->add($app->getContainer()->get('App\Middleware\JwtMiddleware'));
    $app->put('/chapter-reciters/{id}', 'App\Controllers\ReciterController:update')
        ->add($app->getContainer()->get('App\Middleware\JwtMiddleware'));
    $app->delete('/chapter-reciters/{id}', 'App\Controllers\ReciterController:delete')
        ->add($app->getContainer()->get('App\Middleware\JwtMiddleware'));
};
```

---

## Development Workflow

### 1. Adding a New Feature

1. Create Model (if needed) in `src/Models/`
2. Create Service in `src/Services/` with business logic
3. Create Controller in `src/Controllers/` for HTTP handling
4. Define routes in `src/routes/`
5. Register dependencies in `public/index.php`
6. Test with curl or Postman

### 2. Database Changes

1. Update schema in `database/schema.sql`
2. Run migration on database
3. Update corresponding Model class
4. Update Service methods if needed

### 3. Adding Authentication

1. Add JwtMiddleware to route
2. Access user data via `$request->getAttribute('user')`
3. Implement role checks if needed

---

## Common Patterns & Examples

### Pagination Pattern

```php
// In Service
public function getPaginated($page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    
    $query = "SELECT * FROM table LIMIT ? OFFSET ?";
    $results = $this->db->runQuery($query, [$perPage, $offset]);
    
    $countQuery = "SELECT COUNT(*) as total FROM table";
    $countResult = $this->db->runQuery($countQuery);
    $total = $countResult[0]['total'];
    
    return [
        'data' => $results,
        'pagination' => [
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage),
            'total_records' => $total
        ]
    ];
}
```

### Filtering Pattern

```php
// In Service
public function getFiltered($filters) {
    $conditions = [];
    $params = [];
    
    if (!empty($filters['chapter_number'])) {
        $conditions[] = 'chapter_id = ?';
        $params[] = $filters['chapter_number'];
    }
    
    if (!empty($filters['reciter_id'])) {
        $conditions[] = 'reciter_id = ?';
        $params[] = $filters['reciter_id'];
    }
    
    $query = "SELECT * FROM audio_files";
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    
    return $this->db->runQuery($query, $params);
}
```

### Field Selection Pattern

```php
// In Controller
public function getWithFields(Request $request, Response $response): Response {
    $fields = $request->getQueryParams()['fields'] ?? null;
    $data = $this->service->getAll();
    
    if ($fields) {
        $allowedFields = explode(',', $fields);
        $data = array_map(function($item) use ($allowedFields) {
            return array_intersect_key(
                (array)$item,
                array_flip($allowedFields)
            );
        }, $data);
    }
    
    $response->getBody()->write(json_encode(['data' => $data]));
    return $response->withHeader('Content-Type', 'application/json');
}
```

---

## Performance Best Practices

1. Use prepared statements for all database queries
2. Implement pagination for large datasets
3. Use field selection to reduce payload size
4. Cache frequently accessed data (future enhancement)
5. Use database indexes on frequently queried columns
6. Avoid N+1 queries by using JOINs when appropriate

---

## Maintenance Guidelines

1. Keep controllers thin - move logic to services
2. Use type hints for better IDE support and error detection
3. Document complex business logic with comments
4. Log errors for debugging (use error_log())
5. Keep dependencies up to date via composer
6. Follow PSR-4 autoloading standards
7. Use meaningful variable and method names
8. Write self-documenting code when possible
