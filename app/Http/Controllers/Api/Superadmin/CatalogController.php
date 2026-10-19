<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Kategori lintas toko (JSON). Filter: store_id, q.
     */
    public function categories(Request $request)
    {
        $categories = Category::query()
            ->with(['store', 'parent'])
            ->withCount(['children', 'products'])
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->latest()
            ->paginate(20);

        return $this->paginated($categories, fn (Category $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'store_name' => $category->store?->name,
            'parent' => $category->parent?->name,
            'children_count' => (int) $category->children_count,
            'products_count' => (int) $category->products_count,
            'is_active' => (bool) $category->is_active,
        ]);
    }

    /**
     * Produk lintas toko (JSON). Filter: store_id, q, status.
     */
    public function products(Request $request)
    {
        $products = Product::query()
            ->with(['store', 'category'])
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return $this->paginated($products, fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'store_name' => $product->store?->name,
            'category' => $product->category?->name,
            'selling_price_formatted' => 'Rp '.number_format((float) $product->selling_price, 0, ',', '.'),
            'stock' => (int) $product->stock,
            'status' => $product->status,
        ]);
    }

    /**
     * Batch lintas toko (JSON). Filter: store_id.
     */
    public function batches(Request $request)
    {
        $batches = PurchaseBatch::query()
            ->with(['store', 'creator'])
            ->withCount('items')
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->latest('purchase_date')
            ->paginate(20);

        return $this->paginated($batches, fn (PurchaseBatch $batch) => [
            'id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'store_name' => $batch->store?->name,
            'purchase_date_formatted' => $batch->purchase_date?->format('d M Y'),
            'items_count' => (int) $batch->items_count,
            'creator_name' => $batch->creator?->name,
            'status' => $batch->status,
        ]);
    }

    /**
     * Pergerakan stok lintas toko (JSON). Filter: store_id.
     */
    public function stock(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['store', 'product', 'creator'])
            ->when($request->store_id, fn ($q, $id) => $q->where('stock_movements.store_id', $id))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return $this->paginated($movements, fn (StockMovement $movement) => [
            'id' => $movement->id,
            'created_at_formatted' => $movement->created_at?->format('d M H:i'),
            'store_name' => $movement->store?->name,
            'product_name' => $movement->product?->name,
            'product_id' => $movement->product_id,
            'movement_type' => $movement->movement_type,
            'quantity' => (int) $movement->quantity,
            'creator_name' => $movement->creator?->name,
        ]);
    }

    /**
     * Audit log lintas toko (JSON). Filter: store_id, action.
     */
    public function audit(Request $request)
    {
        $logs = AuditLog::query()
            ->with(['user', 'store'])
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->action, fn ($q, $action) => $q->where('action', 'like', "%{$action}%"))
            ->latest('created_at')
            ->paginate(25);

        return $this->paginated($logs, fn (AuditLog $log) => [
            'id' => $log->id,
            'created_at_formatted' => $log->created_at?->format('d M H:i'),
            'action' => $log->action,
            'description' => $log->description,
            'store_name' => $log->store?->name,
            'user_name' => $log->user?->name,
        ]);
    }

    /**
     * Bungkus paginator jadi respons JSON standar (data + meta).
     */
    protected function paginated($paginator, callable $map)
    {
        return response()->json([
            'data' => $paginator->getCollection()->map($map)->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
