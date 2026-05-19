<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Product;
use App\Domain\Catalog\Tier;
use App\Repositories\JsonCatalogRepository;
use PHPUnit\Framework\TestCase;

class JsonCatalogRepositoryTest extends TestCase
{
    private JsonCatalogRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new JsonCatalogRepository(
            productsPath:  base_path('database/seed-data/products.json'),
            customersPath: base_path('database/seed-data/customers.json'),
        );
    }

    public function test_loads_16_products(): void
    {
        $this->assertCount(16, $this->repo->allProducts());
    }

    public function test_all_products_are_product_instances(): void
    {
        foreach ($this->repo->allProducts() as $product) {
            $this->assertInstanceOf(Product::class, $product);
        }
    }

    public function test_null_minimum_tier_deserialises_as_null(): void
    {
        $product = collect($this->repo->allProducts())->firstWhere('sku', 'SAL-100');
        $this->assertNull($product->minimumTier);
    }

    public function test_minimum_tier_deserialises_as_tier_enum(): void
    {
        $product = collect($this->repo->allProducts())->firstWhere('sku', 'SAL-050');
        $this->assertSame(Tier::Silver, $product->minimumTier);
    }

    public function test_finds_all_three_customers(): void
    {
        $this->assertNotNull($this->repo->findCustomer('BRONZECO'));
        $this->assertNotNull($this->repo->findCustomer('SILVERMED'));
        $this->assertNotNull($this->repo->findCustomer('GOLDHOSP'));
    }

    public function test_customer_tier_deserialises_correctly(): void
    {
        $this->assertSame(Tier::Bronze, $this->repo->findCustomer('BRONZECO')->tier);
        $this->assertSame(Tier::Silver, $this->repo->findCustomer('SILVERMED')->tier);
        $this->assertSame(Tier::Gold,   $this->repo->findCustomer('GOLDHOSP')->tier);
    }
}
