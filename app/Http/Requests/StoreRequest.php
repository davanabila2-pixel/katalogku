<?php

namespace App\Http\Requests;

use App\Services\SlugService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');

        return $this->user()->can($store ? 'update' : 'create', $store ?? \App\Models\Store::class);
    }

    public function rules(): array
    {
        $storeId = $this->route('store')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('stores', 'slug')->ignore($storeId),
                function ($attribute, $value, $fail) {
                    if ($value && SlugService::isReserved(strtolower($value))) {
                        $fail('Slug ini dicadangkan untuk URL sistem.');
                    }
                    if ($this->route('store') && $value !== $this->route('store')->slug
                        && ! $this->user()->can('updateSlug', $this->route('store'))) {
                        $fail('Hanya admin utama atau Superadmin yang boleh mengubah slug.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_information' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'qr_account_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'admin_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
