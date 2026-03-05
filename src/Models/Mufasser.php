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
    public $avatar_url;
    public $avatar_cloudinary_id;
    public $background_url;
    public $background_cloudinary_id;
    public $created_at;
    public $updated_at;

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
        $this->avatar_url = isset($data['avatar_url']) ? $data['avatar_url'] : null;
        $this->avatar_cloudinary_id = isset($data['avatar_cloudinary_id']) ? $data['avatar_cloudinary_id'] : null;
        $this->background_url = isset($data['background_url']) ? $data['background_url'] : null;
        $this->background_cloudinary_id = isset($data['background_cloudinary_id']) ? $data['background_cloudinary_id'] : null;
        $this->created_at = isset($data['created_at']) ? $data['created_at'] : null;
        $this->updated_at = isset($data['updated_at']) ? $data['updated_at'] : null;
    }
}