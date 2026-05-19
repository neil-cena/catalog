<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final readonly class Customer
{
    public function __construct(
        public string $id,
        public Tier   $tier,
        public array  $contractGrants,
        public array  $contractPrices,
    ) {}
}
