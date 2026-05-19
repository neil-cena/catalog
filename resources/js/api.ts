export interface CatalogResult {
    sku: string;
    name: string;
    category: string;
    price: number;
    inStock: boolean;
}

export interface CatalogResponse {
    results: CatalogResult[];
    total: number;
    hidden: number;
    page: number;
    totalPages: number;
}

export interface CatalogParams {
    customer: string;
    search?: string;
    category?: string;
    page?: number;
}

export async function fetchCatalog(params: CatalogParams): Promise<CatalogResponse> {
    const query = new URLSearchParams();
    query.set('customer', params.customer);
    if (params.search)   query.set('search', params.search);
    if (params.category) query.set('category', params.category);
    if (params.page)     query.set('page', String(params.page));

    const res = await fetch(`/api/catalog?${query.toString()}`);
    if (!res.ok) throw new Error(`Request failed: ${res.status}`);
    return res.json();
}
