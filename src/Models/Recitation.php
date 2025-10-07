<?php

namespace App\Models;

class Recitation {
    public $id;
    public $reciter_name;
    public $style;
    public $translated_name;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->reciter_name = $data['reciter_name'];
        $this->style = isset($data['style']) ? $data['style'] : null;
        $this->translated_name = isset($data['translated_name']) ? $data['translated_name'] : ['name' => $this->reciter_name, 'language_name' => 'en'];
    }
}
