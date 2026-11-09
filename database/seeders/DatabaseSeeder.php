<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data that exercises every rule of the task:
 *  - products created by the admin AND by shop owners (shared catalog)
 *  - the same catalog product listed by several shops at different prices
 *  - shop-specific title / details / thumbnail overrides
 *  - products that are not assigned to any shop (never visible to customers)
 *  - a hidden listing and an inactive shop (also not visible to customers)
 *
 * All accounts use the password: "password"
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ----- Users ---------------------------------------------------------
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $rahim = $this->owner('Rahim Uddin', 'rahim@example.com');
        $nusrat = $this->owner('Nusrat Jahan', 'nusrat@example.com');
        $karim = $this->owner('Karim Hasan', 'karim@example.com');
        $this->owner('Sadia Akter', 'sadia@example.com'); // owner without a shop yet

        // ----- Shops ---------------------------------------------------------
        $gadget = $this->shop($rahim, 'Gadget House', 'Phones accessories, audio and smart gadgets.', 'House 12, Road 5, Dhanmondi, Dhaka');
        $threads = $this->shop($rahim, 'Urban Threads', 'Everyday clothing and accessories.', 'Level 3, Bashundhara City, Dhaka');
        $grocery = $this->shop($nusrat, 'Green Leaf Grocery', 'Fresh and organic groceries delivered daily.', 'Sector 7, Uttara, Dhaka');
        $daily = $this->shop($karim, 'Daily Needs Store', 'A little bit of everything for your home.', 'GEC Circle, Chattogram');
        $closed = $this->shop(null, 'Old Town Bazaar', 'Temporarily closed (inactive shop – hidden from customers).', 'Lalbagh, Dhaka', false);

        // ----- Shared catalog --------------------------------------------------
        $p = [];
        // created by the admin
        $p['earbuds'] = $this->product($admin, 'Wireless Earbuds Pro', 'ELC-1001', 2500, '🎧', ['#6366f1', '#8b5cf6'], 'True wireless earbuds with active noise cancellation and 24h battery with the charging case.');
        $p['watch'] = $this->product($admin, 'Smart Watch S2', 'ELC-1002', 4200, '⌚', ['#0ea5e9', '#6366f1'], 'Heart-rate, SpO2 and sleep tracking with a 1.8" AMOLED display.');
        $p['powerbank'] = $this->product($admin, 'Power Bank 20000mAh', 'ELC-1003', 1800, '🔋', ['#10b981', '#0ea5e9'], 'Dual USB-A + USB-C PD output, charges two devices at once.');
        $p['speaker'] = $this->product($admin, 'Bluetooth Speaker Mini', 'ELC-1004', 1500, '🔊', ['#f43f5e', '#f97316'], 'Pocket-sized speaker with deep bass and IPX5 water resistance.');
        $p['charger'] = $this->product($admin, 'USB-C Fast Charger 65W', 'ELC-1005', 1200, '🔌', ['#64748b', '#334155'], 'GaN charger for laptops, tablets and phones.');
        $p['tea'] = $this->product($admin, 'Organic Green Tea (100g)', 'GRC-2001', 350, '🍵', ['#22c55e', '#15803d'], 'Hand-picked green tea leaves from Sylhet.');
        $p['rice'] = $this->product($admin, 'Premium Basmati Rice (5kg)', 'GRC-2002', 950, '🍚', ['#f59e0b', '#d97706'], 'Long-grain aged basmati rice.');
        $p['oil'] = $this->product($admin, 'Pure Mustard Oil (1L)', 'GRC-2003', 320, '🫙', ['#eab308', '#ca8a04'], 'Cold-pressed mustard oil.');
        $p['honey'] = $this->product($admin, 'Raw Honey (500g)', 'GRC-2004', 650, '🍯', ['#f59e0b', '#b45309'], 'Natural unprocessed honey.');
        $p['tshirt'] = $this->product($admin, 'Classic Cotton T-Shirt', 'APP-3001', 550, '👕', ['#3b82f6', '#1d4ed8'], '100% combed cotton, regular fit.');
        $p['jacket'] = $this->product($admin, 'Denim Jacket', 'APP-3002', 2200, '🧥', ['#1e40af', '#1e3a8a'], 'Classic blue denim jacket.');
        $p['wallet'] = $this->product($admin, 'Leather Wallet', 'APP-3003', 900, '👛', ['#92400e', '#78350f'], 'Genuine leather bi-fold wallet.');
        // created by the admin but NOT assigned to any shop -> never visible to customers
        $p['sneakers'] = $this->product($admin, 'Running Sneakers', 'APP-3004', 3500, '👟', ['#ec4899', '#be185d'], 'Lightweight running shoes. (Not assigned to any shop – hidden from customers.)');
        $p['mug'] = $this->product($admin, 'Ceramic Coffee Mug', 'HOM-4001', 400, '☕', ['#a16207', '#713f12'], 'A 350ml ceramic mug. (Not assigned to any shop – hidden from customers.)');
        // created by shop owners -> also part of the shared catalog
        $p['mouse'] = $this->product($rahim, 'Gaming Mouse RGB', 'ELC-1006', 1350, '🖱️', ['#a855f7', '#6d28d9'], '7200 DPI gaming mouse with programmable buttons. (Created by shop owner Rahim.)');
        $p['pickle'] = $this->product($nusrat, 'Homemade Mango Pickle (400g)', 'GRC-2005', 280, '🥭', ['#f97316', '#ea580c'], 'Sweet & sour mango pickle made in small batches. (Created by shop owner Nusrat.)');
        $p['bulb'] = $this->product($karim, 'LED Bulb 12W', 'HOM-4002', 180, '💡', ['#facc15', '#eab308'], 'Energy-saving cool daylight LED bulb. (Created by shop owner Karim.)');

        // ----- Listings (shop-specific prices / details / thumbnails) ----------
        $this->list($gadget, $p['earbuds'], 2450, [
            'title' => 'Wireless Earbuds Pro (1 year warranty)',
            'description' => 'Official Gadget House warranty for 12 months. Free delivery inside Dhaka.',
            'thumbnail' => $this->thumbnail('listings', 'gadget-house-earbuds', '🎧', ['#111827', '#4f46e5'], 'Gadget House Edition'),
        ]);
        $this->list($gadget, $p['watch'], 4100);
        $this->list($gadget, $p['powerbank'], 1750);
        $this->list($gadget, $p['speaker'], 1490);
        $this->list($gadget, $p['charger'], 1150);
        $this->list($gadget, $p['mouse'], 1390);

        $this->list($threads, $p['tshirt'], 590, ['description' => 'Available in S, M, L and XL. Free exchange within 7 days.']);
        $this->list($threads, $p['jacket'], 2350);
        $this->list($threads, $p['wallet'], 950);

        $this->list($grocery, $p['tea'], 340);
        $this->list($grocery, $p['rice'], 980);
        $this->list($grocery, $p['oil'], 330);
        $this->list($grocery, $p['honey'], 690, ['title' => 'Sundarbans Raw Honey (500g)']);
        $this->list($grocery, $p['pickle'], 290);

        // Karim's general store re-uses products created by the admin AND by other shop owners
        $this->list($daily, $p['powerbank'], 1850);
        $this->list($daily, $p['charger'], 1250);
        $this->list($daily, $p['rice'], 1000);
        $this->list($daily, $p['oil'], 325);
        $this->list($daily, $p['pickle'], 310);
        $this->list($daily, $p['mouse'], 1450);
        $this->list($daily, $p['bulb'], 180);
        $this->list($daily, $p['speaker'], 1550, ['is_active' => false]); // hidden listing

        // Listings of an inactive shop are not visible to customers either
        $this->list($closed, $p['wallet'], 880);
        $this->list($closed, $p['honey'], 700);

        $this->command?->info('Demo data seeded. Admin: admin@example.com / password, Shop owner: rahim@example.com / password');
    }

    private function owner(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_SHOP_OWNER,
        ]);
    }

    private function shop(?User $owner, string $name, string $description, string $address, bool $active = true): Shop
    {
        return Shop::create([
            'owner_id' => $owner?->id,
            'name' => $name,
            'description' => $description,
            'address' => $address,
            'phone' => '01'.random_int(300000000, 999999999),
            'is_active' => $active,
        ]);
    }

    private function product(User $creator, string $name, string $sku, float $price, string $emoji, array $colors, string $description): Product
    {
        return Product::create([
            'created_by' => $creator->id,
            'name' => $name,
            'sku' => $sku,
            'description' => $description,
            'base_price' => $price,
            'thumbnail' => $this->thumbnail('products', Str::slug($name), $emoji, $colors, $name),
        ]);
    }

    private function list(Shop $shop, Product $product, float $price, array $overrides = []): ShopProduct
    {
        return ShopProduct::create([
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'price' => $price,
            ...$overrides,
        ]);
    }

    /**
     * Writes a simple generated SVG thumbnail to the public disk and returns its path.
     */
    private function thumbnail(string $directory, string $name, string $emoji, array $colors, string $label): string
    {
        [$from, $to] = $colors;
        $label = e(Str::limit($label, 26));

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$from}"/>
      <stop offset="1" stop-color="{$to}"/>
    </linearGradient>
  </defs>
  <rect width="600" height="600" fill="url(#g)"/>
  <circle cx="300" cy="270" r="170" fill="#ffffff" fill-opacity="0.14"/>
  <text x="300" y="275" font-size="190" text-anchor="middle" dominant-baseline="central">{$emoji}</text>
  <text x="300" y="525" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="34" font-weight="600" fill="#ffffff" fill-opacity="0.95" text-anchor="middle">{$label}</text>
</svg>
SVG;

        $path = "{$directory}/seed-{$name}.svg";
        Storage::disk(Media::DISK)->put($path, $svg);

        return $path;
    }
}
