-- ============================================================
-- 006_categories_tags_enhancement.sql
-- Enhance categories and tags with SEO, hierarchy, status,
-- display settings, soft-delete, and analytics columns.
-- Run once on the production database.
-- ============================================================

-- ── 1. Enhance categories ───────────────────────────────────

ALTER TABLE `categories`
  ADD COLUMN `uuid`               CHAR(36)       NOT NULL DEFAULT (UUID()) AFTER `id`,
  ADD COLUMN `short_description`  VARCHAR(500)   NULL                          AFTER `description`,
  ADD COLUMN `depth`              TINYINT        NOT NULL DEFAULT 0             AFTER `parent_id`,
  ADD COLUMN `path`               VARCHAR(1000)  NULL                           AFTER `sort_order`,

  -- images
  ADD COLUMN `image_url`          VARCHAR(500)   NULL,
  ADD COLUMN `image_alt`          VARCHAR(255)   NULL,
  ADD COLUMN `cover_image_url`    VARCHAR(500)   NULL,
  ADD COLUMN `icon`               VARCHAR(100)   NULL,

  -- display settings
  ADD COLUMN `show_in_menu`       TINYINT(1)     NOT NULL DEFAULT 1,
  ADD COLUMN `show_in_homepage`   TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `show_in_footer`     TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `show_in_sidebar`    TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `is_featured`        TINYINT(1)     NOT NULL DEFAULT 0,
  ADD COLUMN `is_hidden`          TINYINT(1)     NOT NULL DEFAULT 0,

  -- SEO
  ADD COLUMN `seo_title`          VARCHAR(70)    NULL,
  ADD COLUMN `seo_description`    VARCHAR(165)   NULL,
  ADD COLUMN `focus_keyword`      VARCHAR(255)   NULL,
  ADD COLUMN `canonical_url`      VARCHAR(500)   NULL,
  ADD COLUMN `robots_directive`   VARCHAR(100)   NOT NULL DEFAULT 'index, follow',

  -- Open Graph
  ADD COLUMN `og_title`           VARCHAR(255)   NULL,
  ADD COLUMN `og_description`     TEXT           NULL,
  ADD COLUMN `og_image`           VARCHAR(500)   NULL,

  -- Twitter Cards
  ADD COLUMN `twitter_title`      VARCHAR(255)   NULL,
  ADD COLUMN `twitter_description` TEXT          NULL,
  ADD COLUMN `twitter_image`      VARCHAR(500)   NULL,

  -- Sitemap
  ADD COLUMN `include_in_sitemap` TINYINT(1)     NOT NULL DEFAULT 1,
  ADD COLUMN `sitemap_priority`   DECIMAL(2,1)   NOT NULL DEFAULT 0.5,
  ADD COLUMN `change_frequency`   ENUM('always','hourly','daily','weekly','monthly','yearly','never')
                                                 NOT NULL DEFAULT 'weekly',

  -- Analytics (denormalized for speed)
  ADD COLUMN `posts_count`        INT            NOT NULL DEFAULT 0,
  ADD COLUMN `views_count`        BIGINT         NOT NULL DEFAULT 0,
  ADD COLUMN `monthly_views`      INT            NOT NULL DEFAULT 0,

  -- Status
  ADD COLUMN `status`             ENUM('active','hidden','archived') NOT NULL DEFAULT 'active',

  -- Audit
  ADD COLUMN `created_by`         BIGINT UNSIGNED NULL,
  ADD COLUMN `updated_by`         BIGINT UNSIGNED NULL,
  ADD COLUMN `deleted_by`         BIGINT UNSIGNED NULL,
  ADD COLUMN `deleted_at`         DATETIME       NULL;

-- Rename old `image` column if it exists (safe with IGNORE)
ALTER TABLE `categories`
  CHANGE COLUMN `image` `image` VARCHAR(500) NULL;

-- Index for soft-delete queries
ALTER TABLE `categories`
  ADD INDEX `categories_deleted_at_idx` (`deleted_at`),
  ADD INDEX `categories_status_idx` (`status`);

-- ── 2. Enhance tags ─────────────────────────────────────────

ALTER TABLE `tags`
  ADD COLUMN `uuid`               CHAR(36)       NOT NULL DEFAULT (UUID()) AFTER `id`,
  ADD COLUMN `description`        TEXT           NULL                           AFTER `slug`,
  ADD COLUMN `color`              VARCHAR(7)     NULL,
  ADD COLUMN `icon`               VARCHAR(100)   NULL,

  -- SEO
  ADD COLUMN `seo_title`          VARCHAR(70)    NULL,
  ADD COLUMN `seo_description`    VARCHAR(165)   NULL,
  ADD COLUMN `focus_keyword`      VARCHAR(255)   NULL,
  ADD COLUMN `canonical_url`      VARCHAR(500)   NULL,
  ADD COLUMN `robots_directive`   VARCHAR(100)   NOT NULL DEFAULT 'index, follow',

  -- Analytics
  ADD COLUMN `posts_count`        INT            NOT NULL DEFAULT 0,
  ADD COLUMN `views_count`        BIGINT         NOT NULL DEFAULT 0,

  -- Status
  ADD COLUMN `status`             ENUM('active','hidden','archived') NOT NULL DEFAULT 'active',

  -- Audit
  ADD COLUMN `created_by`         BIGINT UNSIGNED NULL,
  ADD COLUMN `updated_by`         BIGINT UNSIGNED NULL,
  ADD COLUMN `deleted_by`         BIGINT UNSIGNED NULL,
  ADD COLUMN `deleted_at`         DATETIME       NULL;

ALTER TABLE `tags`
  ADD INDEX `tags_deleted_at_idx` (`deleted_at`),
  ADD INDEX `tags_status_idx`     (`status`);
