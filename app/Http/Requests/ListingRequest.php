<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add a catalog product to a shop, or update the shop-specific price,
 * details and thumbnail of an existing listing.
 */
class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shop = $this->route('shop');

        return $shop && $this->user()?->can('manage', $shop);
    }

    public function rules(): array
    {
        $creating = $this->route('listing') === null;

        $rules = [
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_thumbnail' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($creating) {
            $rules['product_id'] = [
                'required',
                'integer',
                Rule::exists('products', 'id'),
                Rule::unique('shop_products', 'product_id')->where('shop_id', $this->route('shop')->id),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'product_id.unique' => 'This product is already listed in this shop.',
        ];
    }
}
