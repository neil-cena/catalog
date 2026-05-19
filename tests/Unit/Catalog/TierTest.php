<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Tier;
use PHPUnit\Framework\TestCase;

class TierTest extends TestCase
{
    public function test_gold_satisfies_silver_floor(): void
    {
        $this->assertTrue(Tier::Gold->atLeast(Tier::Silver));
    }

    public function test_silver_satisfies_silver_floor_inclusive(): void
    {
        $this->assertTrue(Tier::Silver->atLeast(Tier::Silver));
    }

    public function test_bronze_does_not_satisfy_silver_floor(): void
    {
        $this->assertFalse(Tier::Bronze->atLeast(Tier::Silver));
    }

    public function test_any_tier_satisfies_null_floor(): void
    {
        $this->assertTrue(Tier::Bronze->atLeast(null));
        $this->assertTrue(Tier::Silver->atLeast(null));
        $this->assertTrue(Tier::Gold->atLeast(null));
    }

    public function test_rank_ordering_is_gold_gt_silver_gt_bronze(): void
    {
        $this->assertGreaterThan(Tier::Silver->rank(), Tier::Gold->rank());
        $this->assertGreaterThan(Tier::Bronze->rank(), Tier::Silver->rank());
    }
}
