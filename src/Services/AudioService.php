<?php

namespace App\Services;

use App\Models\AudioFile;
use App\Models\Timestamp;

class AudioService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getChapterAudio($reciterId, $chapterNumber, $segments = false) {
        // Fetch audio file for specific chapter and recitation
        $query = "SELECT af.* FROM audio_files af 
                  INNER JOIN recitations r ON af.recitation_id = r.id 
                  WHERE r.reciter_id = ? AND af.chapter_id = ? AND af.verse_number IS NULL 
                  LIMIT 1";
        
        $audioFiles = $this->db->runQuery($query, [$reciterId, $chapterNumber]);
        
        if (!$audioFiles || empty($audioFiles)) {
            return null;
        }
        
        $data = $audioFiles[0];
        $audio = new AudioFile($data);
        
        // Fetch timestamps for this audio file
        $timestampsData = $this->db->read('timestamps', ['audio_file_id' => $data['id']]);
        $timestamps = [];
        
        if ($timestampsData && !empty($timestampsData)) {
            foreach ($timestampsData as $t) {
                // Decode segments JSON field
                if (isset($t['segments']) && is_string($t['segments'])) {
                    $t['segments'] = json_decode($t['segments'], true);
                }
                
                if (!$segments) {
                    unset($t['segments']);
                }
                
                $timestamps[] = new Timestamp($t);
            }
        }
        
        $audio->timestamps = $timestamps;
        return $audio;
    }

    public function getReciterAudioFiles($reciterId, $language = 'en') {
        // Fetch all audio files for a reciter
        $query = "SELECT af.* FROM audio_files af 
                  INNER JOIN recitations r ON af.recitation_id = r.id 
                  WHERE r.reciter_id = ?";
        
        $files = $this->db->runQuery($query, [$reciterId]);
        
        if (!$files || empty($files)) {
            return [];
        }
        
        return array_map(function($data) {
            return new AudioFile($data);
        }, $files);
    }

    public function getRecitationAudioFiles($recitationId, $filters = [], $fields = []) {
        // Build query with filters
        $query = "SELECT af.* FROM audio_files af WHERE af.recitation_id = ?";
        $params = [$recitationId];
        
        // Apply chapter filter
        if (isset($filters['chapter_number'])) {
            $query .= " AND af.chapter_id = ?";
            $params[] = $filters['chapter_number'];
        }
        
        // Apply juz filter
        if (isset($filters['juz_number'])) {
            $query .= " AND af.juz_number = ?";
            $params[] = $filters['juz_number'];
        }
        
        // Apply page filter
        if (isset($filters['page_number'])) {
            $query .= " AND af.page_number = ?";
            $params[] = $filters['page_number'];
        }
        
        // Apply hizb filter
        if (isset($filters['hizb_number'])) {
            $query .= " AND af.hizb_number = ?";
            $params[] = $filters['hizb_number'];
        }
        
        // Apply rub el hizb filter
        if (isset($filters['rub_el_hizb_number'])) {
            $query .= " AND af.rub_el_hizb_number = ?";
            $params[] = $filters['rub_el_hizb_number'];
        }
        
        $files = $this->db->runQuery($query, $params);
        
        if (!$files || empty($files)) {
            $files = [];
        }
        
        // Get reciter name
        $recitation = $this->db->readById('recitations', $recitationId);
        $reciterName = $recitation ? $recitation['reciter_name'] : 'Unknown';
        
        return [
            'audio_files' => array_map(function($data) {
                return new AudioFile($data);
            }, $files),
            'meta' => ['reciter_name' => $reciterName]
        ];
    }

    public function getAyahRecitations($recitationId, $type, $identifier, $page = 1, $perPage = 10) {
        // Type: surah, juz, page, rub_el_hizb, hizb, ayah
        $query = "";
        $params = [];
        
        switch ($type) {
            case 'surah':
                // Get audio files for a specific chapter
                $query = "SELECT af.id, af.chapter_id, af.verse_key, af.audio_url, af.format, af.duration 
                          FROM audio_files af 
                          WHERE af.recitation_id = ? AND af.chapter_id = ? AND af.verse_key IS NOT NULL";
                $params = [$recitationId, $identifier];
                break;
                
            case 'juz':
                // Get audio files for a specific juz
                $query = "SELECT af.id, af.chapter_id, af.verse_key, af.audio_url, af.format, af.duration 
                          FROM audio_files af 
                          WHERE af.recitation_id = ? AND af.juz_number = ? AND af.verse_key IS NOT NULL";
                $params = [$recitationId, $identifier];
                break;
                
            case 'page':
                // Get audio files for a specific page
                $query = "SELECT af.id, af.chapter_id, af.verse_key, af.audio_url, af.format, af.duration 
                          FROM audio_files af 
                          WHERE af.recitation_id = ? AND af.page_number = ? AND af.verse_key IS NOT NULL";
                $params = [$recitationId, $identifier];
                break;
                
            case 'hizb':
                // Get audio files for a specific hizb
                $query = "SELECT af.id, af.chapter_id, af.verse_key, af.audio_url, af.format, af.duration 
                          FROM audio_files af 
                          WHERE af.recitation_id = ? AND af.hizb_number = ? AND af.verse_key IS NOT NULL";
                $params = [$recitationId, $identifier];
                break;
                
            case 'rub_el_hizb':
                // Get audio files for a specific rub el hizb
                $query = "SELECT af.id, af.chapter_id, af.verse_key, af.audio_url, af.format, af.duration 
                          FROM audio_files af 
                          WHERE af.recitation_id = ? AND af.rub_el_hizb_number = ? AND af.verse_key IS NOT NULL";
                $params = [$recitationId, $identifier];
                break;
                
            case 'ayah':
                // Get audio file for a specific ayah (verse_key like "1:1")
                $query = "SELECT af.id, af.chapter_id, af.verse_key, af.audio_url, af.format, af.duration 
                          FROM audio_files af 
                          WHERE af.recitation_id = ? AND af.verse_key = ?";
                $params = [$recitationId, $identifier];
                break;
        }
        
        // Get total count first
        $countQuery = str_replace("SELECT af.id, af.chapter_id, af.verse_key, af.audio_url, af.format, af.duration", "SELECT COUNT(*) as total", $query);
        $countResult = $this->db->runQuery($countQuery, $params);
        $total = $countResult && !empty($countResult) ? (int)$countResult[0]['total'] : 0;
        
        // Add pagination to query
        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $files = $this->db->runQuery($query, $params);
        
        if (!$files || empty($files)) {
            $files = [];
        }
        
        $pagination = [
            'per_page' => $perPage,
            'current_page' => $page,
            'next_page' => $page < ceil($total / $perPage) ? $page + 1 : null,
            'total_pages' => $total > 0 ? ceil($total / $perPage) : 0,
            'total_records' => $total
        ];
        
        return [
            'audio_files' => array_map(function($data) {
                return new AudioFile($data);
            }, $files),
            'pagination' => $pagination
        ];
    }
}
