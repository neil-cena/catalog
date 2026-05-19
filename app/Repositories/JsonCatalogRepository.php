<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Catalog\Customer;
use App\Domain\Catalog\Product;
use App\Domain\Catalog\Tier;
use RuntimeException;

final class JsonCatalogRepository
{
    /** @var Product[]|null */
    private ?array $products = null;

    /** @var array<string, Customer>|null */
    private ?array $customers = null;

    public function __construct(
        private readonly string $productsPath,
        private readonly string $customersPath,
    ) {}

    /** @return Product[] */
    public function allProducts(): array
    {
        if ($this->products === null) {
            $this->products = $this->loadProducts();
        }

        return $this->products;
    }

    public function findCustomer(string $id): Customer
    {
        if ($this->customers === null) {
            $this->customers = $this->loadCustomers();
        }

        if (!isset($this->customers[$id])) {
            throw new \InvalidArgumentException("Customer not found: {$id}");
        }

        return $this->customers[$id];
    }

    /** @return Product[] */
    private function loadProducts(): array
    {
        $data = $this->readJson($this->productsPath);

        return array_map(function (array $row): Product {
            return new Product(
                sku:                $row['sku'],
                name:               $row['name'],
                category:           $row['category'],
                basePrice:          (float) $row['basePrice'],
                tierPrices:         (array) ($row['tierPrices'] ?? []),
                restrictedForTiers: (array) ($row['restrictedForTiers'] ?? []),
                minimumTier:        $row['minimumTier'] !== null ? Tier::from($row['minimumTier']) : null,
                inStock:            (bool) $row['inStock'],
            );
        }, $data);
    }

    /** @return array<string, Customer> */
    private function loadCustomers(): array
    {
        $data   = $this->readJson($this->customersPath);
        $result = [];

        foreach ($data as $row) {
            $customer = new Customer(
                id:             $row['id'],
                tier:           Tier::from($row['tier']),
                contractGrants: (array) ($row['contractGrants'] ?? []),
                contractPrices: (array) ($row['contractPrices'] ?? []),
            );
            $result[$customer->id] = $customer;
        }

        return $result;
    }

    private function readJson(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Seed file not found: {$path}");
        }

        $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException("Invalid JSON array in: {$path}");
        }

        return $decoded;
    }
}
