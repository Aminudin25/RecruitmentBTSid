<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default user
        $user = User::firstOrCreate(
            ['username' => 'admin'],
            ['password' => Hash::make('password123')]
        );

        // Seed sample products
        $products = [
            [
                'title'          => 'Awesome T-Shirt',
                'price'          => 99.99,
                'description'    => 'High-quality cotton t-shirt',
                'category'       => 'Clothes',
                'images'         => ['https://placeimg.com/640/480/any'],
                'created_by'     => $user->username,
                'created_by_id'  => (string) $user->id,
                'updated_by'     => $user->username,
                'updated_by_id'  => (string) $user->id,
            ],
            [
                'title'          => 'Running Shoes',
                'price'          => 149.99,
                'description'    => 'Lightweight running shoes for everyday use',
                'category'       => 'Footwear',
                'images'         => ['https://placeimg.com/640/480/any'],
                'created_by'     => $user->username,
                'created_by_id'  => (string) $user->id,
                'updated_by'     => $user->username,
                'updated_by_id'  => (string) $user->id,
            ],
            [
                'title'          => 'Wireless Headphones',
                'price'          => 299.99,
                'description'    => 'Noise-cancelling wireless headphones',
                'category'       => 'Electronics',
                'images'         => ['https://placeimg.com/640/480/any'],
                'created_by'     => $user->username,
                'created_by_id'  => (string) $user->id,
                'updated_by'     => $user->username,
                'updated_by_id'  => (string) $user->id,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Seeded: 1 user (admin/password123) + 3 products');
    }
}
