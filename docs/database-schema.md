# Database Schema Documentation

# Database Engine

MySQL 8+

Character Set:

```sql
utf8mb4
```

Collation:

```sql
utf8mb4_unicode_ci
```

---

# Global Rules

## Primary Keys

Every table uses:

```sql
BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

---

## Timestamps

Every table contains:

```sql
created_at TIMESTAMP NULL
updated_at TIMESTAMP NULL
```

Recoverable entities contain:

```sql
deleted_at TIMESTAMP NULL
```

---

## Multi Tenant Rules

Every business table contains:

```sql
website_id BIGINT UNSIGNED NOT NULL
```

Every tenant query must filter by:

```sql
website_id
```

---

# websites

Stores all websites managed by the CMS.

| Column     | Type                  |
| ---------- | --------------------- |
| id         | BIGINT                |
| name       | VARCHAR(255)          |
| slug       | VARCHAR(255)          |
| domain     | VARCHAR(255)          |
| logo       | VARCHAR(500)          |
| favicon    | VARCHAR(500)          |
| timezone   | VARCHAR(100)          |
| language   | VARCHAR(20)           |
| status     | ENUM(active,inactive) |
| created_at | TIMESTAMP             |
| updated_at | TIMESTAMP             |

Indexes:

```text
UNIQUE(domain)
UNIQUE(slug)
```

---

# users

System users.

| Column        | Type                  |
| ------------- | --------------------- |
| id            | BIGINT                |
| website_id    | BIGINT NULL           |
| role_id       | BIGINT                |
| name          | VARCHAR(255)          |
| slug          | VARCHAR(255)          |
| email         | VARCHAR(255)          |
| password      | VARCHAR(255)          |
| avatar        | VARCHAR(500)          |
| bio           | TEXT                  |
| status        | ENUM(active,inactive) |
| last_login_at | TIMESTAMP             |
| created_at    | TIMESTAMP             |
| updated_at    | TIMESTAMP             |

Indexes:

```text
UNIQUE(email)
INDEX(website_id)
INDEX(role_id)
```

---

# roles

| Column     | Type         |
| ---------- | ------------ |
| id         | BIGINT       |
| name       | VARCHAR(100) |
| slug       | VARCHAR(100) |
| created_at | TIMESTAMP    |
| updated_at | TIMESTAMP    |

Examples:

* super_admin
* website_admin
* editor
* writer

---

# permissions

| Column     | Type         |
| ---------- | ------------ |
| id         | BIGINT       |
| name       | VARCHAR(100) |
| slug       | VARCHAR(100) |
| created_at | TIMESTAMP    |
| updated_at | TIMESTAMP    |

Examples:

* post.create
* post.publish
* media.upload

---

# role_permissions

Many-to-many relation.

| Column        | Type   |
| ------------- | ------ |
| role_id       | BIGINT |
| permission_id | BIGINT |

Composite Key:

```text
(role_id, permission_id)
```

---

# categories

| Column      | Type         |
| ----------- | ------------ |
| id          | BIGINT       |
| website_id  | BIGINT       |
| parent_id   | BIGINT NULL  |
| name        | VARCHAR(255) |
| slug        | VARCHAR(255) |
| description | TEXT         |
| image       | VARCHAR(500) |
| sort_order  | INT          |
| created_at  | TIMESTAMP    |
| updated_at  | TIMESTAMP    |

Indexes:

```text
INDEX(website_id)
INDEX(parent_id)
UNIQUE(website_id, slug)
```

---

# tags

| Column     | Type         |
| ---------- | ------------ |
| id         | BIGINT       |
| website_id | BIGINT       |
| name       | VARCHAR(255) |
| slug       | VARCHAR(255) |
| created_at | TIMESTAMP    |
| updated_at | TIMESTAMP    |

Indexes:

```text
UNIQUE(website_id, slug)
```

---

# posts

Core content table.

| Column             | Type                                            |
| ------------------ | ----------------------------------------------- |
| id                 | BIGINT                                          |
| website_id         | BIGINT                                          |
| author_id          | BIGINT                                          |
| editor_id          | BIGINT NULL                                     |
| category_id        | BIGINT                                          |
| title              | VARCHAR(255)                                    |
| slug               | VARCHAR(255)                                    |
| excerpt            | TEXT                                            |
| content            | LONGTEXT                                        |
| featured_image     | VARCHAR(500)                                    |
| featured_image_alt | VARCHAR(255)                                    |
| reading_time       | INT                                             |
| status             | ENUM(draft,review,scheduled,published,archived) |
| visibility         | ENUM(public,private,password)                   |
| password           | VARCHAR(255) NULL                               |
| published_at       | TIMESTAMP NULL                                  |
| scheduled_at       | TIMESTAMP NULL                                  |
| created_at         | TIMESTAMP                                       |
| updated_at         | TIMESTAMP                                       |
| deleted_at         | TIMESTAMP NULL                                  |

Indexes:

```text
INDEX(website_id)
INDEX(author_id)
INDEX(category_id)
INDEX(status)
INDEX(published_at)
UNIQUE(website_id, slug)
FULLTEXT(title, excerpt, content)
```

---

# post_tags

Many-to-many table.

| Column  | Type   |
| ------- | ------ |
| post_id | BIGINT |
| tag_id  | BIGINT |

Composite Key:

```text
(post_id, tag_id)
```

---

# post_revisions

Stores content history.

| Column     | Type      |
| ---------- | --------- |
| id         | BIGINT    |
| post_id    | BIGINT    |
| content    | LONGTEXT  |
| created_by | BIGINT    |
| created_at | TIMESTAMP |

---

# media

Stores uploaded files.

| Column      | Type         |
| ----------- | ------------ |
| id          | BIGINT       |
| website_id  | BIGINT       |
| uploaded_by | BIGINT       |
| file_name   | VARCHAR(255) |
| file_path   | VARCHAR(500) |
| mime_type   | VARCHAR(100) |
| size        | BIGINT       |
| width       | INT          |
| height      | INT          |
| alt_text    | VARCHAR(255) |
| caption     | TEXT         |
| created_at  | TIMESTAMP    |

---

# seo_metadata

Dedicated SEO table.

| Column              | Type         |
| ------------------- | ------------ |
| id                  | BIGINT       |
| website_id          | BIGINT       |
| entity_type         | VARCHAR(50)  |
| entity_id           | BIGINT       |
| meta_title          | VARCHAR(255) |
| meta_description    | VARCHAR(500) |
| canonical_url       | VARCHAR(500) |
| robots              | VARCHAR(100) |
| focus_keyword       | VARCHAR(255) |
| og_title            | VARCHAR(255) |
| og_description      | VARCHAR(500) |
| og_image            | VARCHAR(500) |
| twitter_title       | VARCHAR(255) |
| twitter_description | VARCHAR(500) |
| twitter_image       | VARCHAR(500) |
| schema_json         | JSON         |
| created_at          | TIMESTAMP    |
| updated_at          | TIMESTAMP    |

Supported entity types:

* post
* category
* tag
* author
* page

---

# redirects

SEO redirects.

| Column      | Type         |
| ----------- | ------------ |
| id          | BIGINT       |
| website_id  | BIGINT       |
| old_url     | VARCHAR(500) |
| new_url     | VARCHAR(500) |
| status_code | INT          |
| created_at  | TIMESTAMP    |

Examples:

* 301
* 302

---

# menus

Navigation menus.

| Column     | Type         |
| ---------- | ------------ |
| id         | BIGINT       |
| website_id | BIGINT       |
| name       | VARCHAR(255) |
| location   | VARCHAR(100) |
| created_at | TIMESTAMP    |

---

# menu_items

| Column     | Type         |
| ---------- | ------------ |
| id         | BIGINT       |
| menu_id    | BIGINT       |
| parent_id  | BIGINT NULL  |
| title      | VARCHAR(255) |
| url        | VARCHAR(500) |
| sort_order | INT          |
| created_at | TIMESTAMP    |

---

# api_keys

API access keys for websites.

| Column       | Type         |
| ------------ | ------------ |
| id           | BIGINT       |
| website_id   | BIGINT       |
| name         | VARCHAR(255) |
| api_key      | VARCHAR(255) |
| last_used_at | TIMESTAMP    |
| expires_at   | TIMESTAMP    |
| created_at   | TIMESTAMP    |

---

# audit_logs

Tracks important actions.

| Column      | Type         |
| ----------- | ------------ |
| id          | BIGINT       |
| website_id  | BIGINT NULL  |
| user_id     | BIGINT       |
| action      | VARCHAR(255) |
| entity_type | VARCHAR(100) |
| entity_id   | BIGINT       |
| old_values  | JSON         |
| new_values  | JSON         |
| ip_address  | VARCHAR(100) |
| user_agent  | TEXT         |
| created_at  | TIMESTAMP    |

---

# settings

Per website settings.

| Column     | Type         |
| ---------- | ------------ |
| id         | BIGINT       |
| website_id | BIGINT       |
| key        | VARCHAR(255) |
| value      | LONGTEXT     |
| created_at | TIMESTAMP    |
| updated_at | TIMESTAMP    |

Examples:

* site_title
* theme_color
* analytics_code

---

# Future Tables

Reserved for future expansion.

```text
comments
newsletter_subscribers
analytics_events
push_notifications
translations
webhooks
ai_content_history
```

---

# Estimated Scale

Target capacity:

* 50 websites
* 500 writers
* 1 million posts
* 20 million monthly page views

This schema is designed to support these numbers without major redesign.
