<?php

namespace App\Console\Commands;

use App\Imports\VNZoneImport;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportVNZone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vnzone:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Vietnam zone data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = storage_path('vnzone.xls');

        $this->info('Importing new VN zone ...');

        try {
            Excel::import(new VNZoneImport, $file);
            $this->info('Import completed successfully!');
        } catch (Exception $e) {
            $this->error('Import new VN zone failed');
            if (class_exists('Illuminate\Support\Facades\Log')) {
                Log::error($e);
                Log::error($e->getTraceAsString());
            } else {
                $this->error('Error details: '.$e->getMessage());
            }
        }
    }
}
