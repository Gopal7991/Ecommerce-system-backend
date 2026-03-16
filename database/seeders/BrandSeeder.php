<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $brands = [

        'Apple', 'Samsung', 'Sony', 'LG', 'Dell',
        'Nike', 'Adidas', 'Zara', 'Gucci', 'Levi\'s'
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(['name' => $brand]);
        }
    }
}
