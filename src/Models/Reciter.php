<?php

namespace App\Models;

class Reciter {
    public $id;
    public $name;
    public $arabic_name;
    public $relative_path;
    public $format = 'mp3';
    public $files_size;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->arabic_name = isset($data['arabic_name']) ? $data['arabic_name'] : null;
        $this->relative_path = isset($data['relative_path']) ? $data['relative_path'] : '';
        $this->format = isset($data['format']) ? $data['format'] : 'mp3';
        $this->files_size = isset($data['files_size']) ? $data['files_size'] : 0;
    }
}
