<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Catalog\CatalogQuery;
use App\Domain\Catalog\CatalogService;
use App\Http\Requests\CatalogRequest;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogService $service) {}

    public function index(CatalogRequest $request): JsonResponse
    {
        $query = new CatalogQuery(
            customerId: $request->string('customer')->toString(),
            search:     $request->filled('search') ? $request->string('search')->toString() : null,
            category:   $request->filled('category') ? $request->string('category')->toString() : null,
            page:       (int) $request->input('page', 1),
        );

        $catalogPage = $this->service->search($query);

        $results = array_map(fn (array $item) => [
            'sku'      => $item['product']->sku,
            'name'     => $item['product']->name,
            'category' => $item['product']->category,
            'price'    => round($item['price'], 2),
            'inStock'  => $item['product']->inStock,
        ], $catalogPage->results);

        return response()->json([
            'results'    => $results,
            'total'      => $catalogPage->total,
            'hidden'     => $catalogPage->hidden,
            'page'       => $catalogPage->page,
            'totalPages' => $catalogPage->totalPages,
        ]);
    }
}
