<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final readonly class CatalogQuery
{
    public function __construct(
        public string  $customerId,
        public ?string $search,
        public ?string $category,
        public int     $page,
    ) {}
}
