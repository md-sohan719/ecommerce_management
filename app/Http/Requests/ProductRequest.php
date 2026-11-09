<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / update a catalog product (used by both admins and shop owners).
 * Ownership of existing products is checked with ProductPolicy in the controller.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_thumbnail' => ['sometimes', 'boolean'],
        ];

        // A shop owner creating a product can list it in one of their shops right away.
        if (! $product && $this->user()?->isShopOwner()) {
            $rules['shop_id'] = [
                'nullable',
                'integer',
                Rule::exists('shops', 'id')->where('owner_id', $this->user()->id),
            ];
            $rules['price'] = ['nullable', 'numeric', 'min:0', 'max:9999999999.99'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'shop_id.exists' => 'You can only list products in your own shops.',
        ];
    }
}
