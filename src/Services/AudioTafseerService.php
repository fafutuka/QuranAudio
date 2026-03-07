<?php

namespace App\Services;

use App\Models\AudioTafseer;

class AudioTafseerService {
    private $db;

    public function __construct(DatabaseService $db) {
        $this->db = $db;
    }

    public function getAudioTafseerById(int $id, bool $includeSegments = false): ?AudioTafseer {
        try {
            $query = "
                SELECT at.*, 
                       m.name as mufasser_name, 
                       m.arabic_name as mufasser_arabic_name,
                       m.biography as mufasser_biography,
                       m.birth_year as mufasser_birth_year,
                       m.death_year as mufasser_death_year,
                       m.avatar_url as mufasser_avatar_url,
                       m.background_url as mufasser_background_url
                FROM audio_tafseers at 
                JOIN mufassers m ON at.mufasser_id = m.id
                WHERE at.id = ?
            ";
            
            $result = $this->db->runQuery($query, [$id]);
            
            if (empty($result)) {
                return null;
            }

            $audioTafseer = new AudioTafseer($result[0]);
            
            if ($includeSegments) {
                $this->attachTimestamps($audioTafseer, true);
            }

            return $audioTafseer;
        } catch (\Exception $e) {
            error_log("AudioTafseerService::getAudioTafseerById Error: " . $e->getMessage());
            return null;
        }
    }

