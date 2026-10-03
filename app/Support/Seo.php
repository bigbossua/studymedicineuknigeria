<?php

namespace App\Support;

/**
 * Per-page SEO data. Built by controllers, rendered by partials/seo.blade.php.
 * Keeps metadata, canonical, robots, Open Graph, breadcrumbs and JSON-LD in one object
 * so every template emits the same, complete head.
 */
class Seo
{
    public string $title;
    public string $description;
    public ?string $canonical = null;
    public bool $noindex = false;
    public string $ogType = 'website';
    public ?string $ogImage = null;
    /** @var array<int, array{label:string, url?:string}> */
    public array $breadcrumbs = [];
    /** @var array<int, array<string, mixed>> */
    public array $jsonLd = [];
    public ?string $lastReviewed = null;
    public ?string $intakeYear = null;

    public function __construct(string $title, ?string $description = null)
    {
        $this->title = $title;
        $this->description = $description ?? config('site.default_description');
    }

    public static function make(string $title, ?string $description = null): static
    {
        return new static($title, $description);
    }

    public function canonical(?string $url): static { $this->canonical = $url; return $this; }
    public function noindex(bool $v = true): static { $this->noindex = $v; return $this; }
    public function article(): static { $this->ogType = 'article'; return $this; }
    public function image(?string $path): static { $this->ogImage = $path; return $this; }
    public function reviewed(?string $date, ?string $intakeYear = null): static { $this->lastReviewed = $date; $this->intakeYear = $intakeYear; return $this; }

    /** @param array<int, array{label:string, url?:string}> $crumbs */
    public function breadcrumbs(array $crumbs): static { $this->breadcrumbs = $crumbs; return $this; }

    /** @param array<string, mixed> $schema */
    public function jsonLd(array $schema): static { $this->jsonLd[] = $schema; return $this; }

    public function fullTitle(): string
    {
        $site = config('site.name');
        return str_contains($this->title, $site) ? $this->title : "{$this->title} | {$site}";
    }

    public function resolvedCanonical(): string
    {
        return $this->canonical ?? url()->current();
    }

    public function resolvedImage(): string
    {
        return url($this->ogImage ?? config('site.og_image'));
    }

    /** @return array<int, array<string, mixed>> */
    public function allJsonLd(): array
    {
        $out = [[
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('site.name'),
            'url' => url('/'),
            'logo' => url('/images/brand/smukn-symbol.svg'),
            'email' => config('site.email'),
            'description' => config('site.default_description'),
        ]];
        if ($this->breadcrumbs) {
            $items = [];
            foreach (array_values($this->breadcrumbs) as $i => $c) {
                $item = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['label']];
                if (! empty($c['url'])) $item['item'] = $c['url'];
                $items[] = $item;
            }
            $out[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
        }
        foreach ($this->jsonLd as $s) {
            $out[] = ['@context' => 'https://schema.org'] + $s;
        }
        return $out;
    }
}
