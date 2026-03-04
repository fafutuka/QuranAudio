<?php

namespace App\Services;

use App\Models\Mufasser;
use App\Models\Tafseer;

class MufasserService {
    private $db;

    public function __construct(DatabaseService $db) {
        $this->db = $db;
    }

    public function getAllMufassers(int $page = 1, int $perPage = 10, string $language = 'en'): array {
        try {
            $offset = ($page - 1) * $perPage;
            
            $query = "SELECT * FROM mufassers ORDER BY name LIMIT ? OFFSET ?";
            $mufassers = $this->db->runQuery($query, [$perPage, $offset]);

            $countQuery = "SELECT COUNT(*) as total FROM mufassers";
            $totalResult = $this->db->runQuery($countQuery);
            $totalRecords = $totalResult[0]['total'];

            $mufasserObjects = array_map(function($mufasser) {
                return new Mufasser($mufasser);
            }, $mufassers);

            return [
                'mufassers' => $mufasserObjects,
                'pagination' => [
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'next_page' => $page < ceil($totalRecords / $perPage) ? $page + 1 : null,
                    'total_pages' => (int)ceil($totalRecords / $perPage),
                    'total_records' => (int)$totalRecords
                ]
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch mufassers'];
        }
    }

    public function getMufasserById(int $id, string $language = 'en'): ?Mufasser {
        try {
            $mufasser = $this->db->readById('mufassers', $id);
            return $mufasser ? new Mufasser($mufasser) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getMufasserTafseers(int $mufasserId, string $language = 'en'): array {
        try {
            $query = "
                SELECT t.*, m.name as mufasser_name 
                FROM tafseers t 
                JOIN mufassers m ON t.mufasser_id = m.id 
                WHERE t.mufasser_id = ?
                ORDER BY t.year DESC
            ";
            
            $tafseers = $this->db->runQuery($query, [$mufasserId]);

            return array_map(function($tafseer) {
                // Decode JSON fields if they exist
                if (isset($tafseer['translated_name']) && is_string($tafseer['translated_name'])) {
                    $tafseer['translated_name'] = json_decode($tafseer['translated_name'], true);
                }
                return new Tafseer($tafseer);
            }, $tafseers);
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch mufasser tafseers'];
        }
    }

    public function createMufasser(array $data): array {
        try {
            $requiredFields = ['name'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['error' => "Missing required field: $field"];
                }
            }

            $id = $this->db->create('mufassers', $data);
            $mufasser = $this->db->readById('mufassers', $id);
            
            return new Mufasser($mufasser);
        } catch (\Exception $e) {
            return ['error' => 'Failed to create mufasser'];
        }
    }

    public function updateMufasser(int $id, array $data): ?Mufasser {
        try {
            $updated = $this->db->update('mufassers', $id, $data);
            if (!$updated) {
                return null;
            }

            $mufasser = $this->db->readById('mufassers', $id);
            return new Mufasser($mufasser);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function deleteMufasser(int $id): bool {
        try {
            return $this->db->delete('mufassers', $id);
        } catch (\Exception $e) {
            return false;
        }
    }
}