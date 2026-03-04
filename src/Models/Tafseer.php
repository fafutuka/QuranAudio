<?php

namespace App\Models;

class Tafseer {
    public $id;
    public $mufasser_name;
    public $year;
    public $language;
    public $translated_name;
    public $description;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->mufasser_name = $data['mufasser_name'];
        $this->year = isset($data['year']) ? $data['year'] : null;
        $this->language = isset($data['language']) ? $data['language'] : 'ar';
        $this->translated_name = isset($data['translated_name']) ? $data['translated_name'] : ['name' => $this->mufasser_name, 'language_name' => 'en'];
        $this->description = isset($data['description']) ? $data['description'] : null;
    }
}