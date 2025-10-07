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

    public function create($data) {
        // Validate required fields
        if (empty($data['reciter_name']) || empty($data['style'])) {
            return ['error' => 'Reciter name and style are required'];
        }

        // Handle translated_name if it's an array (convert to JSON)
        if (isset($data['translated_name']) && is_array($data['translated_name'])) {
            $data['translated_name'] = json_encode($data['translated_name']);
        }

        // Create recitation in database
        $id = $this->db->create('recitations', $data);

        if (!$id) {
            return ['error' => 'Failed to create recitation'];
        }

        // Return the created recitation
        return $this->getById($id);
    }

    public function update($id, $data) {
        // Check if recitation exists
        $recitation = $this->getById($id);
        if (!$recitation) {
            return ['error' => 'Recitation not found'];
        }

        // Handle translated_name if it's an array (convert to JSON)
        if (isset($data['translated_name']) && is_array($data['translated_name'])) {
            $data['translated_name'] = json_encode($data['translated_name']);
        }

        // Update recitation in database
        $affected = $this->db->update('recitations', $data, ['id' => $id]);

        if ($affected === false) {
            return ['error' => 'Failed to update recitation'];
        }

        // Return the updated recitation
        return $this->getById($id);
    }

    public function delete($id) {
        // Check if recitation exists
        $recitation = $this->getById($id);
        if (!$recitation) {
            return ['error' => 'Recitation not found'];
        }

        // Delete recitation from database
        $affected = $this->db->delete('recitations', ['id' => $id]);

        if ($affected === false) {
            return ['error' => 'Failed to delete recitation'];
        }

        return ['success' => true, 'message' => 'Recitation deleted successfully'];
    }
}
