<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Repositories\PublicRepository;
use App\Repositories\WebsiteRepository;
use App\Services\SitemapService;

class PublicController
{
    private PublicRepository $repo;
    private WebsiteRepository $websiteRepo;
    private SitemapService $sitemapService;

    public function __construct()
    {
        $this->repo           = new PublicRepository();
        $this->websiteRepo    = new WebsiteRepository();
        $this->sitemapService = new SitemapService();
    }

    private function resolveWebsiteId(Request $request): int
    {
        $websiteId = (int) $request->query('website_id');

        if ($websiteId > 0) {
            return $websiteId;
        }

        $domain  = $request->header('X-Website-Domain') ?? $request->header('Origin') ?? '';
        $domain  = preg_replace('#^https?://#', '', $domain);
        $website = $this->repo->websiteByDomain($domain);

        if ($website === null) {
            throw new NotFoundException('Website not found.');
        }

        return (int) $website['id'];
    }

    // ── Posts ─────────────────────────────────────────────────────────────────

    public function posts(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $filters   = [
            'page'     => $request->query('page', 1),
            'per_page' => $request->query('per_page', 10),
            'sort'     => $request->query('sort', 'latest'),
            'category' => $request->query('category'),
            'tag'      => $request->query('tag'),
            'author'   => $request->query('author'),
            'search'   => $request->query('search'),
        ];

        $result = $this->repo->posts($websiteId, $filters);

        return Response::paginated(
            $result['posts'],
            $result['total'],
            $result['page'],
            $result['limit'],
        );
    }

    public function post(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $post      = $this->repo->postBySlug($websiteId, $request->param('slug'));

        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        $this->repo->incrementViews((int) $post['id']);

        $related = $this->repo->related($websiteId, (int) $post['id'], (int) $post['category_id']);
        $post['related'] = $related;

        return Response::success($post);
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function categories(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        return Response::success($this->repo->categories($websiteId));
    }

    public function category(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $category  = $this->repo->categoryBySlug($websiteId, $request->param('slug'));

        if ($category === null) {
            throw new NotFoundException('Category not found.');
        }

        return Response::success($category);
    }

    // ── Tags ──────────────────────────────────────────────────────────────────

    public function tags(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        return Response::success($this->repo->tags($websiteId));
    }

    public function tag(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $tag       = $this->repo->tagBySlug($websiteId, $request->param('slug'));

        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }

        return Response::success($tag);
    }

    // ── Authors ───────────────────────────────────────────────────────────────

    public function author(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $author    = $this->repo->authorBySlug($websiteId, $request->param('slug'));

        if ($author === null) {
            throw new NotFoundException('Author not found.');
        }

        return Response::success($author);
    }

    // ── Sitemaps ──────────────────────────────────────────────────────────────

    private function xmlResponse(string $xml): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo $xml;
        exit;
    }

    public function sitemapIndex(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $baseUrl   = $request->query('base_url', '');
        $this->xmlResponse($this->sitemapService->generateIndex($baseUrl));
        return Response::success([]); // never reached
    }

    public function sitemapPosts(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $baseUrl   = $request->query('base_url', '');
        $this->xmlResponse($this->sitemapService->generatePosts($websiteId, $baseUrl));
        return Response::success([]);
    }

    public function sitemapCategories(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $baseUrl   = $request->query('base_url', '');
        $this->xmlResponse($this->sitemapService->generateCategories($websiteId, $baseUrl));
        return Response::success([]);
    }

    public function sitemapTags(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $baseUrl   = $request->query('base_url', '');
        $this->xmlResponse($this->sitemapService->generateTags($websiteId, $baseUrl));
        return Response::success([]);
    }

    public function sitemapAuthors(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $baseUrl   = $request->query('base_url', '');
        $this->xmlResponse($this->sitemapService->generateAuthors($websiteId, $baseUrl));
        return Response::success([]);
    }

    // ── RSS ───────────────────────────────────────────────────────────────────

    public function rss(Request $request): Response
    {
        $websiteId = $this->resolveWebsiteId($request);
        $baseUrl   = $request->query('base_url', '');
        $result    = $this->repo->posts($websiteId, ['per_page' => 20, 'sort' => 'latest']);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= "<channel>\n";
        $xml .= '<atom:link href="' . htmlspecialchars($baseUrl . '/api/v1/public/rss.xml') . '" rel="self" type="application/rss+xml"/>' . "\n";
        $xml .= '<title>Blog Feed</title>' . "\n";
        $xml .= '<link>' . htmlspecialchars($baseUrl) . "</link>\n";
        $xml .= "<description>Latest posts</description>\n";
        $xml .= '<lastBuildDate>' . date(DATE_RSS) . "</lastBuildDate>\n";

        foreach ($result['posts'] as $post) {
            $pubDate = $post['published_at'] ? date(DATE_RSS, strtotime($post['published_at'])) : '';
            $xml .= "<item>\n";
            $xml .= '<title>' . htmlspecialchars($post['title']) . "</title>\n";
            $xml .= '<link>' . htmlspecialchars($baseUrl . '/blog/' . $post['slug']) . "</link>\n";
            $xml .= '<guid isPermaLink="true">' . htmlspecialchars($baseUrl . '/blog/' . $post['slug']) . "</guid>\n";
            $xml .= '<description>' . htmlspecialchars($post['excerpt'] ?? '') . "</description>\n";
            $xml .= "<pubDate>{$pubDate}</pubDate>\n";
            $xml .= "<author>{$post['author_name']}</author>\n";
            $xml .= "<category>{$post['category_name']}</category>\n";
            $xml .= "</item>\n";
        }

        $xml .= "</channel>\n</rss>";

        header('Content-Type: application/rss+xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo $xml;
        exit;
    }

    // ── Robots ────────────────────────────────────────────────────────────────

    public function robots(Request $request): Response
    {
        $baseUrl = $request->query('base_url', '');

        $robots  = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /api/\n\n";
        $robots .= 'Sitemap: ' . $baseUrl . "/api/v1/public/sitemap.xml\n";

        header('Content-Type: text/plain');
        echo $robots;
        exit;
    }
}
