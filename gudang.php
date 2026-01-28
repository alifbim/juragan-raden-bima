<?php
require 'koneksi.php'; // Panggil file koneksi pusat

$pesan = "";
// --- LOGIKA GUDANG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = new ObjectId($_POST['id_produk']);
    $jumlah = (int)$_POST['jumlah'];
    $aksi = $_POST['aksi'];
    $barang = $produkColl->findOne(['_id' => $id]);

    if ($barang) {
        if ($aksi == 'buang') {
            // LOGIKA BUANG BARANG (STOK KELUAR - BUKAN JUAL)
            if ($barang['stok'] >= $jumlah) {
                $produkColl->updateOne(['_id' => $id], ['$inc' => ['stok' => -$jumlah]]);
                
                $transaksiColl->insertOne([
                    'barang' => $barang['nama'],
                    'jumlah' => $jumlah,
                    'jenis' => 'buang', // Penanda Khusus Basi
                    'alasan' => $_POST['alasan'],
                    'waktu' => new UTCDateTime()
                ]);
                $pesan = "🗑️ Berhasil membuang " . $jumlah . " " . $barang['nama'];
            } else { $pesan = "❌ Stok tidak cukup."; }
        
        } elseif ($aksi == 'tambah') {
            // LOGIKA TAMBAH BARANG (RESTOCK GUDANG)
            $produkColl->updateOne(['_id' => $id], ['$inc' => ['stok' => $jumlah]]);
            
            $transaksiColl->insertOne([
                'barang' => $barang['nama'],
                'jumlah' => $jumlah,
                'jenis' => 'restock', // Penanda Khusus Masuk
                'waktu' => new UTCDateTime()
            ]);
            $pesan = "📦 Stok Bertambah: " . $jumlah . " " . $barang['nama'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Gudang - Juragan Raden Bima</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Poppins', sans-serif; display: flex; min-height: 100vh; }
        .sidebar-left { width: 260px; background: white; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 25px; flex-shrink: 0; min-height: 100vh;}
        .main-content { flex-grow: 1; padding: 30px; }
        .menu-item { display: flex; align-items: center; padding: 12px 20px; color: #636e72; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 600; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background-color: #ff9f43; color: white; }
        .brand { color: #ff9f43; font-weight: 800; font-size: 20px; margin-bottom: 40px; text-decoration: none; display: flex; align-items: center; gap: 10px; line-height: 1.2; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: white; }
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #eee; color: #636e72; font-weight: 700; }
        .badge-masuk { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-keluar { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <a href="#" class="brand"><i class="fas fa-store"></i> Juragan<br>Raden Bima</a>
        <nav>
            <a href="index.php" class="menu-item"><i class="fas fa-cash-register me-3"></i> Kasir</a>
            <a href="riwayat.php" class="menu-item"><i class="fas fa-receipt me-3"></i> Riwayat</a>
            <a href="gudang.php" class="menu-item active"><i class="fas fa-warehouse me-3"></i> Gudang</a>
        </nav>
    </div>

    <div class="main-content">
        <h3 class="fw-bold text-dark mb-4">Manajemen Gudang 📦</h3>
        <?php if ($pesan): ?> <div class="alert alert-info fw-bold border-0 shadow-sm mb-4"><?= $pesan ?></div> <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card card-custom p-4 h-100">
                    <h5 class="fw-bold mb-3">Atur Stok</h5>
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item"><button class="nav-link active rounded-pill fw-bold" id="pills-buang-tab" data-bs-toggle="pill" data-bs-target="#pills-buang">Buang (Rusak)</button></li>
                        <li class="nav-item"><button class="nav-link rounded-pill fw-bold" id="pills-tambah-tab" data-bs-toggle="pill" data-bs-target="#pills-tambah">Tambah (Restock)</button></li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-buang">
                            <form method="POST">
                                <input type="hidden" name="aksi" value="buang">
                                <div class="mb-3"><label class="small text-muted fw-bold">Pilih Produk</label><select name="id_produk" class="form-select" required><?php foreach($produkColl->find() as $p): ?><option value="<?= $p['_id'] ?>"><?= $p['nama'] ?> (Sisa: <?= $p['stok'] ?>)</option><?php endforeach; ?></select></div>
                                <div class="mb-3"><label class="small text-muted fw-bold">Jumlah</label><input type="number" name="jumlah" class="form-control" min="1" required></div>
                                <div class="mb-3"><label class="small text-muted fw-bold">Alasan</label><select name="alasan" class="form-select"><option value="Basi">Basi</option><option value="Hancur">Hancur</option><option value="Hilang">Hilang</option></select></div>
                                <button class="btn btn-danger w-100 fw-bold">Konfirmasi Buang</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="pills-tambah">
                            <form method="POST">
                                <input type="hidden" name="aksi" value="tambah">
                                <div class="mb-3"><label class="small text-muted fw-bold">Pilih Produk</label><select name="id_produk" class="form-select" required><?php foreach($produkColl->find() as $p): ?><option value="<?= $p['_id'] ?>"><?= $p['nama'] ?> (Sisa: <?= $p['stok'] ?>)</option><?php endforeach; ?></select></div>
                                <div class="mb-3"><label class="small text-muted fw-bold">Jumlah Masuk</label><input type="number" name="jumlah" class="form-control" min="1" required></div>
                                <button class="btn btn-primary w-100 fw-bold">Konfirmasi Tambah</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2"></i>Log Gudang</h5>
                        <span class="badge bg-secondary">Bukan Penjualan</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead><tr><th>Waktu</th><th>Barang</th><th>Status</th><th>Jml</th><th>Ket</th></tr></thead>
                            <tbody>
                                <?php 
                                // Filter: HANYA Restock & Buang
                                $filter = ['jenis' => ['$in' => ['restock', 'buang']]];
                                $riwayat = $transaksiColl->find($filter, ['limit' => 20, 'sort' => ['waktu' => -1]]);
                                foreach($riwayat as $r): 
                                    $tgl = $r['waktu']->toDateTime();
                                    $tgl->setTimezone(new DateTimeZone('Asia/Jakarta'));
                                    if ($r['jenis'] == 'restock') {
                                        $badge = '<span class="badge badge-masuk px-3 py-2 rounded-pill"><i class="fas fa-arrow-down me-1"></i> MASUK</span>';
                                        $ket = "Restock Gudang";
                                    } else {
                                        $badge = '<span class="badge badge-keluar px-3 py-2 rounded-pill"><i class="fas fa-trash me-1"></i> DIBUANG</span>';
                                        $ket = $r['alasan'] ?? '-';
                                    }
                                ?>
                                <tr>
                                    <td class="small text-muted"><?= $tgl->format('d/m/y H:i') ?></td>
                                    <td class="fw-bold"><?= $r['barang'] ?></td>
                                    <td><?= $badge ?></td>
                                    <td class="fw-bold"><?= $r['jumlah'] ?></td>
                                    <td class="small text-muted fst-italic"><?= $ket ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>