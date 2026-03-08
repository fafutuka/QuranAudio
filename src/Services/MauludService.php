<?php

namespace App\Services;

use App\Models\Maulud;
use App\Services\DatabaseService;

/**
 * MauludService
 * 
 * Handles business logic for Maulud (Prophet's Birthday celebration) audio recordings.
 * Provides CRUD operations and data retrieval with pagination and filtering.
 */
class MauludService
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    /**
     * Get all mauluds with optional pagination and filtering
     */
    public function getAllMauluds(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $perPage = min($params['per_page'] ?? 10, 100); // Max 100 per page
        $mufasserId = $params['mufasser_id'] ?? null;
        $year = $params['year'] ?? null;
        $search = $params['search'] ?? null;

        $offset = ($page - 1) * $perPage;

        // Build WHERE conditions
        $whereConditions = [];
        $whereParams = [];

        if ($mufasserId) {
            $whereConditions[] = "m.mufasser_id = ?";
            $whereParams[] = $mufasserId;
        }

        if ($year) {
            $whereConditions[] = "YEAR(m.gregorian_date) = ?";
            $whereParams[] = $year;
        }

        if ($search) {
            $whereConditions[] = "(m.title LIKE ? OR m.location LIKE ? OR muf.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $whereParams[] = $searchTerm;
            $whereParams[] = $searchTerm;
            $whereParams[] = $searchTerm;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $countQuery = "
            SELECT COUNT(*) as total 
            FROM mauluds m 
            LEFT JOIN mufassers muf ON m.mufasser_id = muf.id 
            {$whereClause}
        ";
        $totalResult = $this->db->runQuery($countQuery, $whereParams);
        $totalRecords = $totalResult[0]['total'] ?? 0;

        // Get paginated results
        $query = "
            SELECT 
                m.*,
                muf.name as mufasser_name,
                muf.arabic_name as mufasser_arabic_name
            FROM mauluds m
            LEFT JOIN mufassers muf ON m.mufasser_id = muf.id
            {$whereClause}
            ORDER BY m.gregorian_date DESC, m.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $queryParams = array_merge($whereParams, [$perPage, $offset]);
        $results = $this->db->runQuery($query, $queryParams);

        $mauluds = array_map(function ($row) {
            return new Maulud($row);
        }, $results);

        // Calculate pagination metadata
        $totalPages = ceil($totalRecords / $perPage);
        $nextPage = $page < $totalPages ? $page + 1 : null;

        return [
            'mauluds' => array_map(fn($maulud) => $maulud->toArray(), $mauluds),
            'pagination' => [
                'per_page' => $perPage,
                'current_page' => $page,
                'next_page' => $nextPage,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords
            ]
        ];
    }

    /**
     * Get maulud by ID
     */
    public function getMauludById(int $id): ?Maulud
    {
        $query = "
            SELECT 
                m.*,
                muf.name as mufasser_name,
                muf.arabic_name as mufasser_arabic_name
            FROM mauluds m
            LEFT JOIN mufassers muf ON m.mufasser_id = muf.id
            WHERE m.id = ?
        ";

        $result = $this->db->runQuery($query, [$id]);

        if (empty($result)) {
            return null;
        }

        return new Maulud($result[0]);
    }

    /**
     * Get mauluds by mufasser ID
     */
    public function getMauludsByMufasserId(int $mufasserId, array $params = []): array
    {
        $params['mufasser_id'] = $mufasserId;
        return $this->getAllMauluds($params);
    }

    /**
     * Create new maulud
     */
    public function createMaulud(array $data): array
    {
        // Validate required fields
        $requiredFields = ['mufasser_id', 'title', 'audio_url'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['error' => "Field '{$field}' is required"];
            }
        }

        // Validate mufasser exists
        if (!$this->mufasserExists($data['mufasser_id'])) {
            return ['error' => 'Mufasser not found'];
        }

        // Prepare data for insertion
        $insertData = [
            'mufasser_id' => $data['mufasser_id'],
            'title' => $data['title'],
            'islamic_date' => $data['islamic_date'] ?? null,
            'gregorian_date' => $data['gregorian_date'] ?? null,
            'location' => $data['location'] ?? null,
            'audio_url' => $data['audio_url'],
            'duration' => $data['duration'] ?? 0,
            'file_size' => $data['file_size'] ?? 0,
            'format' => $data['format'] ?? 'mp3',
            'cloudinary_public_id' => $data['cloudinary_public_id'] ?? null
        ];

        try {
            $mauludId = $this->db->create('mauluds', $insertData);
            return ['id' => $mauludId];
        } catch (\Exception $e) {
            error_log("Error creating maulud: " . $e->getMessage());
            return ['error' => 'Failed to create maulud'];
        }
    }

    /**
     * Update maulud
     */
    public function updateMaulud(int $id, array $data): array
    {
        // Check if maulud exists
        $existingMaulud = $this->getMauludById($id);
        if (!$existingMaulud) {
            return ['error' => 'Maulud not found'];
        }

        // Validate mufasser if provided
        if (isset($data['mufasser_id']) && !$this->mufasserExists($data['mufasser_id'])) {
            return ['error' => 'Mufasser not found'];
        }

        // Prepare update data (only include provided fields)
        $updateData = [];
        $allowedFields = [
            'mufasser_id', 'title', 'islamic_date', 'gregorian_date', 
            'location', 'audio_url', 'duration', 'file_size', 'format', 
            'cloudinary_public_id'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return ['error' => 'No valid fields provided for update'];
        }

        try {
            $this->db->update('mauluds', $updateData, ['id' => $id]);
            return ['success' => true];
        } catch (\Exception $e) {
            error_log("Error updating maulud: " . $e->getMessage());
            return ['error' => 'Failed to update maulud'];
        }
    }

    /**
     * Delete maulud
     */
    public function deleteMaulud(int $id): array
    {
        // Check if maulud exists
        $existingMaulud = $this->getMauludById($id);
        if (!$existingMaulud) {
            return ['error' => 'Maulud not found'];
        }

        try {
            $this->db->delete('mauluds', ['id' => $id]);
            return ['success' => true];
        } catch (\Exception $e) {
            error_log("Error deleting maulud: " . $e->getMessage());
            return ['error' => 'Failed to delete maulud'];
        }
    }

    /**
     * Get mauluds by year
     */
    public function getMauludsByYear(int $year, array $params = []): array
    {
        $params['year'] = $year;
        return $this->getAllMauluds($params);
    }

    /**
     * Search mauluds by title, location, or mufasser name
     */
    public function searchMauluds(string $query, array $params = []): array
    {
        $params['search'] = $query;
        return $this->getAllMauluds($params);
    }

    /**
     * Get maulud statistics
     */
    public function getMauludStatistics(): array
    {
        $queries = [
            'total_mauluds' => "SELECT COUNT(*) as count FROM mauluds",
            'total_duration' => "SELECT SUM(duration) as total FROM mauluds",
            'total_file_size' => "SELECT SUM(file_size) as total FROM mauluds",
            'mauluds_by_year' => "
                SELECT YEAR(gregorian_date) as year, COUNT(*) as count 
                FROM mauluds 
                WHERE gregorian_date IS NOT NULL 
                GROUP BY YEAR(gregorian_date) 
                ORDER BY year DESC
            ",
            'mauluds_by_mufasser' => "
                SELECT 
                    muf.name as mufasser_name,
                    COUNT(m.id) as count,
                    SUM(m.duration) as total_duration
                FROM mauluds m
                LEFT JOIN mufassers muf ON m.mufasser_id = muf.id
                GROUP BY m.mufasser_id, muf.name
                ORDER BY count DESC
            "
        ];

        $stats = [];

        foreach ($queries as $key => $query) {
            try {
                $result = $this->db->runQuery($query);
                $stats[$key] = $result;
            } catch (\Exception $e) {
                error_log("Error getting {$key} statistics: " . $e->getMessage());
                $stats[$key] = [];
            }
        }

        return $stats;
    }

    /**
     * Check if mufasser exists
     */
    private function mufasserExists(int $mufasserId): bool
    {
        $result = $this->db->runQuery("SELECT id FROM mufassers WHERE id = ?", [$mufasserId]);
        return !empty($result);
    }

    /**
     * Get recent mauluds
     */
    public function getRecentMauluds(int $limit = 5): array
    {
        $query = "
            SELECT 
                m.*,
                muf.name as mufasser_name,
                muf.arabic_name as mufasser_arabic_name
            FROM mauluds m
            LEFT JOIN mufassers muf ON m.mufasser_id = muf.id
            ORDER BY m.created_at DESC
            LIMIT ?
        ";

        $results = $this->db->runQuery($query, [$limit]);

        return array_map(function ($row) {
            return (new Maulud($row))->toArray();
        }, $results);
    }
}