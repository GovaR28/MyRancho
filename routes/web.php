<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\FincaController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\VaccinationController;
use App\Http\Controllers\VentaController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
});

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/dashboard', [FincaController::class, 'dashboard'])->name('dashboard');
    Route::get('/fincas', [FincaController::class, 'index'])->name('fincas.index');
    Route::get('/fincas/create', [FincaController::class, 'create'])->name('fincas.create');
    Route::post('/fincas', [FincaController::class, 'store'])->name('fincas.store');
    Route::get('/fincas/{finca}/edit', [FincaController::class, 'edit'])->name('fincas.edit');
    Route::put('/fincas/{finca}', [FincaController::class, 'update'])->name('fincas.update');
    Route::delete('/fincas/{finca}', [FincaController::class, 'destroy'])->name('fincas.destroy');
    Route::patch('/fincas/{finca}/estado', [FincaController::class, 'toggleStatus'])->name('fincas.status');

    Route::get('/propietarios', [PropietarioController::class, 'index'])->name('propietarios.index');
    Route::get('/propietarios/create', [PropietarioController::class, 'create'])->name('propietarios.create');
    Route::post('/propietarios', [PropietarioController::class, 'store'])->name('propietarios.store');
    Route::get('/propietarios/{propietario}/edit', [PropietarioController::class, 'edit'])->name('propietarios.edit');
    Route::put('/propietarios/{propietario}', [PropietarioController::class, 'update'])->name('propietarios.update');
    Route::patch('/propietarios/{propietario}/estado', [PropietarioController::class, 'toggleStatus'])->name('propietarios.status');

    Route::get('/animales', [AnimalController::class, 'index'])->name('animales.index');
    Route::get('/animales/create', [AnimalController::class, 'create'])->name('animales.create');
    Route::post('/animales', [AnimalController::class, 'store'])->name('animales.store');
    Route::get('/animales/{animal}/edit', [AnimalController::class, 'edit'])->name('animales.edit');
    Route::put('/animales/{animal}', [AnimalController::class, 'update'])->name('animales.update');
    Route::delete('/animales/{animal}', [AnimalController::class, 'destroy'])->name('animales.destroy');

    Route::get('/vacunaciones', [VaccinationController::class, 'index'])->name('vacunaciones.index');
    Route::get('/vacunaciones/create', [VaccinationController::class, 'create'])->name('vacunaciones.create');
    Route::post('/vacunaciones', [VaccinationController::class, 'store'])->name('vacunaciones.store');
    Route::get('/vacunaciones/{vaccination}/edit', [VaccinationController::class, 'edit'])->name('vacunaciones.edit');
    Route::get('/vacunaciones/{vaccination}', [VaccinationController::class, 'show'])->name('vacunaciones.show');
    Route::put('/vacunaciones/{vaccination}', [VaccinationController::class, 'update'])->name('vacunaciones.update');
    Route::delete('/vacunaciones/{vaccination}', [VaccinationController::class, 'destroy'])->name('vacunaciones.destroy');
    Route::get('/lotes', [LoteController::class, 'index'])->name('lotes.index');
    Route::post('/lotes', [LoteController::class, 'store'])->name('lotes.store');
    Route::get('/lotes/{lote}', [LoteController::class, 'show'])->name('lotes.show');
    Route::post('/lotes/{lote}/animales', [LoteController::class, 'addAnimals'])->name('lotes.animales.store');
    Route::delete('/lotes/{lote}/animales/{animal}', [LoteController::class, 'removeAnimal'])->name('lotes.animales.destroy');
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});