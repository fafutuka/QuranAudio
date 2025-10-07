<?php

namespace App\Services;

use App\Models\Recitation;

class RecitationService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($language = 'en') {
        // Fetch all recitations from database
        $recitations = $this->db->read('recitations');
        
        if (!$recitations || empty($recitations)) {
            return [];
        }
        
        // Convert to Recitation objects and decode JSON fields
        return array_map(function($data) {
            // Decode translated_name JSON field
            if (isset($data['translated_name']) && is_string($data['translated_name'])) {
                $data['translated_name'] = json_decode($data['translated_name'], true);
            }
            return new Recitation($data);
        }, $recitations);
    }

    public function getById($id) {
        // Fetch recitation by ID from database
        $recitation = $this->db->readById('recitations', $id);
        
        if (!$recitation) {
            return null;
        }
        
        // Decode translated_name JSON field
        if (isset($recitation['translated_name']) && is_string($recitation['translated_name'])) {
            $recitation['translated_name'] = json_decode($recitation['translated_name'], true);
        }
        
        return new Recitation($recitation);
    }
}
