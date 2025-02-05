<?php

use App\Http\Controllers\FileUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//Route::get('')
Route::post('/upload-document',[FileUploadController::class,'store'])->name('upload-document');
Route::get('/generate-document', [FileUploadController::class, 'index'])->name('generate-document');
Route::get('/download-document/{document}', [FileUploadController::class, 'show'])->name('download-document');
