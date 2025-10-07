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
}
