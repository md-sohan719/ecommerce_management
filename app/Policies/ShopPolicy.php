<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    /**
     * A shop owner may only manage the listings of shops they own.
     */
    public function manage(User $user, Shop $shop): bool
    {
        return $shop->owner_id !== null && (int) $shop->owner_id === (int) $user->id;
    }
}
