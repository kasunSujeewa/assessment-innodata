<?php

namespace App\Http\Controllers;


use App\Jobs\RecordSavingsDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


class CsvImportController extends Controller
{
    public function importCsv(Request $request) 
    {
        
        $request->validate([
            'file' => 'required|file|mimes:csv',
        ]);

        
         $file = $request->file('file');

         $file = $request->file('file');
        
        $jobId = (string) Str::uuid();

        $this->processCsv($file, $jobId);
        
        return response()->json(['success' => 'CSV file is being processed.', 'jobId' => $jobId]);

    }

    protected function processCsv($file, $jobId)
    {
        $batchSize = 1000;
        $batch = [];

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $header = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                $batch[] = $data;

                if (count($batch) >= $batchSize) {
                 
                    RecordSavingsDatabase::dispatch($batch, $jobId);
                    $batch = []; 
                }
            }

            if (count($batch) > 0) {
                RecordSavingsDatabase::dispatch($batch, $jobId);
            }

            fclose($handle);
        }
    }

    public function getJobProgress($jobId)
    {
        $progress = Cache::get("job-progress-{$jobId}", 0);
        return response()->json(['progress' => $progress]);
    }
}
