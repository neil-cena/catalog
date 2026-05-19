<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Customer;
use App\Domain\Catalog\Product;
use App\Domain\Catalog\Tier;
use App\Domain\Catalog\VisibilityResolver;
use PHPUnit\Framework\TestCase;

class VisibilityResolverTest extends TestCase
{
    private VisibilityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new VisibilityResolver();
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

    public function test_contract_grant_overrides_restricted_for_tiers(): void
    {
        // SCL-10 is restricted for silver+bronze but BRONZECO has a grant
        $product  = $this->makeProduct(['sku' => 'SCL-10', 'restrictedForTiers' => ['silver', 'bronze']]);
        $customer = $this->makeCustomer(['tier' => Tier::Bronze, 'contractGrants' => ['SCL-10']]);

        $this->assertTrue($this->resolver->isVisible($product, $customer));
    }

    public function test_contract_grant_overrides_minimum_tier(): void
    {
        // Bronze customer gets a grant on a gold-minimum product
        $product  = $this->makeProduct(['sku' => 'EQ-LED', 'minimumTier' => Tier::Gold]);
        $customer = $this->makeCustomer(['tier' => Tier::Bronze, 'contractGrants' => ['EQ-LED']]);

        $this->assertTrue($this->resolver->isVisible($product, $customer));
    }

    public function test_tier_in_restricted_list_is_not_visible(): void
    {
        $product  = $this->makeProduct(['restrictedForTiers' => ['bronze']]);
        $customer = $this->makeCustomer(['tier' => Tier::Bronze]);

        $this->assertFalse($this->resolver->isVisible($product, $customer));
    }

    public function test_tier_below_minimum_is_not_visible(): void
    {
        $product  = $this->makeProduct(['minimumTier' => Tier::Silver]);
        $customer = $this->makeCustomer(['tier' => Tier::Bronze]);

        $this->assertFalse($this->resolver->isVisible($product, $customer));
    }

    public function test_tier_equal_to_minimum_is_visible(): void
    {
        $product  = $this->makeProduct(['minimumTier' => Tier::Silver]);
        $customer = $this->makeCustomer(['tier' => Tier::Silver]);

        $this->assertTrue($this->resolver->isVisible($product, $customer));
    }

    public function test_tier_above_minimum_is_visible(): void
    {
        $product  = $this->makeProduct(['minimumTier' => Tier::Silver]);
        $customer = $this->makeCustomer(['tier' => Tier::Gold]);

        $this->assertTrue($this->resolver->isVisible($product, $customer));
    }

    public function test_null_minimum_tier_with_empty_restricted_is_visible(): void
    {
        $product  = $this->makeProduct(['minimumTier' => null, 'restrictedForTiers' => []]);
        $customer = $this->makeCustomer(['tier' => Tier::Bronze]);

        $this->assertTrue($this->resolver->isVisible($product, $customer));
    }

    public function test_sal_irr_is_not_visible_to_silver(): void
    {
        // SAL-IRR has restrictedForTiers: ["silver"]
        $product  = $this->makeProduct(['sku' => 'SAL-IRR', 'restrictedForTiers' => ['silver']]);
        $customer = $this->makeCustomer(['tier' => Tier::Silver]);

        $this->assertFalse($this->resolver->isVisible($product, $customer));
    }
}
