-- Migration script to add Cloudinary support to existing audio_tafseers table

-- Add Cloudinary fields to audio_tafseers table
ALTER TABLE audio_tafseers 
ADD COLUMN cloudinary_public_id VARCHAR(255) NULL AFTER segments,
ADD COLUMN audio_format VARCHAR(10) DEFAULT 'mp3' AFTER cloudinary_public_id;

-- Add index for Cloudinary public_id for faster lookups
ALTER TABLE audio_tafseers 
ADD INDEX idx_cloudinary_public_id (cloudinary_public_id);

-- Update existing records to set audio_format from format field
UPDATE audio_tafseers SET audio_format = format WHERE audio_format IS NULL OR audio_format = '';

-- Optional: Add comment to document the Cloudinary fields
ALTER TABLE audio_tafseers 
MODIFY COLUMN cloudinary_public_id VARCHAR(255) NULL COMMENT 'Cloudinary public ID for the uploaded audio file',
MODIFY COLUMN audio_format VARCHAR(10) DEFAULT 'mp3' COMMENT 'Audio format (mp3, wav, ogg, etc.)';