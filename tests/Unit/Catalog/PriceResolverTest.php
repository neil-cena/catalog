<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Customer;
use App\Domain\Catalog\PriceResolver;
use App\Domain\Catalog\Product;
use App\Domain\Catalog\Tier;
use PHPUnit\Framework\TestCase;

class PriceResolverTest extends TestCase
{
    private PriceResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PriceResolver();
    }

    private function makeProduct(array $overrides = []): Product
    {
        return new Product(
            sku:                $overrides['sku']                ?? 'TEST-SKU',
            name:               $overrides['name']               ?? 'Test Product',
            category:           $overrides['category']           ?? 'General',
            basePrice:          $overrides['basePrice']          ?? 10.00,
            tierPrices:         $overrides['tierPrices']         ?? [],
            restrictedForTiers: $overrides['restrictedForTiers'] ?? [],
            minimumTier:        $overrides['minimumTier']        ?? null,
            inStock:            $overrides['inStock']            ?? true,
        );
    }

    private function makeCustomer(array $overrides = []): Customer
    {
        return new Customer(
            id:             $overrides['id']             ?? 'TEST-CUST',
            tier:           $overrides['tier']           ?? Tier::Silver,
            contractGrants: $overrides['contractGrants'] ?? [],
            contractPrices: $overrides['contractPrices'] ?? [],
        );
    }

    public function test_contract_price_wins_over_base_price(): void
    {
        // BRONZECO has contractPrice SAL-100 = 3.50, basePrice = 4.10
        $product  = $this->makeProduct(['sku' => 'SAL-100', 'basePrice' => 4.10]);
        $customer = $this->makeCustomer(['tier' => Tier::Bronze, 'contractPrices' => ['SAL-100' => 3.50]]);

        $this->assertSame(3.50, $this->resolver->resolve($product, $customer));
    }

    public function test_tier_price_wins_when_no_contract_price(): void
    {
        // SILVERMED + SAL-050: tierPrices.silver = 5.40
        $product  = $this->makeProduct(['sku' => 'SAL-050', 'basePrice' => 6.00, 'tierPrices' => ['silver' => 5.40, 'gold' => 5.00]]);
        $customer = $this->makeCustomer(['tier' => Tier::Silver]);

        $this->assertSame(5.40, $this->resolver->resolve($product, $customer));
    }

    public function test_base_price_used_when_no_contract_or_tier_price(): void
    {
        // SILVERMED + SAL-100: no silver tier price, no contract price → 4.10
        $product  = $this->makeProduct(['sku' => 'SAL-100', 'basePrice' => 4.10, 'tierPrices' => ['gold' => 3.80]]);
        $customer = $this->makeCustomer(['tier' => Tier::Silver]);

        $this->assertSame(4.10, $this->resolver->resolve($product, $customer));
    }

    public function test_contract_price_wins_even_when_tier_price_also_exists(): void
    {
        // Synthetic: customer has contract price AND there's a tier price — contract must win
        $product  = $this->makeProduct(['sku' => 'GLV-NIT', 'basePrice' => 12.50, 'tierPrices' => ['gold' => 11.00]]);
        $customer = $this->makeCustomer(['tier' => Tier::Gold, 'contractPrices' => ['GLV-NIT' => 10.00]]);

        $this->assertSame(10.00, $this->resolver->resolve($product, $customer));
    }

    public function test_tier_price_is_for_customers_tier_not_any_tier(): void
    {
        // SILVERMED + GLV-LAT: tierPrices only has bronze = 9.50, silver has no entry → falls to basePrice 9.95
        $product  = $this->makeProduct(['sku' => 'GLV-LAT', 'basePrice' => 9.95, 'tierPrices' => ['bronze' => 9.50]]);
        $customer = $this->makeCustomer(['tier' => Tier::Silver]);

        $this->assertSame(9.95, $this->resolver->resolve($product, $customer));
    }
}
