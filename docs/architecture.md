# Architecture Documentation

# Project Name

TFP Multi-Site Headless Blog CMS

---

# System Overview

The system is a multi-tenant headless CMS built using PHP and MySQL.

The CMS provides REST APIs that are consumed by multiple React websites.

The backend is responsible for:

* Authentication
* Authorization
* Content management
* SEO management
* Media management
* Analytics
* Sitemap generation

The frontend websites are responsible for:

* Rendering content
* SEO rendering
* User interaction

---

# High Level Architecture

```text
React Website 1
React Website 2
React Website 3
React Website N
        │
        │ REST API
        ▼
+----------------------+
| PHP CMS API          |
| Authentication       |
| RBAC                 |
| Posts                |
| SEO Engine           |
| Media                |
| Analytics            |
+----------------------+
        │
        ▼
+----------------------+
| MySQL Database       |
+----------------------+
```

---

# Architectural Principles

## API First

All functionality must be accessible through APIs.

The admin panel must consume the same APIs as external clients.

No direct database access from frontend applications.

---

## Multi Tenant

The platform serves multiple websites from a single backend instance.

Tenant isolation is implemented using:

```text
website_id
```

Every business table must include:

```text
website_id
```

Examples:

```text
posts
categories
tags
media
settings
menus
redirects
```

---

## Separation Of Concerns

Responsibilities are separated into layers.

```text
Controller
    ↓
Service
    ↓
Repository
    ↓
Database
```

---

# Layer Responsibilities

## Controllers

Responsibilities:

* Parse request
* Validate request
* Call service
* Return response

Controllers must not contain:

* SQL queries
* Business rules
* Complex logic

---

## Services

Responsibilities:

* Business rules
* Transactions
* Workflows
* Domain logic

Examples:

* Publishing a post
* Updating sitemap
* Generating schema data

---

## Repositories

Responsibilities:

* Database operations
* Query construction
* Persistence logic

Repositories must not contain:

* Validation
* Business rules

---

## DTOs

Responsibilities:

* Input validation
* Type safety
* Transformation

Examples:

```text
CreatePostDTO
UpdatePostDTO
CreateCategoryDTO
```

---

## Middleware

Responsibilities:

* Authentication
* Authorization
* Tenant resolution
* Rate limiting
* Logging

---

# Module Architecture

## Authentication Module

Responsibilities:

* Login
* Logout
* Token refresh
* Password reset

Dependencies:

```text
User Repository
JWT Service
Audit Service
```

---

## RBAC Module

Responsibilities:

* Role management
* Permission checks

Dependencies:

```text
Role Repository
Permission Repository
```

---

## Website Module

Responsibilities:

* Website creation
* Website settings
* Domain management

Dependencies:

```text
Website Repository
```

---

## User Module

Responsibilities:

* User management
* Role assignment

Dependencies:

```text
User Repository
Role Repository
```

---

## Content Module

Responsibilities:

* Posts
* Categories
* Tags
* Revisions

Dependencies:

```text
Post Repository
Category Repository
Tag Repository
```

---

## Media Module

Responsibilities:

* Uploads
* Compression
* Thumbnail generation

Dependencies:

```text
Media Repository
Image Service
```

---

## SEO Module

Responsibilities:

* Metadata
* Canonicals
* OpenGraph
* Twitter cards
* Structured data

Dependencies:

```text
Seo Repository
Schema Service
```

---

## Sitemap Module

Responsibilities:

* XML sitemap generation
* Sitemap indexing

Dependencies:

```text
Post Repository
Category Repository
Tag Repository
```

---

# Request Lifecycle

Example request:

```text
POST /api/posts
```

Flow:

```text
Request
↓
Router
↓
Authentication Middleware
↓
Authorization Middleware
↓
Validation Middleware
↓
Controller
↓
Service
↓
Repository
↓
Database
↓
Response
```

---

# Public API Flow

```text
React Website
↓
Public API Endpoint
↓
Cache Layer
↓
Repository
↓
Database
```

---

# Admin API Flow

```text
Admin Dashboard
↓
JWT Authentication
↓
Authorization
↓
Controller
↓
Service
↓
Repository
↓
Database
```

---

# Database Architecture

## Core Tables

```text
websites
users
roles
permissions
categories
tags
posts
post_tags
media
seo_metadata
post_revisions
audit_logs
settings
api_keys
```

---

# File Storage Architecture

```text
/uploads
    /website-1
        /2026
            /06
                image.webp
```

---

# Caching Architecture

Cache targets:

* Public posts
* Categories
* Tags
* Authors
* Sitemap

Invalidation triggers:

* Post publish
* Post update
* Category update
* Tag update

---

# Security Architecture

Security layers:

```text
HTTPS
JWT
RBAC
Rate Limiting
Input Validation
Prepared Statements
Audit Logs
```

---

# SEO Architecture

Each post supports:

* Meta title
* Meta description
* Canonical URL
* OpenGraph
* Twitter cards
* Schema
* Robots directives

Generated artifacts:

```text
sitemap.xml
robots.txt
rss.xml
```

---

# Scalability Strategy

Current target:

```text
10 websites
100 writers
500,000 articles
```

Future scaling strategy:

```text
Shared Hosting
↓
VPS
↓
Dedicated Database
↓
Object Storage
↓
CDN
```

---

# Deployment Architecture

Environment:

```text
Hostinger Shared Hosting
PHP 8.2
Apache
MySQL
Cron Jobs
```

Cron jobs:

```text
Publish scheduled posts
Generate sitemaps
Cleanup logs
Generate analytics
```

---

# Architectural Constraints

Must support:

* Shared hosting deployment
* No Redis requirement
* No Docker requirement
* No queue workers requirement

Optional enterprise upgrades:

* Redis
* Queue workers
* Elasticsearch
* Object storage
* CDN

---

# Decision Record

## Why PHP

* Shared hosting compatibility
* Low operational cost
* Easy deployment
* Excellent hosting support

## Why Headless CMS

* Multiple websites
* Better frontend flexibility
* Better scaling
* Framework independence

## Why Multi Tenant

* Single backend maintenance
* Lower infrastructure cost
* Easier administration
* Centralized updates
