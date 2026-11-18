<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics' => 'Phones, chargers, earphones, laptops, and related devices',
            'ID and Documents' => 'School IDs, identification cards, papers, and documents',
            'Bags and Wallets' => 'Backpacks, purses, wallets, and bags',
            'Keys' => 'Keys and key holders',
            'Clothing and Accessories' => 'Clothing, caps, watches, jewelry, and accessories',
            'Books and School Supplies' => 'Books, notebooks, pens, and other school supplies',
            'Other' => 'Items that do not fit another category',
        ];

        foreach ($categories as $name => $description) {
            Category::firstOrCreate(['name' => $name], ['description' => $description, 'is_active' => true]);
        }
    }
}
