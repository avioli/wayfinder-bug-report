<?php

use App\Http\Controllers\WayfinderBugController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => fn() => Features::enabled(Features::registration()),
    'phpVersion' => PHP_VERSION,
    'laravelVersion' => fn() => app()->version(),
    'wayfinderVersion' => fn() => dirname(__DIR__).'/composer.lock'
        |> file_get_contents(...)
        |> json_decode(...)
        |> (fn($obj) => collect($obj->packages)->firstWhere('name', 'laravel/wayfinder'))
        |> (fn($obj) => substr($obj->version, 1))
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::resource('wayfinder-bugs', WayfinderBugController::class);

Route::get('wayfinder-bugs/{wayfinder_bug}/bugfix', [WayfinderBugController::class, 'bugFix'])
    ->name('wayfinder-bugs.bugFix');

require __DIR__.'/settings.php';
