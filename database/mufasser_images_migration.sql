-- Migration to add image fields to mufassers table
-- Run this script to update existing mufassers table with image support

-- Add image columns to existing mufassers table
ALTER TABLE mufassers 
ADD COLUMN avatar_url VARCHAR(1000) NULL AFTER death_year,
ADD COLUMN avatar_cloudinary_id VARCHAR(255) NULL AFTER avatar_url,
ADD COLUMN background_url VARCHAR(1000) NULL AFTER avatar_cloudinary_id,
ADD COLUMN background_cloudinary_id VARCHAR(255) NULL AFTER background_url;

-- Add indexes for better performance
CREATE INDEX idx_avatar_cloudinary_id ON mufassers(avatar_cloudinary_id);
CREATE INDEX idx_background_cloudinary_id ON mufassers(background_cloudinary_id);

-- Update existing sample data with placeholder images (optional)
UPDATE mufassers SET 
    avatar_url = CONCAT('https://ui-avatars.com/api/?name=', REPLACE(name, ' ', '+'), '&size=400&background=random'),
    background_url = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=600&fit=crop'
WHERE id IN (1, 2, 3, 4, 5);