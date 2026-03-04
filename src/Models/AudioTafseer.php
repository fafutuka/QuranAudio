<?php

namespace App\Models;

class AudioTafseer {
    public $id;
    public $tafseer_id;
    public $file_size;
    public $format;
    public $audio_url;
    public $url;
    public $duration;
    public $verse_range_from;
    public $verse_range_to;
    public $chapter_start;
    public $chapter_end;
    public $verse_start;
    public $verse_end;
    public $segments = [];
    public $timestamps = [];
    
    // Cloudinary fields
    public $cloudinary_public_id;
    public $audio_format;
    public $quality_urls = [];

    public function __construct($data) {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->tafseer_id = isset($data['tafseer_id']) ? $data['tafseer_id'] : null;
        $this->file_size = isset($data['file_size']) ? $data['file_size'] : 0;
        $this->format = isset($data['format']) ? $data['format'] : 'mp3';
        $this->audio_url = isset($data['audio_url']) ? $data['audio_url'] : '';
        $this->url = isset($data['url']) ? $data['url'] : $this->audio_url;
        $this->duration = isset($data['duration']) ? $data['duration'] : 0;
        $this->verse_range_from = isset($data['verse_range_from']) ? $data['verse_range_from'] : null;
        $this->verse_range_to = isset($data['verse_range_to']) ? $data['verse_range_to'] : null;
        
        // Parse verse range from and to
        if ($this->verse_range_from) {
            $parts = explode(':', $this->verse_range_from);
            $this->chapter_start = (int)$parts[0];
            $this->verse_start = (int)$parts[1];
        }
        
        if ($this->verse_range_to) {
            $parts = explode(':', $this->verse_range_to);
            $this->chapter_end = (int)$parts[0];
            $this->verse_end = (int)$parts[1];
        }
        
        $this->segments = isset($data['segments']) ? $data['segments'] : [];
        
        // Cloudinary fields
        $this->cloudinary_public_id = isset($data['cloudinary_public_id']) ? $data['cloudinary_public_id'] : null;
        $this->audio_format = isset($data['audio_format']) ? $data['audio_format'] : $this->format;
        $this->quality_urls = isset($data['quality_urls']) ? $data['quality_urls'] : [];
    }
}