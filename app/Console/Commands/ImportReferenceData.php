<?php

namespace App\Console\Commands;

use App\Services\Reference\DatasetImporter;
use Illuminate\Console\Command;

class ImportReferenceData extends Command
{
    protected $signature = 'reference:import {--path= : Directory containing medical-schools/ and qualifications/}';

    protected $description = 'Import the research datasets (schools, fees, qualification statements) as reference facts with their verification status';

    public function handle(DatasetImporter $importer): int
    {
        $path = $this->option('path') ?: base_path('data');
        $log = $importer->run($path);
        foreach ($log as $line) $this->line($line);
        $this->info(sprintf('Universities: %d · Courses: %d · Facts: %d',
            \App\Models\University::count(), \App\Models\Course::count(), \App\Models\ReferenceFact::count()));

        return self::SUCCESS;
    }
}
