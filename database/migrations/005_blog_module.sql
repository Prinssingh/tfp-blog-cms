-- ============================================================
-- 005_blog_module.sql
-- Full Blog Module Enhancement
-- Run once on the production database
-- ============================================================

-- 1. Expand status ENUM on posts
ALTER TABLE `posts`
  MODIFY COLUMN `status` ENUM(
    'draft','review_requested','in_review','approved',
    'scheduled','published','archived','rejected','deleted'
  ) NOT NULL DEFAULT 'draft';

-- 2. Make category_id nullable (for posts with no category)
ALTER TABLE `posts`
  MODIFY COLUMN `category_id` BIGINT UNSIGNED NULL;

-- 3. Add all new columns to posts
ALTER TABLE `posts`
  ADD COLUMN `subtitle`               VARCHAR(500)   NULL                    AFTER `title`,
  ADD COLUMN `summary`                TEXT           NULL                    AFTER `excerpt`,
  ADD COLUMN `content_json`           LONGTEXT       NULL                    AFTER `content`,
  ADD COLUMN `plain_text_content`     LONGTEXT       NULL                    AFTER `content_json`,
  ADD COLUMN `word_count`             INT            NOT NULL DEFAULT 0      AFTER `reading_time`,
  ADD COLUMN `character_count`        INT            NOT NULL DEFAULT 0      AFTER `word_count`,
  ADD COLUMN `paragraph_count`        INT            NOT NULL DEFAULT 0      AFTER `character_count`,
  ADD COLUMN `priority`               ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  ADD COLUMN `is_featured`            TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `is_sticky`              TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `is_breaking_news`       TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `show_on_homepage`       TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `featured_order`         INT            NULL,
  ADD COLUMN `featured_image_width`   INT            NULL,
  ADD COLUMN `featured_image_height`  INT            NULL,
  ADD COLUMN `featured_image_caption` VARCHAR(500)   NULL,
  ADD COLUMN `featured_image_credit`  VARCHAR(255)   NULL,
  ADD COLUMN `seo_title`              VARCHAR(255)   NULL,
  ADD COLUMN `seo_description`        TEXT           NULL,
  ADD COLUMN `focus_keyword`          VARCHAR(255)   NULL,
  ADD COLUMN `secondary_keywords`     TEXT           NULL,
  ADD COLUMN `canonical_url`          VARCHAR(500)   NULL,
  ADD COLUMN `robots_directive`       VARCHAR(100)   NOT NULL DEFAULT 'index, follow',
  ADD COLUMN `og_title`               VARCHAR(255)   NULL,
  ADD COLUMN `og_description`         TEXT           NULL,
  ADD COLUMN `og_image`               VARCHAR(500)   NULL,
  ADD COLUMN `og_type`                VARCHAR(50)    NULL DEFAULT 'article',
  ADD COLUMN `twitter_title`          VARCHAR(255)   NULL,
  ADD COLUMN `twitter_description`    TEXT           NULL,
  ADD COLUMN `twitter_image`          VARCHAR(500)   NULL,
  ADD COLUMN `twitter_card`           VARCHAR(50)    NULL DEFAULT 'summary_large_image',
  ADD COLUMN `schema_type`            VARCHAR(50)    NULL DEFAULT 'BlogPosting',
  ADD COLUMN `schema_json`            JSON           NULL,
  ADD COLUMN `include_in_sitemap`     TINYINT(1)     NOT NULL DEFAULT 1,
  ADD COLUMN `sitemap_priority`       DECIMAL(2,1)   NOT NULL DEFAULT 0.5,
  ADD COLUMN `include_in_rss`         TINYINT(1)     NOT NULL DEFAULT 1,
  ADD COLUMN `review_notes`           TEXT           NULL,
  ADD COLUMN `editor_notes`           TEXT           NULL,
  ADD COLUMN `internal_notes`         TEXT           NULL,
  ADD COLUMN `rejection_reason`       TEXT           NULL,
  ADD COLUMN `assigned_editor_id`     BIGINT UNSIGNED NULL,
  ADD COLUMN `approved_by`            BIGINT UNSIGNED NULL,
  ADD COLUMN `published_by`           BIGINT UNSIGNED NULL,
  ADD COLUMN `first_published_at`     TIMESTAMP      NULL,
  ADD COLUMN `unpublished_at`         TIMESTAMP      NULL,
  ADD COLUMN `language`               VARCHAR(10)    NOT NULL DEFAULT 'en',
  ADD COLUMN `updated_by`             BIGINT UNSIGNED NULL,
  ADD COLUMN `deleted_by`             BIGINT UNSIGNED NULL,
  ADD CONSTRAINT `fk_posts_assigned_editor` FOREIGN KEY (`assigned_editor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_posts_approved_by`     FOREIGN KEY (`approved_by`)         REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_posts_published_by`    FOREIGN KEY (`published_by`)        REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 4. Expand post_revisions with more fields
ALTER TABLE `post_revisions`
  ADD COLUMN `title`           VARCHAR(255) NULL AFTER `post_id`,
  ADD COLUMN `content_json`    LONGTEXT     NULL AFTER `content`,
  ADD COLUMN `seo_title`       VARCHAR(255) NULL,
  ADD COLUMN `seo_description` TEXT         NULL,
  ADD COLUMN `revision_label`  VARCHAR(100) NULL,
  MODIFY COLUMN `content`      LONGTEXT     NULL;

-- ============================================================
-- post_categories (many-to-many)
-- ============================================================
CREATE TABLE IF NOT EXISTS `post_categories` (
    `post_id`     BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `is_primary`  TINYINT(1)      NOT NULL DEFAULT 0,
    PRIMARY KEY (`post_id`, `category_id`),
    KEY `fk_post_cat_category` (`category_id`),
    CONSTRAINT `fk_post_cat_post`     FOREIGN KEY (`post_id`)     REFERENCES `posts`      (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_post_cat_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- post_workflow_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `post_workflow_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`     BIGINT UNSIGNED NOT NULL,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(50)     NULL,
    `to_status`   VARCHAR(50)     NOT NULL,
    `comment`     TEXT            NULL,
    `created_at`  TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_wf_post`   (`post_id`),
    KEY `fk_wf_user`   (`user_id`),
    CONSTRAINT `fk_wf_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- preview_tokens
-- ============================================================
CREATE TABLE IF NOT EXISTS `preview_tokens` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`    BIGINT UNSIGNED NOT NULL,
    `token`      VARCHAR(64)     NOT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `expires_at` TIMESTAMP       NOT NULL,
    `created_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `preview_tokens_token_unique` (`token`),
    KEY `fk_preview_post` (`post_id`),
    CONSTRAINT `fk_preview_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Add index for deleted posts (trash) queries
-- ============================================================
ALTER TABLE `posts`
  ADD INDEX `posts_deleted_at_index` (`deleted_at`);
