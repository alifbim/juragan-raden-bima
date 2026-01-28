<?php
require 'koneksi.php'; // Panggil file koneksi pusat

// --- LOGIKA LAPORAN ---
// Filter: HANYA Penjualan (Uang Masuk)
// Log 'buang' dan 'restock' tidak diambil.
$filter = ['jenis' => 'jual'];
$opsi = ['limit' => 100, 'sort' => ['waktu' => -1]];

$dataRiwayat = $transaksiColl->find($filter, $opsi);

// Hitung Total Omset (dari 100 data terakhir yang tampil)
$totalOmset = 0;
$listTransaksi = iterator_to_array($dataRiwayat);
foreach ($listTransaksi as $t) {
    if (isset($t['total_uang'])) {
        $totalOmset += $t['total_uang'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - Juragan Raden Bima</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Poppins', sans-serif; display: flex; min-height: 100vh; margin: 0; }
        .sidebar-left { width: 260px; background: white; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 25px; flex-shrink: 0; min-height: 100vh; position: fixed; left: 0; top: 0; bottom: 0; z-index: 100; }
        .main-content { margin-left: 260px; flex-grow: 1; padding: 30px; width: calc(100% - 260px); }
        .brand { color: #ff9f43; font-weight: 800; font-size: 20px; margin-bottom: 40px; text-decoration: none; display: flex; align-items: center; gap: 10px; line-height: 1.2; }
        .menu-item { display: flex; align-items: center; padding: 12px 20px; color: #636e72; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 600; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background-color: #ff9f43; color: white; box-shadow: 0 4px 10px rgba(255, 159, 67, 0.3); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: white; margin-bottom: 20px; }
        .stat-card { background: linear-gradient(135deg, #ff9f43 0%, #ffbe76 100%); color: white; border-radius: 15px; padding: 25px; box-shadow: 0 8px 20px rgba(255, 159, 67, 0.2); }
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #eee; color: #636e72; font-weight: 700; padding: 15px; }
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
        .text-money { font-family: 'Consolas', monospace; font-weight: 700; letter-spacing: -0.5px; }
        .badge-satuan { background: #eee; color: #636e72; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 700; text-transform: uppercase; }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <a href="#" class="brand"><i class="fas fa-store"></i> Juragan<br>Raden Bima</a>
        <nav>
            <a href="index.php" class="menu-item"><i class="fas fa-cash-register me-3"></i> Kasir</a>
            <a href="riwayat.php" class="menu-item active"><i class="fas fa-receipt me-3"></i> Riwayat</a>
            <a href="gudang.php" class="menu-item"><i class="fas fa-warehouse me-3"></i> Gudang</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Laporan Penjualan 🧾</h3>
                <p class="text-muted small mb-0">Rekap transaksi uang masuk hari ini.</p>
            </div>
            <div class="text-end text-muted small fw-bold"><i class="far fa-calendar-alt me-2"></i> <?= date('d F Y') ?></div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 opacity-75">Total Omset (Halaman Ini)</h6>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($totalOmset, 0, ',', '.') ?></h2>
                    </div>
                    <div style="font-size: 40px; opacity: 0.3;"><i class="fas fa-wallet"></i></div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card card-custom p-4">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Barang</th>
                                    <th class="text-center">Satuan</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Total Uang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listTransaksi as $r): 
                                    $tgl = $r['waktu']->toDateTime();
                                    $tgl->setTimezone(new DateTimeZone('Asia/Jakarta'));
                                    $satuanLabel = $r['satuan'] ?? '-';
                                    if($satuanLabel == 'eceran') $satuanLabel = 'Ecer';
                                    if($satuanLabel == 'kg') $satuanLabel = 'Kg';
                                    if($satuanLabel == 'set') $satuanLabel = 'Set';
                                ?>
                                <tr>
                                    <td class="text-muted small" style="width: 150px;"><?= $tgl->format('d M, H:i') ?> WIB</td>
                                    <td class="fw-bold text-dark"><?= $r['barang'] ?></td>
                                    <td class="text-center"><span class="badge-satuan"><?= $satuanLabel ?></span></td>
                                    <td class="text-center fw-bold text-secondary"><?= $r['jumlah'] ?></td>
                                    <td class="text-end text-success text-money fs-6">+ Rp <?= number_format($r['total_uang'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($listTransaksi) === 0): ?>
                        <div class="text-center text-muted py-5"><p>Belum ada penjualan hari ini.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>