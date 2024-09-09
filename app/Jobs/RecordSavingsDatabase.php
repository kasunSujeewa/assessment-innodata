<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecordSavingsDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;
    protected $jobId;

    public function __construct(array $batch, $jobId)
    {
        $this->batch = $batch;
        $this->jobId = $jobId;
    }

    public function handle()
    {
        $totalRows = count($this->batch);
        $processed = 0;

        foreach ($this->batch as $data) {
            User::create([
                'name' => $data[0],
                'email' => $data[1],
                'contact_no' => $data[2],
                'address' => $data[3],
                //'birthday' => $data[4] ,
            ]);

            $processed++;
            $progress = ($processed / $totalRows) * 100;

            Cache::put("job-progress-{$this->jobId}", $progress);
        }

        Cache::put("job-progress-{$this->jobId}", 100);
    }
}
