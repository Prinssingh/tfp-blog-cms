-- Migration: Website enhancements
-- Adds branding, settings, status expansion, soft delete

ALTER TABLE `websites`
  ADD COLUMN `description`      TEXT          NULL              AFTER `domain`,
  ADD COLUMN `subdomain`        VARCHAR(255)  NULL              AFTER `description`,
  ADD COLUMN `cover_image_url`  VARCHAR(500)  NULL              AFTER `favicon`,
  ADD COLUMN `theme_color`      VARCHAR(20)   NULL DEFAULT '#000000' AFTER `cover_image_url`,
  ADD COLUMN `accent_color`     VARCHAR(20)   NULL DEFAULT '#3b82f6' AFTER `theme_color`,
  ADD COLUMN `currency`         VARCHAR(10)   NOT NULL DEFAULT 'USD' AFTER `accent_color`,
  ADD COLUMN `settings`         JSON          NULL              AFTER `currency`,
  ADD COLUMN `deleted_at`       TIMESTAMP     NULL DEFAULT NULL  AFTER `updated_at`;

ALTER TABLE `websites`
  MODIFY COLUMN `status` ENUM('active','maintenance','suspended','archived','inactive')
    NOT NULL DEFAULT 'active';

-- Rename logo/favicon to logo_url/favicon_url for consistency
ALTER TABLE `websites`
  CHANGE COLUMN `logo`    `logo_url`    VARCHAR(500) NULL,
  CHANGE COLUMN `favicon` `favicon_url` VARCHAR(500) NULL;
