<?php

use App\Http\Controllers\Api\AccountingApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ResourceApiController;
use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

/*
| Voltra REST API — autentikasi token via Laravel Sanctum.
| Setiap endpoint terproteksi men-scope data ke tenant milik pengguna.
*/

$resources = [
    'genset', 'pelanggan', 'supplier', 'merek', 'kategori-genset', 'pengguna',
    'suku-cadang', 'transaksi-sewa', 'pembayaran', 'pemeliharaan', 'pengembalian',
    'penjualan-genset', 'jadwal-ketersediaan', 'akun-perkiraan', 'periode',
    'jurnal', 'jadwal-penyusutan',
];

// ---- Publik ----
Route::post('/login', [AuthApiController::class, 'login']);

// ---- Terproteksi (Bearer token) ----
Route::middleware('auth:sanctum')->group(function () use ($resources) {
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // Transaksi operasional (memicu auto-jurnal)
    Route::post('/rental', [TransactionApiController::class, 'storeRental']);
    Route::post('/payment', [TransactionApiController::class, 'storePayment']);
    Route::post('/asset-purchase', [TransactionApiController::class, 'storeAssetPurchase']);
    Route::post('/asset-disposal', [TransactionApiController::class, 'storeDisposal']);
    Route::post('/handover', [TransactionApiController::class, 'storeHandover']);
    Route::post('/maintenance/{id}/complete', [TransactionApiController::class, 'completeMaintenance']);
    Route::post('/opex', [TransactionApiController::class, 'storeOpex']);

    // Akuntansi & laporan
    Route::post('/depreciation/run', [AccountingApiController::class, 'runDepreciation']);
    Route::post('/journal/manual', [AccountingApiController::class, 'storeManualJournal']);
    Route::get('/period/{id}/validate', [AccountingApiController::class, 'validatePeriod']);
    Route::get('/reports/{type}', [AccountingApiController::class, 'report']);
    Route::middleware('role:akuntan,owner')->group(function () {
        Route::post('/period/{id}/close', [AccountingApiController::class, 'closePeriod']);
    });

    // Baca data master & transaksi (generik, tenant-scoped)
    Route::get('/{resource}', [ResourceApiController::class, 'index'])->whereIn('resource', $resources);
    Route::get('/{resource}/{id}', [ResourceApiController::class, 'show'])->whereIn('resource', $resources);
});
