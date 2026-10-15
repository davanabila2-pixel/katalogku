<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');
        $storeId = $this->resolveStoreId();

        if ($category) {
            return $this->user()->can('update', $category);
        }

        return $this->user()->can('create', [Category::class, $storeId]);
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
        $categoryId = $this->route('category')?->id;
        $storeId = $this->resolveStoreId()
            ?? $this->route('category')?->store_id;

        return [
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->where('store_id', $storeId)->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'parent_id' => [
                'nullable', 'integer', 'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId, $storeId) {
                    if (! $value) {
                        return;
                    }
                    $parent = Category::find($value);
                    if (! $parent) {
                        return;
                    }
                    if ($storeId && (int) $parent->store_id !== (int) $storeId) {
                        $fail('Kategori induk harus dari toko yang sama.');

                        return;
                    }
                    if ($categoryId && (int) $value === (int) $categoryId) {
                        $fail('Kategori tidak boleh menjadi induk bagi dirinya sendiri.');

                        return;
                    }
                    if ($categoryId) {
                        $category = Category::find($categoryId);
                        if ($category && in_array($value, $category->descendantIds(), false)) {
                            $fail('Relasi kategori tidak boleh membentuk siklus.');
                        }
                    }
                    $depth = 2;
                    $p = $parent->parent;
                    while ($p) {
                        $depth++;
                        $p = $p->parent;
                    }
                    if ($depth > Category::MAX_DEPTH) {
                        $fail('Kedalaman kategori maksimal '.Category::MAX_DEPTH.' tingkat.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }
}
