<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            'Engineering',
            'Design',
            'Marketing',
            'Product',
            'Operations',
        ];

        foreach ($divisions as $name) {
            Division::firstOrCreate(['name' => $name]);
        }
    }
}
