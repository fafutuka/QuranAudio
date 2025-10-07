<?php

namespace App\Services;

use App\Models\Reciter;

class ReciterService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($language = 'en') {
        // Fetch all reciters from database
        $reciters = $this->db->read('reciters');
        
        if (!$reciters || empty($reciters)) {
            return [];
        }
        
        // Convert to Reciter objects
        return array_map(function($data) {
            return new Reciter($data);
        }, $reciters);
    }

    public function getById($id) {
        // Fetch reciter by ID from database
        $reciter = $this->db->readById('reciters', $id);

        if (!$reciter) {
            return null;
        }

        return new Reciter($reciter);
    }

    public function create($data) {
        // Validate required fields
        if (empty($data['name']) || empty($data['arabic_name'])) {
            return ['error' => 'Name and Arabic name are required'];
        }

        // Create reciter in database
        $id = $this->db->create('reciters', $data);

        if (!$id) {
            return ['error' => 'Failed to create reciter'];
        }

        // Return the created reciter
        return $this->getById($id);
    }

    public function update($id, $data) {
        // Check if reciter exists
        $reciter = $this->getById($id);
        if (!$reciter) {
            return ['error' => 'Reciter not found'];
        }

        // Update reciter in database
        $affected = $this->db->update('reciters', $data, ['id' => $id]);

        if ($affected === false) {
            return ['error' => 'Failed to update reciter'];
        }

        // Return the updated reciter
        return $this->getById($id);
    }

    public function delete($id) {
        // Check if reciter exists
        $reciter = $this->getById($id);
        if (!$reciter) {
            return ['error' => 'Reciter not found'];
        }

        // Delete reciter from database
        $affected = $this->db->delete('reciters', ['id' => $id]);

        if ($affected === false) {
            return ['error' => 'Failed to delete reciter'];
        }

        return ['success' => true, 'message' => 'Reciter deleted successfully'];
    }
}
