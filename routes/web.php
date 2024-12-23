<?php

// use PDF;

use App\Http\Controllers\ExcelUploadController;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Mpdf\Mpdf;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {   
    $pdf = Pdf::loadView('welcome')->setOption(['defaultPaperSize' => 'a3']);
    return $pdf->stream();
    // return view('welcome');
});

Route::get('/pib', function () {   
    // return view('welcome');
    $pdf = Pdf::loadView('pib')->setOption(['defaultPaperSize' => 'a3']);
    return $pdf->stream();
    // return view('welcome');
});

Route::get('/umum/{type?}', function () {   
    if(request()->type == 'download'){
        $pdf = Pdf::loadView('umum')->setOption(['defaultPaperSize' => 'a3']);
        return $pdf->download();
    }else{
        return view('umum');
    }
});

Route::get('/npe/{type?}', function () {   
    $pdf = Pdf::loadView('npe')->setOption(['defaultPaperSize' => 'a3']);
    if(request()->type == 'download'){
        return $pdf->download();
    }else{
        return $pdf->stream();
    }
});

Route::get('read/excel',[ExcelUploadController::class,"index"]);
Route::post('write/excel',[ExcelUploadController::class,"write"])->name("write.excel");
Route::get('/download/{filename}', [ExcelUploadController::class, 'downloadImage'])->name('image.download');
