<?php

use App\Http\Controllers\CsvImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/csv-upload',[CsvImportController::class,'importCsv'])->name('csv file upload');

Route::get('/job-progress/{jobId}', [CsvImportController::class, 'getJobProgress'])->name('job-progress');
