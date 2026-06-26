-- Post view logs for daily unique tracking
CREATE TABLE IF NOT EXISTS `post_views` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`    BIGINT UNSIGNED NOT NULL,
    `website_id` BIGINT UNSIGNED NOT NULL,
    `ip_hash`    VARCHAR(64) NOT NULL,
    `viewed_at`  DATE NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `post_views_unique` (`post_id`, `ip_hash`, `viewed_at`),
    KEY `post_views_post_id` (`post_id`),
    KEY `post_views_website_id_date` (`website_id`, `viewed_at`),
    CONSTRAINT `fk_post_views_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_post_views_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search term tracking
CREATE TABLE IF NOT EXISTS `search_terms` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `website_id`  BIGINT UNSIGNED NOT NULL,
    `term`        VARCHAR(255) NOT NULL,
    `results`     INT UNSIGNED NOT NULL DEFAULT 0,
    `searched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `search_terms_website_id` (`website_id`),
    KEY `search_terms_term` (`term`),
    CONSTRAINT `fk_search_terms_website` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
