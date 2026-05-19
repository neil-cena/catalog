<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final class PriceResolver
{
    public function resolve(Product $product, Customer $customer): float
    {
        if (isset($customer->contractPrices[$product->sku])) {
            return (float) $customer->contractPrices[$product->sku];
        }

        if (isset($product->tierPrices[$customer->tier->value])) {
            return (float) $product->tierPrices[$customer->tier->value];
        }

        return $product->basePrice;
    }
}
