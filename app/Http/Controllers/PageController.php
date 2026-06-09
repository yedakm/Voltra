<?php

namespace App\Http\Controllers;

use App\Support\VoltraData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Single entry point for every Voltra ERP page.
 *
 * The active route name decides which Blade view to render, its page title and
 * breadcrumb (ported from the prototype's app.jsx `titles` map). All views
 * receive the shared mock dataset as `$d`.
 */
class PageController extends Controller
{
    /**
     * route name => [view, page title, [breadcrumb...], extra view data].
     *
     * @var array<string, array{0:string,1:string,2:array<int,string>,3?:array<string,mixed>}>
     */
    protected array $map = [
        'dashboard'  => ['dashboard', 'Dashboard', ['Home', 'Dashboard']],
        'rental'     => ['rental', 'Penyewaan', ['Operasional', 'Penyewaan']],
        'calendar'   => ['calendar', 'Kalender', ['Operasional', 'Kalender']],
        'handover'   => ['handover', 'Serah-Terima', ['Operasional', 'Serah-Terima']],
        'maintenance'=> ['maintenance', 'Pemeliharaan', ['Operasional', 'Pemeliharaan'], ['sub' => 'pemeliharaan']],
        'parts'      => ['maintenance', 'Suku Cadang', ['Aset & Inventaris', 'Suku Cadang'], ['sub' => 'parts']],
        'assets'     => ['assets', 'Genset', ['Aset & Inventaris', 'Genset']],
        'disposal'   => ['disposal', 'Pelepasan Aset', ['Aset & Inventaris', 'Pelepasan Aset']],
        'invoices'   => ['invoice', 'Invoice & Bayar', ['Keuangan', 'Invoice & Pembayaran']],
        'opex'       => ['opex', 'Beban Operasional', ['Keuangan', 'Beban Operasional']],
        'accounting' => ['accounting', 'Jurnal & COA', ['Keuangan', 'Jurnal & COA']],
        'period'     => ['period', 'Tutup Buku', ['Keuangan', 'Tutup Buku Periode']],
        'reports'    => ['reports', 'Laporan', ['Keuangan', 'Laporan']],
        'customers'  => ['master', 'Pelanggan', ['Master Data', 'Pelanggan'], ['kind' => 'customers']],
        'suppliers'  => ['master', 'Supplier', ['Master Data', 'Supplier'], ['kind' => 'suppliers']],
        'brands'     => ['master', 'Merek', ['Master Data', 'Merek'], ['kind' => 'brands']],
        'users'      => ['master', 'Pengguna', ['Master Data', 'Pengguna'], ['kind' => 'users']],
        'tenant'     => ['master', 'Perusahaan', ['Master Data', 'Perusahaan'], ['kind' => 'tenant']],
    ];

    public function __invoke(Request $request): View
    {
        $route = $request->route()->getName();
        [$view, $title, $crumbs] = $this->map[$route];
        $extra = $this->map[$route][3] ?? [];

        return view('pages.' . $view, array_merge([
            'd' => VoltraData::all(),
            'route' => $route,
            'pageTitle' => $title,
            'breadcrumb' => $crumbs,
        ], $extra));
    }
}
