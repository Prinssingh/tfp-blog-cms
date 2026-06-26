# API Specification

# Base URL

Production:

```text
https://cms.example.com/api/v1
```

Development:

```text
http://localhost:8000/api/v1
```

---

# API Design Principles

* REST API
* JSON only
* Stateless
* JWT Authentication
* Versioned endpoints
* Multi-tenant aware

---

# Standard Response Format

## Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {},
  "meta": {}
}
```

## Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": [
      "Title is required."
    ]
  }
}
```

---

# Authentication

## Login

```http
POST /auth/login
```

Request:

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Response:

```json
{
  "success": true,
  "data": {
    "access_token": "",
    "refresh_token": "",
    "expires_in": 3600,
    "user": {}
  }
}
```

---

## Refresh Token

```http
POST /auth/refresh
```

---

## Logout

```http
POST /auth/logout
```

---

## Current User

```http
GET /auth/me
```

---

# Websites Module

## List Websites

```http
GET /websites
```

## Create Website

```http
POST /websites
```

Request:

```json
{
  "name": "Tech Blog",
  "slug": "tech-blog",
  "domain": "techblog.com"
}
```

---

## Update Website

```http
PUT /websites/{id}
```

---

## Delete Website

```http
DELETE /websites/{id}
```

---

# Users Module

## List Users

```http
GET /users
```

Filters:

```text
role=
status=
search=
```

---

## Create User

```http
POST /users
```

Request:

```json
{
  "website_id": 1,
  "role_id": 4,
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret"
}
```

---

## Update User

```http
PUT /users/{id}
```

---

## Delete User

```http
DELETE /users/{id}
```

---

# Categories Module

## List Categories

```http
GET /categories
```

Query Parameters:

```text
page=
limit=
search=
parent_id=
```

---

## Create Category

```http
POST /categories
```

---

## Update Category

```http
PUT /categories/{id}
```

---

## Delete Category

```http
DELETE /categories/{id}
```

---

# Tags Module

## List Tags

```http
GET /tags
```

---

## Create Tag

```http
POST /tags
```

---

## Update Tag

```http
PUT /tags/{id}
```

---

## Delete Tag

```http
DELETE /tags/{id}
```

---

# Posts Module

## List Posts

```http
GET /posts
```

Query Parameters:

```text
page=
limit=
search=
status=
category_id=
author_id=
tag=
sort=
order=
```

Example:

```http
GET /posts?page=1&limit=20&status=published
```

---

## Create Post

```http
POST /posts
```

Request:

```json
{
  "title": "Best React Practices",
  "slug": "best-react-practices",
  "excerpt": "",
  "content": "",
  "category_id": 1,
  "tags": [1,2],
  "status": "draft"
}
```

---

## Get Post

```http
GET /posts/{id}
```

---

## Update Post

```http
PUT /posts/{id}
```

---

## Delete Post

```http
DELETE /posts/{id}
```

Soft delete only.

---

## Publish Post

```http
POST /posts/{id}/publish
```

---

## Schedule Post

```http
POST /posts/{id}/schedule
```

Request:

```json
{
  "scheduled_at": "2026-07-01 10:00:00"
}
```

---

## Duplicate Post

```http
POST /posts/{id}/duplicate
```

---

# Media Module

## Upload Media

```http
POST /media/upload
```

Multipart Form Data:

```text
file
alt_text
caption
```

---

## List Media

```http
GET /media
```

Filters:

```text
type=
search=
uploaded_by=
```

---

## Delete Media

```http
DELETE /media/{id}
```

---

# SEO Module

## Update SEO Metadata

```http
PUT /seo/{entity_type}/{entity_id}
```

Request:

```json
{
  "meta_title": "",
  "meta_description": "",
  "canonical_url": "",
  "focus_keyword": "",
  "robots": "index,follow"
}
```

---

## Get SEO Metadata

```http
GET /seo/{entity_type}/{entity_id}
```

---

# Redirect Module

## Create Redirect

```http
POST /redirects
```

Request:

```json
{
  "old_url": "/old-post",
  "new_url": "/new-post",
  "status_code": 301
}
```

---

# Settings Module

## Get Settings

```http
GET /settings
```

---

## Update Settings

```http
PUT /settings
```

---

# Audit Logs

## List Logs

```http
GET /audit-logs
```

Filters:

```text
user_id=
entity_type=
action=
```

---

# Public APIs

These endpoints require no authentication.

---

## Public Posts

```http
GET /public/posts
```

Query Parameters:

```text
website=
page=
limit=
category=
tag=
author=
search=
```

Example:

```http
GET /public/posts?website=techblog.com&page=1
```

---

## Single Post

```http
GET /public/posts/{slug}
```

Example:

```http
GET /public/posts/best-react-practices?website=techblog.com
```

---

## Categories

```http
GET /public/categories
```

---

## Tags

```http
GET /public/tags
```

---

## Authors

```http
GET /public/authors
```

---

## Search

```http
GET /public/search?q=react
```

---

## Related Posts

```http
GET /public/posts/{slug}/related
```

---

## Sitemap

```http
GET /public/sitemap.xml
GET /public/sitemap-posts.xml
GET /public/sitemap-categories.xml
GET /public/sitemap-tags.xml
```

---

## Robots

```http
GET /public/robots.txt
```

---

## RSS Feed

```http
GET /public/rss.xml
```

---

# Rate Limits

Authenticated API:

```text
1000 requests/hour
```

Public API:

```text
500 requests/hour/IP
```

Authentication API:

```text
10 requests/minute/IP
```

---

# Authentication Header

```http
Authorization: Bearer ACCESS_TOKEN
```

---

# Pagination Format

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 200,
    "last_page": 10
  }
}
```

---

# API Versioning Strategy

Current Version:

```text
v1
```

Future versions:

```text
/api/v2
/api/v3
```

Never introduce breaking changes inside the same API version.

---

# Tenant Resolution Strategy

Priority order:

1. JWT website_id
2. API key website_id
3. Domain mapping
4. Explicit website query parameter

Every request must resolve to exactly one tenant before executing business logic.

---

# Future APIs

Reserved modules:

* comments
* newsletters
* analytics
* notifications
* translations
* webhooks
* AI content generation
* bulk import/export

These modules must follow the same response structure and authentication model.
