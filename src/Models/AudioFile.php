<?php

namespace App\Models;

class AudioFile {
    public $id;
    public $chapter_id;
    public $file_size;
    public $format;
    public $audio_url;
    public $url;
    public $duration;
    public $segments = [];
    public $total_files;
    public $verse_key;
    public $timestamps = [];

    public function __construct($data) {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->chapter_id = isset($data['chapter_id']) ? $data['chapter_id'] : null;
        $this->file_size = isset($data['file_size']) ? $data['file_size'] : 0;
        $this->format = isset($data['format']) ? $data['format'] : 'mp3';
        $this->audio_url = isset($data['audio_url']) ? $data['audio_url'] : '';
        $this->url = isset($data['url']) ? $data['url'] : $this->audio_url;
        $this->duration = isset($data['duration']) ? $data['duration'] : 0;
        $this->segments = isset($data['segments']) ? $data['segments'] : [];
        $this->total_files = isset($data['total_files']) ? $data['total_files'] : 1;
        $this->verse_key = isset($data['verse_key']) ? $data['verse_key'] : null;
    }
}
