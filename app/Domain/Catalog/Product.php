<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final readonly class Product
{
    public function __construct(
        public string  $sku,
        public string  $name,
        public string  $category,
        public float   $basePrice,
        public array   $tierPrices,
        public array   $restrictedForTiers,
        public ?Tier   $minimumTier,
        public bool    $inStock,
    ) {}
}
