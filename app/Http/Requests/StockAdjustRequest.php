<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-1000000', 'max:1000000'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan koreksi wajib diisi untuk audit.',
        ];
    }
}
