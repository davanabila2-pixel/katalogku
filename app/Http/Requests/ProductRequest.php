<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        if ($product) {
            return $this->user()->can('update', $product);
        }

        $storeId = $this->resolveStoreId();

        return $this->user()->can('create', [Product::class, $storeId]);
    }

    protected function resolveStoreId(): ?int
    {
        if ($this->route('store')) {
            return is_object($this->route('store')) ? $this->route('store')->id : (int) $this->route('store');
        }

        return $this->input('store_id') ? (int) $this->input('store_id') : null;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        $storeId = $this->resolveStoreId()
            ?? $this->route('product')?->store_id;

        return [
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->where('store_id', $storeId)->ignore($productId),
            ],
            'sku' => [
                'nullable', 'string', 'max:50',
                Rule::unique('products', 'sku')->where('store_id', $storeId)->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => [
                'nullable', 'integer', 'exists:categories,id',
                function ($attribute, $value, $fail) use ($storeId) {
                    if (! $value) {
                        return;
                    }
                    $category = Category::find($value);
                    if ($category && $storeId && (int) $category->store_id !== (int) $storeId) {
                        $fail('Kategori harus dari toko yang sama.');
                    }
                    if ($category && ! $category->is_active) {
                        $fail('Kategori tidak aktif tidak dapat dipilih untuk produk baru.');
                    }
                },
            ],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'is_bundle' => ['nullable', 'boolean'],
            'is_hidden_from_catalog' => ['nullable', 'boolean'],
            'bundle_items' => [
                'nullable',
                Rule::requiredIf((bool) $this->input('is_bundle')),
                'array',
                function ($attribute, $value, $fail) use ($storeId, $productId) {
                    if (! $this->input('is_bundle')) {
                        return;
                    }

                    if (! is_array($value) || count($value) < 2) {
                        $fail('Produk setelan (bundle) wajib memiliki minimal 2 produk komponen yang berbeda.');
                        return;
                    }

                    $itemIds = [];
                    foreach ($value as $index => $item) {
                        $itemProductId = $item['product_id'] ?? null;
                        if (! $itemProductId) {
                            $fail('Komponen ke-'.($index + 1).' wajib memilih produk.');
                            continue;
                        }

                        if ($productId && (int) $itemProductId === (int) $productId) {
                            $fail('Produk setelan tidak boleh memuat dirinya sendiri.');
                            continue;
                        }

                        if (in_array($itemProductId, $itemIds, true)) {
                            $fail('Produk komponen dalam setelan tidak boleh duplikat.');
                            continue;
                        }
                        $itemIds[] = $itemProductId;

                        $comp = Product::find($itemProductId);
                        if (! $comp) {
                            $fail("Produk komponen ID {$itemProductId} tidak ditemukan.");
                            continue;
                        }

                        if ($storeId && (int) $comp->store_id !== (int) $storeId) {
                            $fail("Produk '{$comp->name}' bukan milik toko ini.");
                        }

                        if ($comp->isBundle()) {
                            $fail("Produk '{$comp->name}' adalah produk setelan lain. Produk setelan tidak boleh bersarang (nested bundle) agar stok tidak rancu.");
                        }
                    }
                },
            ],
            'bundle_items.*.product_id' => ['required_with:bundle_items', 'integer'],
            'bundle_items.*.quantity' => ['required_with:bundle_items', 'integer', 'min:1'],
        ];
    }
}
