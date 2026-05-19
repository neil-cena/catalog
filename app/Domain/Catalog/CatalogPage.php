<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final readonly class CatalogPage
{
    /**
     * @param array<int, array{product: Product, price: float}> $results
     */
    public function __construct(
        public array $results,
        public int   $total,
        public int   $hidden,
        public int   $page,
        public int   $totalPages,
    ) {}
}
