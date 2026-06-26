<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class SitemapService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function generateIndex(string $baseUrl): string
    {
        $sitemaps = [
            ['loc' => $baseUrl . '/api/v1/public/sitemap-posts.xml'],
            ['loc' => $baseUrl . '/api/v1/public/sitemap-categories.xml'],
            ['loc' => $baseUrl . '/api/v1/public/sitemap-tags.xml'],
            ['loc' => $baseUrl . '/api/v1/public/sitemap-authors.xml'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sitemap) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars($sitemap['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    public function generatePosts(int $websiteId, string $baseUrl): string
    {
        $stmt = $this->db->prepare(
            'SELECT slug, updated_at FROM posts
             WHERE website_id = ? AND status = "published" AND deleted_at IS NULL
             ORDER BY published_at DESC'
        );
        $stmt->execute([$websiteId]);
        $posts = $stmt->fetchAll();

        return $this->buildUrlSet($posts, $baseUrl, '/blog/', 'weekly', '0.8');
    }

    public function generateCategories(int $websiteId, string $baseUrl): string
    {
        $stmt = $this->db->prepare(
            'SELECT slug, updated_at FROM categories WHERE website_id = ? ORDER BY name ASC'
        );
        $stmt->execute([$websiteId]);
        $categories = $stmt->fetchAll();

        return $this->buildUrlSet($categories, $baseUrl, '/category/', 'weekly', '0.6');
    }

    public function generateTags(int $websiteId, string $baseUrl): string
    {
        $stmt = $this->db->prepare(
            'SELECT slug, updated_at FROM tags WHERE website_id = ? ORDER BY name ASC'
        );
        $stmt->execute([$websiteId]);
        $tags = $stmt->fetchAll();

        return $this->buildUrlSet($tags, $baseUrl, '/tag/', 'monthly', '0.4');
    }

    public function generateAuthors(int $websiteId, string $baseUrl): string
    {
        $stmt = $this->db->prepare(
            'SELECT u.slug, u.updated_at FROM users u
             JOIN posts p ON p.author_id = u.id
             WHERE p.website_id = ? AND p.status = "published" AND p.deleted_at IS NULL
             GROUP BY u.id'
        );
        $stmt->execute([$websiteId]);
        $authors = $stmt->fetchAll();

        return $this->buildUrlSet($authors, $baseUrl, '/author/', 'monthly', '0.5');
    }

    private function buildUrlSet(array $items, string $baseUrl, string $prefix, string $changefreq, string $priority): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($items as $item) {
            $lastmod = $item['updated_at'] ? date('Y-m-d', strtotime($item['updated_at'])) : date('Y-m-d');
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($baseUrl . $prefix . $item['slug']) . "</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
