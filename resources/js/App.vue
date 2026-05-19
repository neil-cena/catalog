<script setup lang="ts">
import { ref, watch } from 'vue';
import { fetchCatalog, type CatalogResponse } from './api';

const CUSTOMERS = ['BRONZECO', 'SILVERMED', 'GOLDHOSP'];
const CATEGORIES = ['', 'Fluids', 'PPE', 'Devices', 'Wound', 'Equip'];

const customerId = ref(CUSTOMERS[0]);
const search     = ref('');
const category   = ref('');
const page       = ref(1);

const data    = ref<CatalogResponse | null>(null);
const loading = ref(false);
const error   = ref('');

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

async function load() {
    if (!customerId.value) return;
    loading.value = true;
    error.value   = '';
    try {
        data.value = await fetchCatalog({
            customer: customerId.value,
            search:   search.value || undefined,
            category: category.value || undefined,
            page:     page.value,
        });
    } catch (e: any) {
        error.value = e.message ?? 'Unknown error';
    } finally {
        loading.value = false;
    }
}

watch([customerId, category], () => {
    page.value = 1;
    load();
});

watch(search, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        page.value = 1;
        load();
    }, 250);
});

watch(page, load);

load();

function showingFrom(): number {
    if (!data.value || data.value.total === 0) return 0;
    return (data.value.page - 1) * 10 + 1;
}

function showingTo(): number {
    if (!data.value) return 0;
    return (data.value.page - 1) * 10 + data.value.results.length;
}

function formatPrice(p: number): string {
    return '$' + p.toFixed(2);
}
</script>

<template>
    <div style="max-width:900px;margin:2rem auto;font-family:sans-serif;padding:0 1rem">
        <h1 style="margin-bottom:1.5rem">B2B Product Catalog</h1>

        <!-- Controls -->
        <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
            <label style="display:flex;flex-direction:column;gap:4px">
                <span style="font-size:.8rem;font-weight:600">Customer</span>
                <select v-model="customerId" style="padding:.4rem .6rem;border:1px solid #ccc;border-radius:4px">
                    <option v-for="c in CUSTOMERS" :key="c" :value="c">{{ c }}</option>
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:160px">
                <span style="font-size:.8rem;font-weight:600">Search</span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Name or SKU…"
                    style="padding:.4rem .6rem;border:1px solid #ccc;border-radius:4px"
                />
            </label>

            <label style="display:flex;flex-direction:column;gap:4px">
                <span style="font-size:.8rem;font-weight:600">Category</span>
                <select v-model="category" style="padding:.4rem .6rem;border:1px solid #ccc;border-radius:4px">
                    <option value="">All categories</option>
                    <option v-for="cat in CATEGORIES.filter(c => c !== '')" :key="cat" :value="cat">{{ cat }}</option>
                </select>
            </label>
        </div>

        <!-- Status line -->
        <div v-if="data" style="margin-bottom:.75rem;font-size:.9rem;color:#555">
            <template v-if="data.total === 0">
                No results for this account.
            </template>
            <template v-else>
                Showing {{ showingFrom() }}–{{ showingTo() }} of {{ data.total }} &middot; {{ data.hidden }} hidden for this account
            </template>
        </div>

        <!-- Error -->
        <div v-if="error" style="color:red;margin-bottom:.75rem">{{ error }}</div>

        <!-- Loading -->
        <div v-if="loading" style="color:#888;margin-bottom:.75rem">Loading…</div>

        <!-- Results table -->
        <table v-if="data && data.results.length" style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead>
                <tr style="background:#f5f5f5;text-align:left">
                    <th style="padding:.5rem .75rem;border-bottom:2px solid #ddd">SKU</th>
                    <th style="padding:.5rem .75rem;border-bottom:2px solid #ddd">Name</th>
                    <th style="padding:.5rem .75rem;border-bottom:2px solid #ddd">Category</th>
                    <th style="padding:.5rem .75rem;border-bottom:2px solid #ddd;text-align:right">Price</th>
                    <th style="padding:.5rem .75rem;border-bottom:2px solid #ddd;text-align:center">In Stock</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="item in data.results"
                    :key="item.sku"
                    style="border-bottom:1px solid #eee"
                    :style="{ opacity: item.inStock ? 1 : 0.55 }"
                >
                    <td style="padding:.5rem .75rem;font-family:monospace">{{ item.sku }}</td>
                    <td style="padding:.5rem .75rem">{{ item.name }}</td>
                    <td style="padding:.5rem .75rem">{{ item.category }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:600">{{ formatPrice(item.price) }}</td>
                    <td style="padding:.5rem .75rem;text-align:center">{{ item.inStock ? '✓' : '–' }}</td>
                </tr>
            </tbody>
        </table>

        <div v-else-if="data && !loading" style="color:#888;margin-top:1rem">No products match your filters.</div>

        <!-- Pagination -->
        <div v-if="data && data.totalPages > 1" style="display:flex;align-items:center;gap:1rem;margin-top:1rem">
            <button
                @click="page--"
                :disabled="page <= 1"
                style="padding:.4rem .9rem;border:1px solid #ccc;border-radius:4px;cursor:pointer"
                :style="{ opacity: page <= 1 ? 0.4 : 1 }"
            >Prev</button>

            <span style="font-size:.9rem">Page {{ data.page }} of {{ data.totalPages }}</span>

            <button
                @click="page++"
                :disabled="page >= data.totalPages"
                style="padding:.4rem .9rem;border:1px solid #ccc;border-radius:4px;cursor:pointer"
                :style="{ opacity: page >= data.totalPages ? 0.4 : 1 }"
            >Next</button>
        </div>
    </div>
</template>
