@props(['status' => '', 'label' => null, 'tone' => null])
@php
    $tones = [
        'brand'  => ['bg' => '#ecf7f8', 'fg' => '#0b4e56', 'dot' => '#177f8a'],
        'gray'   => ['bg' => '#f0f1f5', 'fg' => '#3e4553', 'dot' => '#8a92a3'],
        'amber'  => ['bg' => '#fef3e8', 'fg' => '#a6700f', 'dot' => '#d18b1f'],
        'red'    => ['bg' => '#fef3f2', 'fg' => '#b42318', 'dot' => '#e56362'],
        'green'  => ['bg' => '#eef8ef', 'fg' => '#1f6a34', 'dot' => '#3d9956'],
        'blue'   => ['bg' => '#eef3fd', 'fg' => '#1f4c9c', 'dot' => '#3f73d6'],
        'purple' => ['bg' => '#f3eefd', 'fg' => '#5b2ea8', 'dot' => '#7c43d4'],
    ];
    $statusTone = [
        'di_perusahaan' => 'gray', 'di_proyek' => 'brand', 'di_gudang' => 'green', 'terjual' => 'gray', 'rusak' => 'red',
        'pesan' => 'blue', 'deal' => 'brand', 'selesai' => 'gray', 'dibatalkan' => 'red',
        'belum_bayar' => 'red', 'dp' => 'amber', 'lunas' => 'green', 'overdue' => 'red',
        'tersedia' => 'green', 'disewa' => 'brand', 'maintenance' => 'amber', 'tidak_tersedia' => 'gray',
        'aktif' => 'brand', 'ditutup' => 'gray',
        'posted' => 'green', 'pending' => 'amber',
        'rutin' => 'green', 'perbaikan' => 'amber', 'overhaul' => 'red',
        'pengambilan' => 'blue', 'pengembalian' => 'purple',
        'owner' => 'purple', 'admin' => 'brand', 'operator' => 'blue', 'teknisi' => 'amber', 'akuntan' => 'green',
    ];
    $t = $tones[$tone ?? ($statusTone[$status] ?? 'gray')];
    $text = $label ?? lbl($status);
@endphp
<span class="pill" style="background:{{ $t['bg'] }};color:{{ $t['fg'] }}">
    <span class="dot" style="background:{{ $t['dot'] }}"></span>{{ $text }}
</span>
