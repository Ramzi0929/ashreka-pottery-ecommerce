-- Fix Heritage Archive Categorization Issues

-- 1. Update content_type enum to include all proper categories
ALTER TABLE `heritage_archive` 
MODIFY COLUMN `content_type` ENUM('document', 'image', 'video_link', 'photo', 'video') NOT NULL;

-- 2. Standardize existing content_type values
UPDATE `heritage_archive` SET `content_type` = 'image' WHERE `content_type` = 'photo';
UPDATE `heritage_archive` SET `content_type` = 'video_link' WHERE `content_type` = 'video';

-- 3. Add category field for better organization
ALTER TABLE `heritage_archive` 
ADD COLUMN `category` VARCHAR(50) DEFAULT 'general' AFTER `content_type`;

-- 4. Update categories based on content_type
UPDATE `heritage_archive` SET `category` = 'research' WHERE `content_type` = 'document';
UPDATE `heritage_archive` SET `category` = 'images' WHERE `content_type` = 'image';
UPDATE `heritage_archive` SET `category` = 'videos' WHERE `content_type` = 'video_link';

-- 5. Add language support
ALTER TABLE `heritage_archive` 
ADD COLUMN `language` VARCHAR(10) DEFAULT 'en' AFTER `category`;

-- 6. Ensure proper file paths for images
UPDATE `heritage_archive` 
SET `file_path` = CONCAT('assets/uploads/heritage/', SUBSTRING_INDEX(`file_path`, '/', -1))
WHERE `content_type` = 'image' AND `file_path` NOT LIKE 'assets/uploads/heritage/%';

-- 7. Clean up any NULL or empty video_url fields
UPDATE `heritage_archive` SET `video_url` = `video_link` WHERE `content_type` = 'video_link' AND (`video_url` IS NULL OR `video_url` = '');

-- 8. Final content_type standardization
ALTER TABLE `heritage_archive` 
MODIFY COLUMN `content_type` ENUM('document', 'image', 'video_link') NOT NULL;