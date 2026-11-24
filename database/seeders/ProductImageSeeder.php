<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductImageSeeder extends Seeder
{
    /** @var array<string, string> */
    public const IMAGES = [
        'Kopi Susu Gula Aren' => '/images/menu/kopi-susu-gula-aren.jpg',
        'Americano' => '/images/menu/americano.jpg',
        'Caffè Latte' => '/images/menu/caffe-latte.jpg',
        'Cappuccino' => '/images/menu/cappuccino.jpg',
        'Espresso' => '/images/menu/espresso.jpg',
        'Vanilla Latte' => '/images/menu/vanilla-latte.jpg',
        'Mocha' => '/images/menu/mocha.jpg',
        'Flat White' => '/images/menu/flat-white.jpg',
        'Matcha Latte' => '/images/menu/matcha-latte.jpg',
        'Chocolate' => '/images/menu/chocolate.jpg',
        'Vanilla Milk' => '/images/menu/vanilla-milk.jpg',
        'Matcha Aren' => '/images/menu/matcha-aren.jpg',
        'Original Tea' => '/images/menu/original-tea.jpg',
        'Milk Tea' => '/images/menu/milk-tea.jpg',
        'Vanilla Tea' => '/images/menu/vanilla-tea.jpg',
        'Chicken Sandwich' => '/images/menu/chicken-sandwich.jpg',
        'Rice Bowl' => '/images/menu/rice-bowl.jpg',
        'Toast' => '/images/menu/toast.jpg',
        'Butter Croissant' => '/images/menu/butter-croissant.jpg',
        'Chocolate Croissant' => '/images/menu/chocolate-croissant.jpg',
    ];

    public function run(): void
    {
        foreach (self::IMAGES as $imageUrl) {
            if (!is_file(public_path(ltrim($imageUrl, '/')))) {
                throw new RuntimeException('Foto menu tidak ditemukan: ' . $imageUrl);
            }
        }

        $updatedProducts = DB::transaction(function (): int {
            $updatedProducts = 0;

            foreach (self::IMAGES as $name => $imageUrl) {
                $updatedProducts += Product::where('name', $name)->update([
                    'image_url' => $imageUrl,
                ]);
            }

            return $updatedProducts;
        });

        $this->command?->info("Foto diperbarui untuk {$updatedProducts} produk.");

        $unmappedNames = Product::whereNotIn('name', array_keys(self::IMAGES))->pluck(
            'name',
        );

        if ($unmappedNames->isNotEmpty()) {
            $this->command?->warn(
                'Produk di luar katalog foto: ' . $unmappedNames->implode(', '),
            );
        }
    }
}