    public function getAudioTafseerByVerseRange(string $verseFrom, string $verseTo, array $mufasserIds = [], bool $includeSegments = false, int $page = 1, int $perPage = 10): array {
        try {
            $offset = ($page - 1) * $perPage;
            
            $whereClause = "WHERE (
                (at.verse_range_from <= ? AND at.verse_range_to >= ?) OR
                (at.verse_range_from <= ? AND at.verse_range_to >= ?) OR
                (at.verse_range_from >= ? AND at.verse_range_to <= ?)
            )";
            
            $params = [$verseFrom, $verseFrom, $verseTo, $verseTo, $verseFrom, $verseTo];
            
            if (!empty($mufasserIds)) {
                $placeholders = str_repeat('?,', count($mufasserIds) - 1) . '?';
                $whereClause .= " AND at.mufasser_id IN ($placeholders)";
                $params = array_merge($params, $mufasserIds);
            }

            $query = "
                SELECT at.*, 
                       m.name as mufasser_name, 
                       m.arabic_name as mufasser_arabic_name,
                       m.biography as mufasser_biography,
                       m.birth_year as mufasser_birth_year,
                       m.death_year as mufasser_death_year,
                       m.avatar_url as mufasser_avatar_url,
                       m.background_url as mufasser_background_url
                FROM audio_tafseers at 
                JOIN mufassers m ON at.mufasser_id = m.id
                $whereClause
                ORDER BY at.verse_range_from 
                LIMIT ? OFFSET ?
            ";
            
            $queryParams = array_merge($params, [$perPage, $offset]);
            $audioFiles = $this->db->runQuery($query, $queryParams);

            // Count query for pagination
            $countQuery = "
                SELECT COUNT(*) as total 
                FROM audio_tafseers at 
                $whereClause
            ";
            $totalResult = $this->db->runQuery($countQuery, $params);
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
            error_log("AudioTafseerService::getAudioTafseerByVerseRange Error: " . $e->getMessage());
            return ['error' => 'Failed to fetch audio tafseer by verse range: ' . $e->getMessage()];
        }
    }

    public function getAudioTafseerByChapter(int $chapterNumber, array $mufasserIds = [], bool $includeSegments = false, int $page = 1, int $perPage = 10): array {
        try {
            $offset = ($page - 1) * $perPage;
            
            // Build WHERE clause to find all audio tafseers for the chapter
            $whereClause = "WHERE (
                CAST(SUBSTRING_INDEX(at.verse_range_from, ':', 1) AS UNSIGNED) = ? OR
                CAST(SUBSTRING_INDEX(at.verse_range_to, ':', 1) AS UNSIGNED) = ?
            )";
            
            $params = [$chapterNumber, $chapterNumber];
            
            if (!empty($mufasserIds)) {
                $placeholders = str_repeat('?,', count($mufasserIds) - 1) . '?';
                $whereClause .= " AND at.mufasser_id IN ($placeholders)";
                $params = array_merge($params, $mufasserIds);
            }

            $query = "
                SELECT at.*, 
                       m.name as mufasser_name, 
                       m.arabic_name as mufasser_arabic_name,
                       m.biography as mufasser_biography,
                       m.birth_year as mufasser_birth_year,
                       m.death_year as mufasser_death_year,
                       m.avatar_url as mufasser_avatar_url,
                       m.background_url as mufasser_background_url
                FROM audio_tafseers at 
                JOIN mufassers m ON at.mufasser_id = m.id
                $whereClause
                ORDER BY at.mufasser_id, 
                         CAST(SUBSTRING_INDEX(at.verse_range_from, ':', 1) AS UNSIGNED),
                         CAST(SUBSTRING_INDEX(at.verse_range_from, ':', -1) AS UNSIGNED)
                LIMIT ? OFFSET ?
            ";
            
            $queryParams = array_merge($params, [$perPage, $offset]);
            $audioFiles = $this->db->runQuery($query, $queryParams);

            // Count query for pagination
            $countQuery = "
                SELECT COUNT(*) as total 
                FROM audio_tafseers at 
                $whereClause
            ";
            $totalResult = $this->db->runQuery($countQuery, $params);
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
                ],
                'meta' => [
                    'chapter_number' => $chapterNumber
                ]
            ];
        } catch (\Exception $e) {
            error_log("AudioTafseerService::getAudioTafseerByChapter Error: " . $e->getMessage());
            return ['error' => 'Failed to fetch audio tafseer by chapter: ' . $e->getMessage()];
        }
    }

    public function createAudioTafseer(array $data): array {
        try {
            $requiredFields = ['mufasser_id', 'audio_url', 'verse_range_from', 'verse_range_to'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['error' => "Missing required field: $field"];
                }
            }

            // Validate verse range format
            if (!$this->isValidVerseKey($data['verse_range_from']) || !$this->isValidVerseKey($data['verse_range_to'])) {
                return ['error' => 'Invalid verse range format. Use format: chapter:verse (e.g., 1:1)'];
            }

            // Encode JSON fields if they exist
            if (isset($data['segments']) && is_array($data['segments'])) {
                $data['segments'] = json_encode($data['segments']);
            }

            $id = $this->db->create('audio_tafseers', $data);
            $audioTafseer = $this->getAudioTafseerById($id);
            
            if ($audioTafseer) {
                return ['audio_tafseer' => $audioTafseer];
            } else {
                return ['error' => 'Failed to retrieve created audio tafseer'];
            }
        } catch (\Exception $e) {
            return ['error' => 'Failed to create audio tafseer'];
        }
    }

    public function updateAudioTafseer(int $id, array $data): ?AudioTafseer {
        try {
            // Validate verse range format if provided
            if (isset($data['verse_range_from']) && !$this->isValidVerseKey($data['verse_range_from'])) {
                return ['error' => 'Invalid verse_range_from format. Use format: chapter:verse (e.g., 1:1)'];
            }
            
            if (isset($data['verse_range_to']) && !$this->isValidVerseKey($data['verse_range_to'])) {
                return ['error' => 'Invalid verse_range_to format. Use format: chapter:verse (e.g., 1:1)'];
            }

            // Encode JSON fields if they exist
            if (isset($data['segments']) && is_array($data['segments'])) {
                $data['segments'] = json_encode($data['segments']);
            }

            $updated = $this->db->update('audio_tafseers', $id, $data);
            if (!$updated) {
                return null;
            }

            return $this->getAudioTafseerById($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function deleteAudioTafseer(int $id): bool {
        try {
            return $this->db->delete('audio_tafseers', $id);
        } catch (\Exception $e) {
            return false;
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

    private function isValidVerseKey(string $verseKey): bool {
        return preg_match('/^\d+:\d+$/', $verseKey) === 1;
    }
}