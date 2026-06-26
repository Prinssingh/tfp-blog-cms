# Coding Standards

# Purpose

This document defines the coding conventions, architectural patterns, and quality requirements for the TFP Multi-Site Headless Blog CMS.

All generated code, manual code, and AI-generated code must follow these standards.

---

# PHP Version

Required:

```text
PHP 8.2+
```

Always use modern PHP features when appropriate.

Allowed:

* Constructor property promotion
* Enums
* Match expressions
* Readonly properties
* Typed properties
* Union types

---

# Standards Compliance

Follow:

* PSR-1
* PSR-4
* PSR-12

---

# File Naming

## Classes

Use:

```text
PascalCase
```

Examples:

```text
PostController.php
AuthService.php
UserRepository.php
CreatePostDTO.php
```

---

## Methods

Use:

```text
camelCase
```

Examples:

```php
createPost()
publishPost()
generateSitemap()
resolveTenant()
```

---

## Variables

Use:

```text
camelCase
```

Examples:

```php
$postTitle
$websiteId
$currentUser
```

---

## Constants

Use:

```text
UPPER_SNAKE_CASE
```

Examples:

```php
MAX_IMAGE_SIZE
DEFAULT_PAGE_SIZE
JWT_EXPIRATION_TIME
```

---

## Database Tables

Use:

```text
snake_case plural
```

Examples:

```text
users
posts
categories
post_tags
audit_logs
```

---

## Database Columns

Use:

```text
snake_case
```

Examples:

```text
website_id
created_at
updated_at
published_at
```

---

# Folder Structure

```text
app/
├── Controllers
├── Services
├── Repositories
├── DTOs
├── Models
├── Middleware
├── Policies
├── Validators
├── Helpers
└── Exceptions
```

Never create folders outside this structure without approval.

---

# Controller Rules

Controllers are thin layers.

Controllers may:

* Validate request data
* Call services
* Return responses

Controllers must never:

* Execute SQL
* Contain business logic
* Access PDO directly
* Handle transactions

Maximum recommended length:

```text
200 lines
```

---

# Service Rules

Services contain business logic.

Services may:

* Execute workflows
* Coordinate repositories
* Handle transactions
* Trigger events

Services must never:

* Read HTTP requests directly
* Access superglobals

Maximum recommended length:

```text
500 lines
```

---

# Repository Rules

Repositories manage persistence.

Repositories may:

* Execute queries
* Build query filters
* Return entities

Repositories must never:

* Validate business rules
* Handle authentication
* Send emails

---

# DTO Rules

Every create and update request must use DTO validation.

Examples:

```text
CreatePostDTO
UpdatePostDTO
CreateUserDTO
UpdateCategoryDTO
```

DTOs must:

* Validate input
* Cast types
* Normalize data

---

# Method Size

Recommended limits:

| Type              | Max Lines |
| ----------------- | --------- |
| Controller Method | 30        |
| Service Method    | 80        |
| Repository Method | 50        |

Refactor when limits are exceeded.

---

# Function Rules

Functions should:

* Perform one task
* Be deterministic where possible
* Avoid side effects

Avoid:

```php
function processEverything()
```

Prefer:

```php
validatePost()
publishPost()
generateSeoMetadata()
```

---

# Dependency Injection

Prefer constructor injection.

Example:

```php
class PostService
{
    public function __construct(
        private PostRepository $postRepository
    ) {}
}
```

Avoid:

```php
new PostRepository();
```

inside business logic.

---

# Type Safety

Always declare parameter types.

Example:

```php
public function createPost(CreatePostDTO $dto): Post
```

Avoid:

```php
public function createPost($data)
```

---

# Return Types

Always declare return types.

Example:

```php
public function findById(int $id): ?Post
```

Avoid:

```php
public function findById($id)
```

---

# Strict Types

Every PHP file begins with:

```php
declare(strict_types=1);
```

---

# Exception Handling

Throw exceptions for exceptional situations.

Examples:

```php
PostNotFoundException
UnauthorizedException
ValidationException
```

Never return false for errors.

Avoid:

```php
return false;
```

Prefer:

```php
throw new PostNotFoundException();
```

---

# Transactions

Use transactions for multi-step operations.

Examples:

* Publish post
* Delete user
* Assign roles

Example:

```php
$this->database->beginTransaction();

try {
    // operations
    $this->database->commit();
} catch (Throwable $e) {
    $this->database->rollBack();
    throw $e;
}
```

---

# Security Rules

Mandatory:

* Prepared statements only
* Password hashing only using password_hash()
* password_verify() for validation
* Validate all user input
* Escape output where required

Forbidden:

* Raw SQL concatenation
* MD5 passwords
* SHA1 passwords

Never do:

```php
$sql = "SELECT * FROM users WHERE email = '$email'";
```

Always do:

```php
$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE email = ?"
);
```

---

# Authentication Rules

Use:

* JWT access token
* Refresh token

Never use:

```text
PHP Sessions
```

---

# Multi Tenant Rules

Every repository query must include:

```php
website_id
```

Example:

```php
SELECT *
FROM posts
WHERE website_id = ?
```

Never expose records from another tenant.

---

# API Response Standards

Success:

```json
{
    "success": true,
    "message": "",
    "data": {}
}
```

Error:

```json
{
    "success": false,
    "message": "",
    "errors": {}
}
```

---

# Logging Rules

Log:

* Login attempts
* Publishing actions
* Permission changes
* User changes

Never log:

* Passwords
* JWT tokens
* Secrets
* API keys

---

# Image Rules

Allowed formats:

* jpg
* jpeg
* png
* webp
* avif

Maximum upload size:

```text
10 MB
```

Generate:

* original
* thumbnail
* webp version

---

# Pagination Standards

Default:

```text
20 items
```

Maximum:

```text
100 items
```

---

# Caching Rules

Cache:

* Public posts
* Categories
* Tags
* Authors
* Sitemap

Always invalidate cache after updates.

---

# Code Review Checklist

Before merging:

* PSR compliant
* Typed methods
* Return types defined
* DTO validation exists
* Tenant isolation verified
* Authorization verified
* Tests pass
* SEO requirements satisfied

---

# Claude Code Rules

When generating code:

1. Follow existing architecture.
2. Never bypass services.
3. Never access PDO from controllers.
4. Never skip tenant filtering.
5. Never introduce breaking API changes.
6. Reuse existing components before creating new ones.
7. Keep Hostinger compatibility.

If multiple implementations are possible:

Choose the solution that is:

* Simpler
* Easier to maintain
* More secure
* Shared hosting compatible
* Easier to scale
