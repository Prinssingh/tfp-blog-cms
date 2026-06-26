-- TFP Multi-Site Headless Blog CMS
-- Initial Schema
-- Run this file in Hostinger phpMyAdmin or via MySQL CLI

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- websites
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `websites` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `slug`       VARCHAR(255) NOT NULL,
    `domain`     VARCHAR(255) NOT NULL,
    `logo`       VARCHAR(500) NULL,
    `favicon`    VARCHAR(500) NULL,
    `timezone`   VARCHAR(100) NOT NULL DEFAULT 'UTC',
    `language`   VARCHAR(20)  NOT NULL DEFAULT 'en',
    `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `websites_slug_unique`   (`slug`),
    UNIQUE KEY `websites_domain_unique` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- permissions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- role_permissions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`       BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`    BIGINT UNSIGNED NULL,
    `role_id`       BIGINT UNSIGNED NOT NULL,
    `name`          VARCHAR(255) NOT NULL,
    `slug`          VARCHAR(255) NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `password`      VARCHAR(255) NOT NULL,
    `avatar`        VARCHAR(500) NULL,
    `bio`           TEXT NULL,
    `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`    TIMESTAMP NULL DEFAULT NULL,
    `updated_at`    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_website_id_index` (`website_id`),
    KEY `users_role_id_index`    (`role_id`),
    CONSTRAINT `fk_users_role`    FOREIGN KEY (`role_id`)    REFERENCES `roles`    (`id`),
    CONSTRAINT `fk_users_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`  BIGINT UNSIGNED NOT NULL,
    `parent_id`   BIGINT UNSIGNED NULL,
    `name`        VARCHAR(255) NOT NULL,
    `slug`        VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `image`       VARCHAR(500) NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `categories_website_slug_unique` (`website_id`, `slug`),
    KEY `categories_website_id_index` (`website_id`),
    KEY `categories_parent_id_index`  (`parent_id`),
    CONSTRAINT `fk_categories_website` FOREIGN KEY (`website_id`) REFERENCES `websites`    (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_categories_parent`  FOREIGN KEY (`parent_id`)  REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- tags
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id` BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `slug`       VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tags_website_slug_unique` (`website_id`, `slug`),
    CONSTRAINT `fk_tags_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- media
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `media` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`  BIGINT UNSIGNED NOT NULL,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `file_name`   VARCHAR(255) NOT NULL,
    `file_path`   VARCHAR(500) NOT NULL,
    `mime_type`   VARCHAR(100) NOT NULL,
    `size`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `width`       INT NULL,
    `height`      INT NULL,
    `alt_text`    VARCHAR(255) NULL,
    `caption`     TEXT NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `media_website_id_index` (`website_id`),
    CONSTRAINT `fk_media_website`  FOREIGN KEY (`website_id`)  REFERENCES `websites` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_media_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users`    (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- posts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`         BIGINT UNSIGNED NOT NULL,
    `author_id`          BIGINT UNSIGNED NOT NULL,
    `editor_id`          BIGINT UNSIGNED NULL,
    `category_id`        BIGINT UNSIGNED NOT NULL,
    `title`              VARCHAR(255) NOT NULL,
    `slug`               VARCHAR(255) NOT NULL,
    `excerpt`            TEXT NULL,
    `content`            LONGTEXT NULL,
    `featured_image`     VARCHAR(500) NULL,
    `featured_image_alt` VARCHAR(255) NULL,
    `reading_time`       INT NOT NULL DEFAULT 0,
    `status`             ENUM('draft','review','scheduled','published','archived') NOT NULL DEFAULT 'draft',
    `visibility`         ENUM('public','private','password') NOT NULL DEFAULT 'public',
    `password`           VARCHAR(255) NULL,
    `published_at`       TIMESTAMP NULL DEFAULT NULL,
    `scheduled_at`       TIMESTAMP NULL DEFAULT NULL,
    `created_at`         TIMESTAMP NULL DEFAULT NULL,
    `updated_at`         TIMESTAMP NULL DEFAULT NULL,
    `deleted_at`         TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `posts_website_slug_unique` (`website_id`, `slug`),
    KEY `posts_website_id_index`   (`website_id`),
    KEY `posts_author_id_index`    (`author_id`),
    KEY `posts_category_id_index`  (`category_id`),
    KEY `posts_status_index`       (`status`),
    KEY `posts_published_at_index` (`published_at`),
    FULLTEXT KEY `posts_search` (`title`, `excerpt`, `content`),
    CONSTRAINT `fk_posts_website`  FOREIGN KEY (`website_id`)  REFERENCES `websites`   (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_posts_author`   FOREIGN KEY (`author_id`)   REFERENCES `users`      (`id`),
    CONSTRAINT `fk_posts_editor`   FOREIGN KEY (`editor_id`)   REFERENCES `users`      (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- post_tags
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_tags` (
    `post_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`  BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`),
    CONSTRAINT `fk_post_tags_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_post_tags_tag`  FOREIGN KEY (`tag_id`)  REFERENCES `tags`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- post_revisions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_revisions` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`    BIGINT UNSIGNED NOT NULL,
    `content`    LONGTEXT NOT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `post_revisions_post_id_index` (`post_id`),
    CONSTRAINT `fk_revisions_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_revisions_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- seo_metadata
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seo_metadata` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`          BIGINT UNSIGNED NOT NULL,
    `entity_type`         VARCHAR(50) NOT NULL,
    `entity_id`           BIGINT UNSIGNED NOT NULL,
    `meta_title`          VARCHAR(255) NULL,
    `meta_description`    VARCHAR(500) NULL,
    `canonical_url`       VARCHAR(500) NULL,
    `robots`              VARCHAR(100) NOT NULL DEFAULT 'index,follow',
    `focus_keyword`       VARCHAR(255) NULL,
    `og_title`            VARCHAR(255) NULL,
    `og_description`      VARCHAR(500) NULL,
    `og_image`            VARCHAR(500) NULL,
    `twitter_title`       VARCHAR(255) NULL,
    `twitter_description` VARCHAR(500) NULL,
    `twitter_image`       VARCHAR(500) NULL,
    `schema_json`         JSON NULL,
    `created_at`          TIMESTAMP NULL DEFAULT NULL,
    `updated_at`          TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `seo_entity_unique` (`website_id`, `entity_type`, `entity_id`),
    CONSTRAINT `fk_seo_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- redirects
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `redirects` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`  BIGINT UNSIGNED NOT NULL,
    `old_url`     VARCHAR(500) NOT NULL,
    `new_url`     VARCHAR(500) NOT NULL,
    `status_code` INT NOT NULL DEFAULT 301,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `redirects_website_id_index` (`website_id`),
    CONSTRAINT `fk_redirects_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- menus
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menus` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id` BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `location`   VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_menus_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- menu_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `menu_id`    BIGINT UNSIGNED NOT NULL,
    `parent_id`  BIGINT UNSIGNED NULL,
    `title`      VARCHAR(255) NOT NULL,
    `url`        VARCHAR(500) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_menu_items_menu`   FOREIGN KEY (`menu_id`)   REFERENCES `menus`      (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_menu_items_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- api_keys
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`   BIGINT UNSIGNED NOT NULL,
    `name`         VARCHAR(255) NOT NULL,
    `api_key`      VARCHAR(255) NOT NULL,
    `last_used_at` TIMESTAMP NULL DEFAULT NULL,
    `expires_at`   TIMESTAMP NULL DEFAULT NULL,
    `created_at`   TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `api_keys_key_unique` (`api_key`),
    CONSTRAINT `fk_api_keys_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id` BIGINT UNSIGNED NOT NULL,
    `key`        VARCHAR(255) NOT NULL,
    `value`      LONGTEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_website_key_unique` (`website_id`, `key`),
    CONSTRAINT `fk_settings_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- audit_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`  BIGINT UNSIGNED NULL,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `action`      VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(100) NULL,
    `entity_id`   BIGINT UNSIGNED NULL,
    `old_values`  JSON NULL,
    `new_values`  JSON NULL,
    `ip_address`  VARCHAR(100) NULL,
    `user_agent`  TEXT NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `audit_logs_user_id_index`    (`user_id`),
    KEY `audit_logs_website_id_index` (`website_id`),
    CONSTRAINT `fk_audit_logs_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`),
    CONSTRAINT `fk_audit_logs_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed: default roles
-- --------------------------------------------------------
INSERT IGNORE INTO `roles` (`name`, `slug`, `created_at`) VALUES
('Super Admin',    'super_admin',    NOW()),
('Website Admin',  'website_admin',  NOW()),
('Editor',         'editor',         NOW()),
('Writer',         'writer',         NOW());

-- --------------------------------------------------------
-- Seed: default permissions
-- --------------------------------------------------------
INSERT IGNORE INTO `permissions` (`name`, `slug`, `created_at`) VALUES
('post.create',        'post.create',        NOW()),
('post.edit',          'post.edit',          NOW()),
('post.publish',       'post.publish',       NOW()),
('post.delete',        'post.delete',        NOW()),
('category.manage',    'category.manage',    NOW()),
('tag.manage',         'tag.manage',         NOW()),
('media.upload',       'media.upload',       NOW()),
('media.delete',       'media.delete',       NOW()),
('user.manage',        'user.manage',        NOW()),
('website.manage',     'website.manage',     NOW()),
('settings.manage',    'settings.manage',    NOW()),
('seo.manage',         'seo.manage',         NOW()),
('redirect.manage',    'redirect.manage',    NOW()),
('audit.view',         'audit.view',         NOW());

SET FOREIGN_KEY_CHECKS = 1;
