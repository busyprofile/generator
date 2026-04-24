<?php

namespace Database\Seeders;

use App\Models\TraderCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TraderCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Премиум',
                'description' => 'Трейдеры с высоким рейтингом и стабильностью',
                'is_active' => true,
            ],
            [
                'name' => 'Стандарт',
                'description' => 'Обычные трейдеры со средним рейтингом',
                'is_active' => true,
            ],
            [
                'name' => 'Новички',
                'description' => 'Новые трейдеры с минимальным опытом',
                'is_active' => true,
            ],
            [
                'name' => 'VIP',
                'description' => 'Эксклюзивные трейдеры с максимальными лимитами',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            TraderCategory::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'is_active' => $category['is_active'],
                ]
            );
        }
    }
} 