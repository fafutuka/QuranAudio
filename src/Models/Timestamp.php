<?php

namespace App\Models;

class Timestamp {
    public $verse_key;
    public $timestamp_from;
    public $timestamp_to;
    public $duration;
    public $segments = [];

    public function __construct($data) {
        $this->verse_key = $data['verse_key'];
        $this->timestamp_from = $data['timestamp_from'];
        $this->timestamp_to = $data['timestamp_to'];
        $this->duration = $data['duration'];
        $this->segments = $data['segments'] ?? [];
    }
}
