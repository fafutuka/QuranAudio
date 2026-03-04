<?php

namespace App\Models;

class Mufasser {
    public $id;
    public $name;
    public $arabic_name;
    public $relative_path;
    public $format = 'mp3';
    public $files_size;
    public $biography;
    public $birth_year;
    public $death_year;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->arabic_name = isset($data['arabic_name']) ? $data['arabic_name'] : null;
        $this->relative_path = isset($data['relative_path']) ? $data['relative_path'] : '';
        $this->format = isset($data['format']) ? $data['format'] : 'mp3';
        $this->files_size = isset($data['files_size']) ? $data['files_size'] : 0;
        $this->biography = isset($data['biography']) ? $data['biography'] : null;
        $this->birth_year = isset($data['birth_year']) ? $data['birth_year'] : null;
        $this->death_year = isset($data['death_year']) ? $data['death_year'] : null;
    }
}