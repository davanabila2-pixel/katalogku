<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\PurchaseBatch;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $storeId = $this->route('store')
            ? (is_object($this->route('store')) ? $this->route('store')->id : (int) $this->route('store'))
            : ($this->input('store_id') ? (int) $this->input('store_id') : null);

        return $this->user()->can('create', [PurchaseBatch::class, $storeId]);
    }

    public function rules(): array
    {
        return [
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
            'batch_code' => ['required', 'string', 'max:50', 'unique:purchase_batches,batch_code'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'additional_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_purchase_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $storeId = $this->route('store')
                ? (is_object($this->route('store')) ? $this->route('store')->id : (int) $this->route('store'))
                : ($this->input('store_id') ? (int) $this->input('store_id') : null);

            if (! $storeId) {
                return;
            }

            foreach ((array) $this->input('items', []) as $index => $item) {
                if (empty($item['product_id'])) {
                    continue;
                }
                $product = Product::find($item['product_id']);
                if ($product && (int) $product->store_id !== (int) $storeId) {
                    $validator->errors()->add("items.{$index}.product_id", 'Produk harus dari toko yang sama dengan batch.');
                }
            }
        });
    }
}
