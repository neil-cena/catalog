<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\CatalogQuery;
use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\PriceResolver;
use App\Domain\Catalog\VisibilityResolver;
use App\Repositories\JsonCatalogRepository;
use PHPUnit\Framework\TestCase;

class CatalogServicePaginationTest extends TestCase
{
    private CatalogService $service;

    protected function setUp(): void
    {
        $repo = new JsonCatalogRepository(
            productsPath:  base_path('database/seed-data/products.json'),
            customersPath: base_path('database/seed-data/customers.json'),
        );

        $this->service = new CatalogService($repo, new VisibilityResolver(), new PriceResolver());
    }

    public function test_page_size_is_ten(): void
    {
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 1));

        $this->assertCount(10, $page->results);
    }

    public function test_goldhosp_no_filters_produces_two_pages(): void
    {
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 1));

        $this->assertSame(16, $page->total);
        $this->assertSame(2, $page->totalPages);
    }

    public function test_page_two_has_six_items(): void
    {
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 2));

        $this->assertCount(6, $page->results);
        $this->assertSame(2, $page->page);
    }

    public function test_page_beyond_range_clamps_to_last_page(): void
    {
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 99));

        $this->assertSame(2, $page->page);
        $this->assertCount(6, $page->results);
    }

    public function test_catalog_page_carries_all_required_fields(): void
    {
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 1));

        $this->assertSame(16, $page->total);
        $this->assertSame(0, $page->hidden);
        $this->assertSame(1, $page->page);
        $this->assertSame(2, $page->totalPages);
    }
}
