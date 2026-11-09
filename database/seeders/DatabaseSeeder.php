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
        $this->removeOldSeedImages();

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
        $closed = $this->shop(null, 'Old Town Bazaar', 'Traditional goods from old Dhaka. Temporarily closed.', 'Lalbagh, Dhaka', false);

        // ----- Shared catalog --------------------------------------------------
        $p = [];
        // created by the admin
        $p['earbuds'] = $this->product($admin, 'Wireless Earbuds Pro', 'ELC-1001', 2500, 'True wireless earbuds with active noise cancellation and 24h battery with the charging case.');
        $p['watch'] = $this->product($admin, 'Smart Watch S2', 'ELC-1002', 4200, 'Heart-rate, SpO2 and sleep tracking with a 1.8" AMOLED display.');
        $p['powerbank'] = $this->product($admin, 'Power Bank 20000mAh', 'ELC-1003', 1800, 'Dual USB-A + USB-C PD output, charges two devices at once.');
        $p['speaker'] = $this->product($admin, 'Bluetooth Speaker Mini', 'ELC-1004', 1500, 'Pocket-sized speaker with deep bass and IPX5 water resistance.');
        $p['charger'] = $this->product($admin, 'USB-C Fast Charger 65W', 'ELC-1005', 1200, 'GaN charger for laptops, tablets and phones.');
        $p['tea'] = $this->product($admin, 'Organic Green Tea (100g)', 'GRC-2001', 350, 'Hand-picked green tea leaves from Sylhet.');
        $p['rice'] = $this->product($admin, 'Premium Basmati Rice (5kg)', 'GRC-2002', 950, 'Long-grain aged basmati rice.');
        $p['oil'] = $this->product($admin, 'Pure Mustard Oil (1L)', 'GRC-2003', 320, 'Cold-pressed mustard oil.');
        $p['honey'] = $this->product($admin, 'Raw Honey (500g)', 'GRC-2004', 650, 'Natural unprocessed honey.');
        $p['tshirt'] = $this->product($admin, 'Classic Cotton T-Shirt', 'APP-3001', 550, '100% combed cotton, regular fit.');
        $p['jacket'] = $this->product($admin, 'Denim Jacket', 'APP-3002', 2200, 'Classic blue denim jacket.');
        $p['wallet'] = $this->product($admin, 'Leather Wallet', 'APP-3003', 900, 'Genuine leather bi-fold wallet.');
        // created by the admin but NOT assigned to any shop -> never visible to customers
        $p['sneakers'] = $this->product($admin, 'Running Sneakers', 'APP-3004', 3500, 'Lightweight running shoes.');
        $p['mug'] = $this->product($admin, 'Ceramic Coffee Mug', 'HOM-4001', 400, 'A 350ml ceramic mug.');
        // created by shop owners -> also part of the shared catalog
        $p['mouse'] = $this->product($rahim, 'Gaming Mouse RGB', 'ELC-1006', 1350, '7200 DPI gaming mouse with programmable buttons.');
        $p['pickle'] = $this->product($nusrat, 'Homemade Mango Pickle (400g)', 'GRC-2005', 280, 'Sweet & sour mango pickle made in small batches.');
        $p['bulb'] = $this->product($karim, 'LED Bulb 12W', 'HOM-4002', 180, 'Energy-saving cool daylight LED bulb.');

        // ----- Listings (shop-specific prices / details / thumbnails) ----------
        $this->list($gadget, $p['earbuds'], 2450, [
            'title' => 'Wireless Earbuds Pro (1 year warranty)',
            'description' => 'Official Gadget House warranty for 12 months. Free delivery inside Dhaka.',
            'thumbnail' => $this->image('listings', 'listing-gadget-house-earbuds'),
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

    private function product(User $creator, string $name, string $sku, float $price, string $description): Product
    {
        return Product::create([
            'created_by' => $creator->id,
            'name' => $name,
            'sku' => $sku,
            'description' => $description,
            'base_price' => $price,
            'thumbnail' => $this->image('products', Str::slug($name)),
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
     * Copies a demo photo from database/seeders/images to the public disk and
     * returns its path (null if the photo is missing, the UI then shows a placeholder).
     * Photo authors and licenses: database/seeders/images/CREDITS.md
     */
    private function image(string $directory, string $name): ?string
    {
        $source = database_path("seeders/images/{$name}.jpg");

        if (! is_file($source)) {
            return null;
        }

        $path = "{$directory}/seed-{$name}.jpg";
        Storage::disk(Media::DISK)->put($path, file_get_contents($source));

        return $path;
    }

    /**
     * Re-seeding starts from a clean state: remove demo images from earlier runs
     * (uploaded files don't start with "seed-" and are kept).
     */
    private function removeOldSeedImages(): void
    {
        $disk = Storage::disk(Media::DISK);

        foreach (['products', 'listings'] as $directory) {
            foreach ($disk->files($directory) as $file) {
                if (str_starts_with(basename($file), 'seed-')) {
                    $disk->delete($file);
                }
            }
        }
    }
}
