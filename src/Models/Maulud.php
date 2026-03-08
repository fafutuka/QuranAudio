<?php

namespace App\Models;

/**
 * Maulud Model
 * 
 * Represents a Maulud (Prophet's Birthday celebration) audio recording
 * with metadata including dates, location, and audio file information.
 */
class Maulud
{
    public $id;
    public $mufasser_id;
    public $title;
    public $islamic_date;
    public $gregorian_date;
    public $location;
    public $audio_url;
    public $duration; // Duration in milliseconds
    public $file_size;
    public $format;
    public $cloudinary_public_id;
    public $created_at;
    public $updated_at;
    
    // Related mufasser data (when joined)
    public $mufasser_name;
    public $mufasser_arabic_name;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->mufasser_id = $data['mufasser_id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->islamic_date = $data['islamic_date'] ?? null;
        $this->gregorian_date = $data['gregorian_date'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->audio_url = $data['audio_url'] ?? '';
        $this->duration = $data['duration'] ?? 0;
        $this->file_size = $data['file_size'] ?? 0;
        $this->format = $data['format'] ?? 'mp3';
        $this->cloudinary_public_id = $data['cloudinary_public_id'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        
        // Related mufasser data
        $this->mufasser_name = $data['mufasser_name'] ?? null;
        $this->mufasser_arabic_name = $data['mufasser_arabic_name'] ?? null;
    }

    /**
     * Convert duration from milliseconds to human-readable format
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration) {
            return '0:00';
        }
        
        $seconds = floor($this->duration / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        
        $remainingMinutes = $minutes % 60;
        $remainingSeconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $remainingMinutes, $remainingSeconds);
        }
        
        return sprintf('%d:%02d', $remainingMinutes, $remainingSeconds);
    }

    /**
     * Convert file size to human-readable format
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get array representation for JSON response
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'mufasser_id' => $this->mufasser_id,
            'title' => $this->title,
            'islamic_date' => $this->islamic_date,
            'gregorian_date' => $this->gregorian_date,
            'location' => $this->location,
            'audio_url' => $this->audio_url,
            'duration' => $this->duration,
            'file_size' => $this->file_size,
            'format' => $this->format,
            'cloudinary_public_id' => $this->cloudinary_public_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_duration' => $this->getFormattedDuration(),
            'formatted_file_size' => $this->getFormattedFileSize()
        ];

        // Include mufasser data if available
        if ($this->mufasser_name) {
            $data['mufasser'] = [
                'id' => $this->mufasser_id,
                'name' => $this->mufasser_name,
                'arabic_name' => $this->mufasser_arabic_name
            ];
        }

        return $data;
    }
}