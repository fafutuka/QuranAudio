<?php

namespace App\Services;

use App\Models\Tafseer;
use App\Models\AudioTafseer;

class TafseerService {
    private $db;

    public function __construct(DatabaseService $db) {
        $this->db = $db;
    }

    public function getAllTafseers(int $page = 1, int $perPage = 10, string $language = 'en'): array {
        try {
            $offset = ($page - 1) * $perPage;
            
            $query = "
                SELECT t.*, m.name as mufasser_name 
                FROM tafseers t 
                JOIN mufassers m ON t.mufasser_id = m.id 
                ORDER BY t.year DESC 
                LIMIT ? OFFSET ?
            ";
            
            $tafseers = $this->db->runQuery($query, [$perPage, $offset]);

            $countQuery = "SELECT COUNT(*) as total FROM tafseers";
            $totalResult = $this->db->runQuery($countQuery);
            $totalRecords = $totalResult[0]['total'];

            $tafseerObjects = array_map(function($tafseer) {
                // Decode JSON fields if they exist
                if (isset($tafseer['translated_name']) && is_string($tafseer['translated_name'])) {
                    $tafseer['translated_name'] = json_decode($tafseer['translated_name'], true);
                }
                return new Tafseer($tafseer);
            }, $tafseers);

            return [
                'tafseers' => $tafseerObjects,
                'pagination' => [
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'next_page' => $page < ceil($totalRecords / $perPage) ? $page + 1 : null,
                    'total_pages' => (int)ceil($totalRecords / $perPage),
                    'total_records' => (int)$totalRecords
                ]
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch tafseers'];
        }
    }

    public function getTafseerById(int $id, string $language = 'en'): ?Tafseer {
        try {
            $query = "
                SELECT t.*, m.name as mufasser_name 
                FROM tafseers t 
                JOIN mufassers m ON t.mufasser_id = m.id 
                WHERE t.id = ?
            ";
            
            $result = $this->db->runQuery($query, [$id]);
            
            if (empty($result)) {
                return null;
            }

            $tafseer = $result[0];
            
            // Decode JSON fields if they exist
            if (isset($tafseer['translated_name']) && is_string($tafseer['translated_name'])) {
                $tafseer['translated_name'] = json_decode($tafseer['translated_name'], true);
            }

            return new Tafseer($tafseer);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getTafseerAudioFiles(int $tafseerId, int $page = 1, int $perPage = 10, bool $includeSegments = false): array {
        try {
            $offset = ($page - 1) * $perPage;
            
            $query = "
                SELECT at.*, t.mufasser_name, t.year 
                FROM audio_tafseers at 
                JOIN (
                    SELECT t.*, m.name as mufasser_name 
                    FROM tafseers t 
                    JOIN mufassers m ON t.mufasser_id = m.id
                ) t ON at.tafseer_id = t.id 
                WHERE at.tafseer_id = ? 
                ORDER BY at.verse_range_from 
                LIMIT ? OFFSET ?
            ";
            
            $audioFiles = $this->db->runQuery($query, [$tafseerId, $perPage, $offset]);

            $countQuery = "SELECT COUNT(*) as total FROM audio_tafseers WHERE tafseer_id = ?";
            $totalResult = $this->db->runQuery($countQuery, [$tafseerId]);
            $totalRecords = $totalResult[0]['total'];

            $audioTafseerObjects = array_map(function($audioFile) use ($includeSegments) {
                $audioTafseer = new AudioTafseer($audioFile);
                
                if ($includeSegments) {
                    $this->attachTimestamps($audioTafseer, true);
                }
                
                return $audioTafseer;
            }, $audioFiles);

            return [
                'audio_tafseers' => $audioTafseerObjects,
                'pagination' => [
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'next_page' => $page < ceil($totalRecords / $perPage) ? $page + 1 : null,
                    'total_pages' => (int)ceil($totalRecords / $perPage),
                    'total_records' => (int)$totalRecords
                ]
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch tafseer audio files'];
        }
    }

    public function getTafseerByVerseRange(string $verseFrom, string $verseTo, array $tafseerIds = [], bool $includeSegments = false): array {
        try {
            $whereClause = "WHERE (
                (at.verse_range_from <= ? AND at.verse_range_to >= ?) OR
                (at.verse_range_from <= ? AND at.verse_range_to >= ?) OR
                (at.verse_range_from >= ? AND at.verse_range_to <= ?)
            )";
            
            $params = [$verseFrom, $verseFrom, $verseTo, $verseTo, $verseFrom, $verseTo];
            
            if (!empty($tafseerIds)) {
                $placeholders = str_repeat('?,', count($tafseerIds) - 1) . '?';
                $whereClause .= " AND at.tafseer_id IN ($placeholders)";
                $params = array_merge($params, $tafseerIds);
            }

            $query = "
                SELECT at.*, t.mufasser_name, t.year 
                FROM audio_tafseers at 
                JOIN (
                    SELECT t.*, m.name as mufasser_name 
                    FROM tafseers t 
                    JOIN mufassers m ON t.mufasser_id = m.id
                ) t ON at.tafseer_id = t.id 
                $whereClause
                ORDER BY at.verse_range_from
            ";
            
            $audioFiles = $this->db->runQuery($query, $params);

            $audioTafseerObjects = array_map(function($audioFile) use ($includeSegments) {
                $audioTafseer = new AudioTafseer($audioFile);
                
                if ($includeSegments) {
                    $this->attachTimestamps($audioTafseer, true);
                }
                
                return $audioTafseer;
            }, $audioFiles);

            return ['audio_tafseers' => $audioTafseerObjects];
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch tafseer by verse range'];
        }
    }

    private function attachTimestamps(AudioTafseer $audioTafseer, bool $includeSegments = false): void {
        try {
            $query = "SELECT * FROM tafseer_timestamps WHERE audio_tafseer_id = ? ORDER BY timestamp_ms";
            $timestamps = $this->db->runQuery($query, [$audioTafseer->id]);

            foreach ($timestamps as &$timestamp) {
                if ($includeSegments && isset($timestamp['segments']) && is_string($timestamp['segments'])) {
                    $timestamp['segments'] = json_decode($timestamp['segments'], true);
                }
            }

            $audioTafseer->timestamps = $timestamps;
        } catch (\Exception $e) {
            $audioTafseer->timestamps = [];
        }
    }

    public function createTafseer(array $data): array {
        try {
            $requiredFields = ['mufasser_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['error' => "Missing required field: $field"];
                }
            }

            // Encode JSON fields
            if (isset($data['translated_name']) && is_array($data['translated_name'])) {
                $data['translated_name'] = json_encode($data['translated_name']);
            }

            $id = $this->db->create('tafseers', $data);
            return $this->getTafseerById($id);
        } catch (\Exception $e) {
            return ['error' => 'Failed to create tafseer'];
        }
    }

    public function updateTafseer(int $id, array $data): ?Tafseer {
        try {
            // Encode JSON fields
            if (isset($data['translated_name']) && is_array($data['translated_name'])) {
                $data['translated_name'] = json_encode($data['translated_name']);
            }

            $updated = $this->db->update('tafseers', $id, $data);
            if (!$updated) {
                return null;
            }

            return $this->getTafseerById($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function deleteTafseer(int $id): bool {
        try {
            return $this->db->delete('tafseers', $id);
        } catch (\Exception $e) {
            return false;
        }
    }
}