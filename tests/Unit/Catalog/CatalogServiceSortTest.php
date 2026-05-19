<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\CatalogQuery;
use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\PriceResolver;
use App\Domain\Catalog\VisibilityResolver;
use App\Repositories\JsonCatalogRepository;
use PHPUnit\Framework\TestCase;

class CatalogServiceSortTest extends TestCase
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

    public function test_in_stock_items_come_before_out_of_stock(): void
    {
        // GOLDHOSP sees all; GLV-LAT and DRS-GAU are out of stock
        $page    = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 1));
        $results = $page->results;

        $inStockValues = array_column(array_column($results, 'product'), 'inStock');

        // Find the first false
        $firstFalse = array_search(false, $inStockValues, true);
        $lastTrue   = array_key_last(array_filter($inStockValues, fn ($v) => $v === true));

        $this->assertGreaterThan($lastTrue, $firstFalse, 'All in-stock items should precede out-of-stock items');
    }

    public function test_without_search_first_result_for_goldhosp_is_scl10(): void
    {
        // Cheapest in-stock item: SCL-10 at 0.95
        $page = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 1));

        $this->assertSame('SCL-10', $page->results[0]['product']->sku);
    }

    public function test_with_search_starts_with_group_precedes_contains_group(): void
    {
        // SILVERMED + saline: "Saline…" (SAL-250, SAL-050) start with "saline"; "Sterile Saline" (SAL-100) only contains
        $page = $this->service->search(new CatalogQuery('SILVERMED', 'saline', null, 1));
        $skus = array_column(array_column($page->results, 'product'), 'sku');

        $this->assertSame(['SAL-250', 'SAL-050', 'SAL-100'], $skus);
    }

    public function test_within_starts_with_group_price_ascending_applies(): void
    {
        // SAL-250 (2.50) should come before SAL-050 (5.40) — both start with "saline"
        $page    = $this->service->search(new CatalogQuery('SILVERMED', 'saline', null, 1));
        $results = $page->results;

        $sal250Index = array_search('SAL-250', array_column(array_column($results, 'product'), 'sku'), true);
        $sal050Index = array_search('SAL-050', array_column(array_column($results, 'product'), 'sku'), true);

        $this->assertLessThan($sal050Index, $sal250Index);
    }

    public function test_sku_ascending_is_final_tiebreak(): void
    {
        // GOLDHOSP, no filters — for any products with equal price+inStock, sku should be ascending
        $page    = $this->service->search(new CatalogQuery('GOLDHOSP', null, null, 1));
        $results = $page->results;

        for ($i = 0; $i < count($results) - 1; $i++) {
            $curr = $results[$i];
            $next = $results[$i + 1];

            if ($curr['product']->inStock === $next['product']->inStock
                && $curr['price'] === $next['price']) {
                $this->assertLessThan(
                    0,
                    strcmp($curr['product']->sku, $next['product']->sku),
                    "SKU tiebreak violated: {$curr['product']->sku} should come before {$next['product']->sku}"
                );
            }
        }
    }
}
