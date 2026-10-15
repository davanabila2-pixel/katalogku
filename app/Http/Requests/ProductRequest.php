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
        ];
    }
}
