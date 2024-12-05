<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DevController;

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
    return redirect('/dev');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// sql查询
Route::middleware(['auth', 'role:admin'])->match(['get', 'post'], '/dev', [DevController::class, 'index'])->name('dev');
// sql导出excel
Route::middleware(['auth', 'role:admin'])->post('/dev/exportExcel', [DevController::class, 'exportExcel'])->name('dev.exportExcel');
// sql导出json
Route::middleware(['auth', 'role:admin'])->post('/dev/exportJson', [DevController::class, 'exportJson'])->name('dev.exportJson');

require __DIR__.'/auth.php';
