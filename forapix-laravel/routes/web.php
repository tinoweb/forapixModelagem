<?php

use App\Http\Controllers\FileServeController;
use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

// Serve arquivos de upload sem depender de storage:link
Route::get('/uploads/{path}', [FileServeController::class, 'serve'])
    ->where('path', '.+')
    ->name('uploads.serve');
