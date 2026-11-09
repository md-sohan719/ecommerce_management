<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Admins can edit any catalog product; shop owners only the products they created.
     * (Shop owners customise price/details/thumbnail per shop through listings instead.)
     */
    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $this->isCreator($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin() || $this->isCreator($user, $product);
    }

    private function isCreator(User $user, Product $product): bool
    {
        return $product->created_by !== null && (int) $product->created_by === (int) $user->id;
    }
}
