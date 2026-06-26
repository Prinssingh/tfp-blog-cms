# SEO Specification

# Purpose

This document defines all SEO requirements for the CMS.

Every website, post, category, author page, and tag page must comply with this specification.

---

# SEO Philosophy

SEO is a first-class feature.

SEO requirements must be implemented at:

* Database layer
* API layer
* React frontend layer
* Sitemap generation
* Structured data generation

---

# Supported SEO Entities

SEO metadata can be attached to:

* Posts
* Categories
* Tags
* Authors
* Pages
* Homepage

---

# Required Meta Fields

Every SEO-enabled entity supports:

```text
meta_title
meta_description
canonical_url
robots
focus_keyword
```

---

# Meta Title Rules

Maximum length:

```text
60 characters
```

Recommended format:

```text
Primary Keyword | Brand Name
```

Example:

```text
Best React Practices in 2026 | Tech Blog
```

---

# Meta Description Rules

Maximum length:

```text
160 characters
```

Requirements:

* Include focus keyword
* Human readable
* Encourage click-through

---

# URL Rules

Good:

```text
/blog/best-react-practices-2026
```

Bad:

```text
/post?id=45
```

Requirements:

* Lowercase
* Hyphen separated
* Stable
* Human readable

---

# Canonical URLs

Every public page must have:

```html
<link rel="canonical">
```

Purpose:

* Prevent duplicate content
* Consolidate ranking signals

---

# Robots Directives

Supported values:

```text
index,follow
index,nofollow
noindex,follow
noindex,nofollow
```

Default:

```text
index,follow
```

---

# Open Graph

Required fields:

```text
og_title
og_description
og_image
og_type
```

Default type:

```text
article
```

---

# Twitter Cards

Required fields:

```text
twitter_title
twitter_description
twitter_image
twitter_card
```

Default card type:

```text
summary_large_image
```

---

# Structured Data

Supported schemas:

* BlogPosting
* BreadcrumbList
* FAQPage
* Person
* Organization
* WebSite
* SearchAction

---

# BlogPosting Schema

Required properties:

* headline
* description
* image
* author
* publisher
* datePublished
* dateModified

---

# Breadcrumb Schema

Example:

```text
Home
→ Technology
→ React
→ Best React Practices
```

Every content page must expose breadcrumb schema.

---

# Author Pages

URL:

```text
/author/{slug}
```

Must include:

* Name
* Bio
* Avatar
* Social links
* Published articles

Schema:

```text
Person
```

---

# Category Pages

URL:

```text
/category/{slug}
```

Must include:

* Description
* Meta title
* Meta description
* Featured image

---

# Tag Pages

URL:

```text
/tag/{slug}
```

Must include:

* Description
* Meta title
* Meta description

---

# Sitemap Requirements

Generate:

```text
/sitemap.xml
/sitemap-posts.xml
/sitemap-categories.xml
/sitemap-tags.xml
/sitemap-authors.xml
```

Update after:

* Post publish
* Post delete
* Category update
* Author update

---

# Robots.txt

Example:

```text
User-agent: *
Allow: /

Sitemap: https://example.com/sitemap.xml
```

---

# RSS Feed

Generate:

```text
/rss.xml
```

Include:

* Latest 50 posts
* Published content only

---

# Image SEO

Every image requires:

* alt_text
* title
* caption

Allowed formats:

* webp
* avif
* jpg

Generate:

* Thumbnail
* WebP version

---

# Internal Linking

Support:

* Related posts
* Category links
* Tag links
* Author links

---

# Reading Time

Calculate automatically.

Formula:

```text
Word Count / 200
```

---

# Table of Contents

Generate automatically from:

```text
H2
H3
H4
```

---

# Pagination SEO

Use:

```html
rel="prev"
rel="next"
```

Canonical must point to current page.

---

# Redirect Support

Support:

* 301
* 302

Required for:

* Slug changes
* Category changes

---

# Performance Requirements

Target metrics:

* LCP < 2.5s
* CLS < 0.1
* INP < 200ms

---

# Target Scores

Google Lighthouse:

* SEO: 100
* Performance: 90+
* Accessibility: 90+
* Best Practices: 90+
