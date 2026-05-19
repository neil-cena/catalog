<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Repositories\JsonCatalogRepository;

final class CatalogService
{
    private const PAGE_SIZE = 10;

    public function __construct(
        private readonly JsonCatalogRepository $repo,
        private readonly VisibilityResolver    $visibility,
        private readonly PriceResolver         $price,
    ) {}

    public function search(CatalogQuery $query): CatalogPage
    {
        $customer = $this->repo->findCustomer($query->customerId);

        $allProducts = $this->repo->allProducts();

        $matched        = $this->applyFilters($allProducts, $query);
        $visibleMatched = [];
        $hiddenCount    = 0;

        foreach ($matched as $product) {
            if ($this->visibility->isVisible($product, $customer)) {
                $visibleMatched[] = $product;
            } else {
                $hiddenCount++;
            }
        }

        $priced = array_map(
            fn (Product $p) => ['product' => $p, 'price' => $this->price->resolve($p, $customer)],
            $visibleMatched,
        );

        usort($priced, $this->buildComparator($query->search));

        $total      = count($priced);
        $totalPages = (int) ceil($total / self::PAGE_SIZE);
        $totalPages = max(1, $totalPages);

        $page   = max(1, min($query->page, $totalPages));
        $offset = ($page - 1) * self::PAGE_SIZE;
        $slice  = array_slice($priced, $offset, self::PAGE_SIZE);

        return new CatalogPage(
            results:    $slice,
            total:      $total,
            hidden:     $hiddenCount,
            page:       $page,
            totalPages: $totalPages,
        );
    }

    /**
     * @param Product[] $products
     * @return Product[]
     */
    private function applyFilters(array $products, CatalogQuery $query): array
    {
        return array_values(array_filter($products, function (Product $p) use ($query): bool {
            if ($query->search !== null && $query->search !== '') {
                $term        = mb_strtolower($query->search);
                $nameMatches = str_contains(mb_strtolower($p->name), $term);
                $skuMatches  = mb_strtolower($p->sku) === $term;

                if (!$nameMatches && !$skuMatches) {
                    return false;
                }
            }

            if ($query->category !== null && $query->category !== '') {
                if ($p->category !== $query->category) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function buildComparator(?string $search): \Closure
    {
        return function (array $a, array $b) use ($search): int {
            $pa = $a['product'];
            $pb = $b['product'];

            if ($pa->inStock !== $pb->inStock) {
                return $pa->inStock ? -1 : 1;
            }

            if ($search !== null && $search !== '') {
                $term  = mb_strtolower($search);
                $aStarts = str_starts_with(mb_strtolower($pa->name), $term);
                $bStarts = str_starts_with(mb_strtolower($pb->name), $term);

                if ($aStarts !== $bStarts) {
                    return $aStarts ? -1 : 1;
                }
            }

            if ($a['price'] !== $b['price']) {
                return $a['price'] <=> $b['price'];
            }

            return $pa->sku <=> $pb->sku;
        };
    }
}
