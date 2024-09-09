<?php

namespace App\Http\Controllers;


use App\Jobs\RecordSavingsDatabase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class CsvImportController extends Controller
{
    public function importCsv(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->messages()['file'][0]], 422);
        }

        $file = $request->file('file');

        $expectedHeaders = ['Name', 'Email', 'Contact No','Address'];

        if(!$this->checkHeders($file,$expectedHeaders)){
            return response()->json(['errors' => 'Invalid CSV headers. Please make sure the headers are: ' . implode(', ', $expectedHeaders)], 422);
        }

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

public function checkHeders($file,$expectedHeaders) 
{
    if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
       
        $header = fgetcsv($handle);
       
        if ($header !== $expectedHeaders) {
            fclose($handle); 
            return false;
        }
        return true;
    }
}

    public function getJobProgress($jobId)
    {
        $progress = Cache::get("job-progress-{$jobId}", 0);
        return response()->json(['progress' => $progress]);
    }

    public function getData()
    {
        $users = User::select(['id', 'name', 'email', 'contact_no', 'address', 'created_at']);

        return DataTables::of($users)
            ->addIndexColumn()
            ->make(true);
    }
}
