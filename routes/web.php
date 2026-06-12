<?php

use App\Http\Controllers\Api\AccountingApiController;
use App\Http\Controllers\Api\TransactionApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
| Rute web Voltra.
| Semua halaman dilindungi login; modul keuangan dibatasi per role.
*/

// ---- Autentikasi ----
Route::middleware('guest')->group(function () {
    Route::view('/login', 'pages.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

    Route::get('/pilih-tenant', [AuthController::class, 'showTenantPicker'])->name('tenant.pick');
    Route::post('/pilih-tenant', [AuthController::class, 'pickTenant'])->name('tenant.pick.submit');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Aplikasi (perlu login) ----
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));

    // Operasional & Aset - semua role login
    foreach (['dashboard', 'rental', 'calendar', 'handover', 'maintenance', 'parts',
        'assets', 'disposal', 'invoices', 'customers', 'suppliers', 'brands'] as $page) {
        Route::get('/' . $page, PageController::class)->name($page);
    }

    // Keuangan - RBAC: hanya owner / akuntan / admin
    Route::middleware('role:owner,akuntan,admin')->group(function () {
        foreach (['opex', 'accounting', 'period', 'reports'] as $page) {
            Route::get('/' . $page, PageController::class)->name($page);
        }
    });

    // Master pengguna & tenant - owner / admin
    Route::middleware('role:owner,admin')->group(function () {
        foreach (['users', 'tenant'] as $page) {
            Route::get('/' . $page, PageController::class)->name($page);
        }
    });

    /*
    | Aksi simpan dari form di halaman (memakai sesi login + CSRF).
    */
    Route::prefix('aksi')->group(function () {
        Route::post('/rental', [TransactionApiController::class, 'storeRental']);
        Route::post('/rental/{id}/update', [TransactionApiController::class, 'updateRental']);
        Route::post('/payment', [TransactionApiController::class, 'storePayment']);
        Route::post('/asset-purchase', [TransactionApiController::class, 'storeAssetPurchase']);
        Route::post('/asset-disposal', [TransactionApiController::class, 'storeDisposal']);
        Route::post('/genset/{id}/status', [TransactionApiController::class, 'updateGensetStatus']);
        Route::post('/handover', [TransactionApiController::class, 'storeHandover']);
        Route::post('/maintenance/{id}/update', [TransactionApiController::class, 'updateMaintenance']);
        Route::post('/maintenance/{id}/part', [TransactionApiController::class, 'addPartToMaintenance']);
        Route::post('/maintenance/{id}/complete', [TransactionApiController::class, 'completeMaintenance']);
        Route::post('/opex', [TransactionApiController::class, 'storeOpex']);
        Route::post('/depreciation/run', [AccountingApiController::class, 'runDepreciation']);
        Route::post('/journal/manual', [AccountingApiController::class, 'storeManualJournal']);
        Route::post('/journal/koreksi', [AccountingApiController::class, 'storeKoreksi']);
        Route::post('/journal/{id}/update', [AccountingApiController::class, 'updateJournal']);
        Route::post('/period/{id}/close', [AccountingApiController::class, 'closePeriod'])
            ->middleware('role:akuntan,owner');
        Route::post('/period/{id}/reopen', [AccountingApiController::class, 'reopenPeriod'])
            ->middleware('role:akuntan,owner');

        // Master data & work order
        Route::post('/master/{type}', [MasterController::class, 'store']);
        Route::post('/master/{type}/{id}', [MasterController::class, 'update']);
        Route::post('/master/{type}/{id}/delete', [MasterController::class, 'destroy']);
        Route::post('/maintenance', [MasterController::class, 'storeMaintenance']);
    });
});
