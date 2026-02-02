<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HangmanController;



Route::get('hangman', [HangmanController::class, 'index'])->name('hangman.index');
Route::post('hangman/guess', [HangmanController::class, 'guess'])->name('hangman.guess');
Route::get('hangman/reset', [HangmanController::class, 'reset'])->name('hangman.reset');
Route::get('hangman/hint', [HangmanController::class, 'hint'])->name('hangman.hint');
Route::post('hangman/add', [HangmanController::class, 'add'])->name('hangman.add');
// DÜZELTİLDİ: Artık POST metodu kullanıyor ve URL'den ID beklemiyor
Route::post('/hangman/select', [HangmanController::class, 'selectQuestion'])->name('hangman.select');


Route::post('/hangman/delete', [HangmanController::class, 'deleteQuestion'])->name('hangman.delete');
