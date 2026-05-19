<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\CatalogQuery;
use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\PriceResolver;
use App\Domain\Catalog\VisibilityResolver;
use App\Repositories\JsonCatalogRepository;
use PHPUnit\Framework\TestCase;

class CatalogServiceFilterTest extends TestCase
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

    public function test_case_insensitive_name_search_matches_saline_products(): void
    {
        // GOLDHOSP sees all; search "SALINE" should match SAL-100, SAL-250, SAL-050, SAL-IRR
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', 'SALINE', null, 1));
        $skus = array_column(array_column($page->results, 'product'), 'sku');

        $this->assertContains('SAL-100', $skus);
        $this->assertContains('SAL-250', $skus);
        $this->assertContains('SAL-050', $skus);
        $this->assertContains('SAL-IRR', $skus);
    }

    public function test_case_insensitive_sku_equals_matches_single_product(): void
    {
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', 'sal-100', null, 1));
        $skus = array_column(array_column($page->results, 'product'), 'sku');

        $this->assertCount(1, $skus);
        $this->assertContains('SAL-100', $skus);
    }

    public function test_category_exact_match_returns_devices(): void
    {
        // GOLDHOSP sees all; Devices = SCL-10, CAN-18, SUT-30
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', null, 'Devices', 1));
        $skus = array_column(array_column($page->results, 'product'), 'sku');

        $this->assertCount(3, $skus);
        $this->assertContains('SCL-10', $skus);
        $this->assertContains('CAN-18', $skus);
        $this->assertContains('SUT-30', $skus);
    }

    public function test_search_and_category_are_applied_as_and(): void
    {
        // "saline" in "Devices" category → no match
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', 'saline', 'Devices', 1));

        $this->assertSame(0, $page->total);
    }

    public function test_total_counts_visible_and_matched_products(): void
    {
        // SILVERMED + saline: SAL-IRR is restricted for silver → hidden
        $page = $this->service->search(new CatalogQuery('SILVERMED', 'saline', null, 1));

        $this->assertSame(3, $page->total);
    }

    public function test_hidden_counts_matched_but_invisible_products(): void
    {
        // SILVERMED + saline: SAL-IRR matches but is restricted for silver
        $page = $this->service->search(new CatalogQuery('SILVERMED', 'saline', null, 1));

        $this->assertSame(1, $page->hidden);
    }

    public function test_hidden_is_scoped_to_matched_products_not_whole_catalog(): void
    {
        // BRONZECO + category=Devices: 3 devices total; SCL-10 is granted, CAN-18+SUT-30 fail minimumTier
        $page = $this->service->search(new CatalogQuery('BRONZECO', null, 'Devices', 1));

        $this->assertSame(1, $page->total);
        $this->assertSame(2, $page->hidden);
    }
}
