<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\StudyDeckController;
use App\Http\Controllers\FocusSessionController;
use App\Http\Controllers\LessonReadProgressController;

Route::view('/', 'welcome')->name('home');

// We still keep the landing page accessible via /landing just in case
Route::get('/landing', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return redirect()->to(route('home').'#about');
})->name('about');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [StudyController::class, 'dashboard'])->name('dashboard');
    Route::post('/focus-sessions', [FocusSessionController::class, 'store'])->name('focus-sessions.store');
    Route::get('/profile', [StudyController::class, 'profile'])->name('profile');
    Route::patch('/profile', [StudyController::class, 'updateProfile'])->name('profile.update');
    Route::get('/profile/photo', [StudyController::class, 'photo'])->name('profile.photo');
    Route::get('/lessons', [LessonController::class, 'index'])->name('lessons.index');
    Route::post('/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::post('/study-decks', [StudyDeckController::class, 'store'])->name('study-decks.store');
    Route::put('/study-decks/{deck}', [StudyDeckController::class, 'update'])->name('study-decks.update');
    Route::delete('/study-decks/{deck}', [StudyDeckController::class, 'destroy'])->name('study-decks.destroy');
    Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->whereNumber('lesson')->name('lessons.show');
    Route::patch('/lessons/{lesson}', [LessonController::class, 'update'])->whereNumber('lesson')->name('lessons.update');
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->whereNumber('lesson')->name('lessons.destroy');
    Route::get('/lessons/{lesson}/read', [LessonController::class, 'read'])->whereNumber('lesson')->name('lessons.read');
    Route::post('/lessons/{lesson}/read-progress', [LessonReadProgressController::class, 'store'])->whereNumber('lesson')->name('lessons.read-progress');
    Route::get('/lessons/{lesson}/pdf', [LessonController::class, 'download'])->whereNumber('lesson')->name('lessons.download');
});

// Auth Routes
Route::get('/login', function () {
    return view('login');
})->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'loginUser']);

Route::get('/register', function () {
    return view('register');
})->middleware('guest');
Route::post('/register', [AuthController::class, 'registerUser']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/buttons', function () {
    return view('buttons');
});
