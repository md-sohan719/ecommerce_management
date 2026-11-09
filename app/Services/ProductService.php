<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Create / update catalog products, including thumbnail handling.
 * Shared by the admin panel and the shop owner panel.
 */
class ProductService
{
    private const FIELDS = ['name', 'sku', 'description', 'base_price'];

    public function create(array $data, User $creator, ?UploadedFile $thumbnail = null): Product
    {
        $product = new Product(Arr::only($data, self::FIELDS));
        $product->created_by = $creator->id;

        if ($thumbnail) {
            $product->thumbnail = Media::store($thumbnail, 'products');
        }

        $product->save();

        return $product;
    }

    public function update(Product $product, array $data, ?UploadedFile $thumbnail = null, bool $removeThumbnail = false): Product
    {
        $product->fill(Arr::only($data, self::FIELDS));

        if ($thumbnail) {
            $product->thumbnail = Media::replace($product->thumbnail, $thumbnail, 'products');
        } elseif ($removeThumbnail && $product->thumbnail) {
            Media::delete($product->thumbnail);
            $product->thumbnail = null;
        }

        $product->save();

        return $product;
    }
}
