-- Add views counter to posts
ALTER TABLE `posts`
    ADD COLUMN `views` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `reading_time`;
