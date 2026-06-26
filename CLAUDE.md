# Project

TFP Multi-Site Headless Blog CMS

## Project Vision

Build a production-grade, API-first, multi-tenant headless CMS using PHP and MySQL that can power multiple React websites from a single backend instance.

The system must support:

* Multiple websites
* Multiple writers and editors
* Enterprise SEO
* High performance
* Shared hosting deployment
* API-first architecture
* Scalability

---

# Core Principles

1. API First
2. Multi Tenant
3. SEO First
4. Security First
5. Performance First
6. Shared Hosting Compatible
7. Maintainable Architecture

---

# Technology Stack

## Backend

* PHP 8.3
* MySQL 8+
* Composer
* JWT Authentication
* PDO
* Apache

## Frontend

* React
* Next.js
* TailwindCSS

## Hosting

* Hostinger Shared Hosting

---

# Architecture

React Website
↓
Public REST API
↓
Application Services
↓
Repositories
↓
MySQL Database

Business logic must never exist inside controllers.

---

# Multi Tenant Rules

Every business entity must contain:

```text
website_id
```

Examples:

```text
posts.website_id
categories.website_id
tags.website_id
media.website_id
users.website_id
settings.website_id
menus.website_id
redirects.website_id
```

Every query involving tenant data must filter by website_id.

Never return data from another tenant.

---

# User Roles

## Super Admin

Access to everything.

## Website Admin

Access only to assigned website.

## Editor

Can:

* Review posts
* Edit posts
* Publish posts
* Manage categories

## Writer

Can:

* Create posts
* Edit own posts
* Upload media

Cannot publish content.

---

# Project Structure

```text
app/
├── Controllers
├── Services
├── Repositories
├── Models
├── DTOs
├── Middleware
├── Policies
├── Helpers
├── Exceptions
└── Validators

config/
database/
routes/
storage/
uploads/
public/
```

---

# Architectural Rules

## Controllers

Controllers only:

* Validate request
* Call services
* Return response

No business logic.

---

## Services

Services contain:

* Business rules
* Transactions
* Workflows

---

## Repositories

Repositories contain:

* Database access
* Query logic

Repositories never contain business logic.

---

## DTO Rules

Every create and update request must use DTO validation.

Example:

```text
CreatePostDTO
UpdatePostDTO
CreateUserDTO
```

---

# Database Standards

## Naming

Use snake_case.

Example:

```text
created_at
updated_at
published_at
website_id
```

---

## Primary Keys

Use:

```text
BIGINT UNSIGNED AUTO_INCREMENT
```

---

## Foreign Keys

Always create foreign key relationships.

---

## Soft Delete

Use:

```text
deleted_at
```

for recoverable entities.

---

# API Standards

## Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

## Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

---

# Authentication

Use:

* JWT Access Token
* Refresh Token

Never use PHP sessions.

---

# Security Standards

Mandatory:

* Prepared statements
* Password hashing
* Rate limiting
* CORS validation
* XSS protection
* CSRF protection where applicable
* Audit logging

Never concatenate SQL strings.

---

# Password Rules

Use:

```php
password_hash()
password_verify()
```

Never implement custom hashing.

---

# SEO Requirements

Every post must support:

* Meta Title
* Meta Description
* Canonical URL
* OpenGraph
* Twitter Cards
* Robots Directive
* Focus Keyword
* Structured Data

---

# Required Schema Support

* BlogPosting
* BreadcrumbList
* FAQPage
* Person
* Organization

---

# Sitemap Requirements

Generate:

```text
/sitemap.xml
/sitemap-posts.xml
/sitemap-categories.xml
/sitemap-tags.xml
```

Automatically update sitemaps after publishing content.

---

# URL Structure

Posts:

```text
/blog/{slug}
```

Categories:

```text
/category/{slug}
```

Tags:

```text
/tag/{slug}
```

Authors:

```text
/author/{slug}
```

Never expose numeric IDs publicly.

---

# Media Rules

Store:

* alt_text
* caption
* title

Generate:

* Original image
* WebP version
* Thumbnail

---

# Caching Rules

Cache:

* Public posts
* Categories
* Tags
* Authors
* Sitemaps

Cache invalidation must happen automatically after updates.

---

# Coding Standards

* PSR-12
* Strict typing
* Dependency Injection where possible
* Small functions
* Single Responsibility Principle

---

# Logging

Log:

* Authentication events
* Failed logins
* Publishing actions
* User changes
* Permission changes

---

# Development Rules

Before creating a new module:

1. Create database schema.
2. Create DTOs.
3. Create repository.
4. Create service.
5. Create controller.
6. Create routes.
7. Create tests.

Follow this order consistently.

---

# Feature Development Order

Phase 1:
Infrastructure

Phase 2:
Authentication

Phase 3:
RBAC

Phase 4:
Website Management

Phase 5:
User Management

Phase 6:
Categories and Tags

Phase 7:
Media Library

Phase 8:
Post Management

Phase 9:
SEO Engine

Phase 10:
Public APIs

Phase 11:
Caching

Phase 12:
Analytics

Phase 13:
Deployment

---

# Claude Code Instructions

When implementing features:

* Always follow existing architecture.
* Never introduce new patterns without justification.
* Reuse services when possible.
* Avoid duplication.
* Prefer composition over inheritance.
* Maintain backward compatibility for public APIs.
* Keep code Hostinger compatible.

Before generating code:

1. Verify module dependencies.
2. Verify tenant isolation.
3. Verify authorization rules.
4. Verify SEO requirements.
5. Verify API consistency.

If multiple implementation options exist:

Choose the solution that is:

* Simpler
* Easier to maintain
* Shared hosting compatible
* Easier to scale
