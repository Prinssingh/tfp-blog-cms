<?php

declare(strict_types=1);

namespace App\Services;

class SchemaService
{
    public function blogPosting(array $post, array $website, string $baseUrl): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BlogPosting',
            'headline'        => $post['title'],
            'description'     => $post['excerpt'] ?? '',
            'image'           => $post['featured_image'] ? $baseUrl . $post['featured_image'] : null,
            'datePublished'   => $post['published_at'] ?? $post['created_at'],
            'dateModified'    => $post['updated_at'],
            'author'          => [
                '@type' => 'Person',
                'name'  => $post['author_name'],
                'url'   => $baseUrl . '/author/' . $post['author_slug'],
            ],
            'publisher'       => $this->organization($website, $baseUrl),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $baseUrl . '/blog/' . $post['slug'],
            ],
            'articleSection'  => $post['category_name'] ?? null,
            'wordCount'       => str_word_count(strip_tags($post['content'] ?? '')),
            'timeRequired'    => 'PT' . ($post['reading_time'] ?? 1) . 'M',
        ];
    }

    public function breadcrumbs(array $items, string $baseUrl): array
    {
        $listItems = [];
        foreach ($items as $position => $item) {
            $listItems[] = [
                '@type'    => 'ListItem',
                'position' => $position + 1,
                'name'     => $item['name'],
                'item'     => $baseUrl . ($item['url'] ?? ''),
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }

    public function organization(array $website, string $baseUrl): array
    {
        return [
            '@type' => 'Organization',
            'name'  => $website['name'],
            'url'   => $baseUrl,
            'logo'  => $website['logo'] ? $baseUrl . $website['logo'] : null,
        ];
    }

    public function webSite(array $website, string $baseUrl): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => $website['name'],
            'url'             => $baseUrl,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => $baseUrl . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function person(array $user, string $baseUrl): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'Person',
            'name'       => $user['name'],
            'url'        => $baseUrl . '/author/' . $user['slug'],
            'image'      => $user['avatar'] ?? null,
            'description'=> $user['bio'] ?? null,
        ];
    }

    public function faqPage(array $faqs): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(fn($faq) => [
                '@type'          => 'Question',
                'name'           => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $faq['answer'],
                ],
            ], $faqs),
        ];
    }
}
