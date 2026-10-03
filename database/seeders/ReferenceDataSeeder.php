<?php

namespace Database\Seeders;

use App\Services\Reference\DatasetImporter;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        app(DatasetImporter::class)->run(base_path('data'));
    }
}
