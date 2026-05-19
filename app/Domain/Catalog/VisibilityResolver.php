<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final class VisibilityResolver
{
    public function isVisible(Product $product, Customer $customer): bool
    {
        if (in_array($product->sku, $customer->contractGrants, true)) {
            return true;
        }

        if (in_array($customer->tier->value, $product->restrictedForTiers, true)) {
            return false;
        }

        return $customer->tier->atLeast($product->minimumTier);
    }
}
