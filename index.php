<?php
require 'koneksi.php';

$pesan = "";
$tipe_pesan = "";

// --- LOGIKA TRANSAKSI (MULTIPLE ITEM / KERANJANG) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_data'])) {
    
    // Ambil data JSON dari keranjang
    $keranjang = json_decode($_POST['cart_data'], true);
    
    if (!empty($keranjang)) {
        $totalTransaksi = 0;
        $waktuTransaksi = new UTCDateTime(); // Waktu yang sama untuk semua item ini
        $berhasil = true;
        
        // 1. Cek Stok Dulu (Validasi)
        foreach ($keranjang as $item) {
            $id = new ObjectId($item['id']);
            $dbBarang = $produkColl->findOne(['_id' => $id]);
            if ($dbBarang['stok'] < $item['qty']) {
                $pesan = "❌ Stok " . $dbBarang['nama'] . " tidak cukup!";
                $tipe_pesan = "danger";
                $berhasil = false;
                break; 
            }
        }

        // 2. Jika Semua Stok Aman, Proses Transaksi
        if ($berhasil) {
            foreach ($keranjang as $item) {
                $id = new ObjectId($item['id']);
                $qty = (int)$item['qty'];
                $total_uang = (int)$item['total']; // Total harga item tersebut
                
                // Kurangi Stok
                $produkColl->updateOne(
                    ['_id' => $id], 
                    ['$inc' => ['stok' => -$qty]]
                );

                // Catat ke Laporan
                $transaksiColl->insertOne([
                    'barang' => $item['nama'],
                    'jumlah' => $qty,
                    'satuan' => $item['satuan'],
                    'total_uang' => $total_uang,
                    'jenis' => 'jual',
                    'waktu' => $waktuTransaksi
                ]);
            }
            $pesan = "✅ Transaksi Berhasil! Semua item telah terjual.";
            $tipe_pesan = "success";
        }
    }
}

// --- LOGIKA RESTOCK CEPAT (SUDAH DIPERBAIKI) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi_restock'])) {
    $id = new ObjectId($_POST['id_produk_restock']);
    
    // PERBAIKAN DISINI (Tidak ada lagi tanda $ dobel)
    $qty = (int)$_POST['jumlah_restock'];
    
    $nama = $_POST['nama_produk_restock'];
    
    $produkColl->updateOne(['_id' => $id], ['$inc' => ['stok' => $qty]]);
    $transaksiColl->insertOne([
        'barang' => $nama,
        'jumlah' => $qty,
        'jenis' => 'restock',
        'waktu' => new UTCDateTime()
    ]);
    $pesan = "📦 Stok " . $nama . " bertambah!";
    $tipe_pesan = "primary";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir - Juragan Raden Bima</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Poppins', sans-serif; margin: 0; height: 100vh; overflow: hidden; display: flex; }
        
        /* LAYOUT */
        .sidebar-left { width: 250px; background: white; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 20px; z-index: 10; flex-shrink: 0; }
        .content-middle { flex-grow: 1; overflow-y: auto; padding: 20px; background-color: #f4f6f9; }
        .sidebar-right { width: 400px; background: white; border-left: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 0; z-index: 10; flex-shrink: 0; box-shadow: -5px 0 15px rgba(0,0,0,0.02); }

        /* COMPONENTS */
        .brand { color: #ff9f43; font-weight: 800; font-size: 18px; margin-bottom: 30px; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .menu-item { display: flex; align-items: center; padding: 10px 15px; color: #636e72; text-decoration: none; border-radius: 10px; margin-bottom: 5px; font-weight: 600; font-size: 14px; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background-color: #ff9f43; color: white; }

        .product-card { background: white; border-radius: 12px; border: 1px solid #eee; cursor: pointer; position: relative; overflow: hidden; height: 100%; transition: 0.2s; }
        .product-card:hover { transform: translateY(-3px); border-color: #ff9f43; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .product-card.selected { border: 2px solid #ff9f43; background-color: #fff8e1; }
        .stok-badge { position: absolute; top: 8px; right: 8px; background: #fff3e0; color: #e67e22; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        
        /* KERANJANG STYLE */
        .cart-header { padding: 20px; border-bottom: 1px solid #eee; background: #fff; }
        .cart-items { flex-grow: 1; overflow-y: auto; padding: 20px; }
        .cart-footer { padding: 20px; border-top: 1px solid #eee; background: #f8f9fa; }
        
        .cart-item-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #eee; }
        .cart-item-title { font-weight: 700; font-size: 14px; color: #2d3436; }
        .cart-item-meta { font-size: 11px; color: #b2bec3; }
        .cart-item-price { font-weight: 700; color: #2d3436; }
        .btn-remove { color: #ff7675; cursor: pointer; font-size: 14px; padding: 5px; }

        .btn-add-cart { background: #ff9f43; color: white; border: none; font-weight: bold; width: 100%; padding: 10px; border-radius: 8px; margin-top: 10px; }
        .btn-add-cart:disabled { background: #dfe6e9; cursor: not-allowed; }
        .btn-checkout { background: #2d3436; color: white; border: none; font-weight: bold; width: 100%; padding: 15px; border-radius: 10px; font-size: 16px; }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <a href="#" class="brand"><i class="fas fa-store"></i> Juragan<br>Raden Bima</a>
        <nav>
            <a href="index.php" class="menu-item active"><i class="fas fa-cash-register me-3"></i> Kasir</a>
            <a href="riwayat.php" class="menu-item"><i class="fas fa-receipt me-3"></i> Riwayat</a>
            <a href="gudang.php" class="menu-item"><i class="fas fa-warehouse me-3"></i> Gudang</a>
        </nav>
    </div>

    <div class="content-middle">
        <h5 class="fw-bold mb-3">Pilih Produk 🍘</h5>
        
        <?php if ($pesan): ?> 
            <div class="alert alert-<?= $tipe_pesan ?> fw-bold shadow-sm border-0 mb-3"><?= $pesan ?></div> 
        <?php endif; ?>

        <div class="row g-2">
            <?php foreach($produkColl->find() as $p): 
                $dataJson = json_encode([
                    'id' => (string)$p['_id'],
                    'nama' => $p['nama'],
                    'stok' => $p['stok'],
                    'ecer' => $p['harga_eceran'] ?? 0,
                    'kg' => $p['harga_kg'] ?? 0,
                    'set' => $p['harga_set'] ?? 0
                ]);
            ?>
            <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6">
                <div class="product-card p-3 text-center" onclick='pilihProduk(<?= $dataJson ?>, this)'>
                    <span class="stok-badge"><?= $p['stok'] ?> pcs</span>
                    <div style="font-size: 35px; margin: 10px 0;">🍘</div>
                    <div style="font-weight:700; font-size:13px; line-height:1.2;"><?= $p['nama'] ?></div>
                    <div style="font-size:12px; color:#636e72;" class="mt-1">Rp <?= number_format($p['harga_eceran'] ?? 0) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sidebar-right">
        
        <div class="cart-header">
            <h6 class="fw-bold mb-3"><i class="fas fa-edit me-2"></i>Input Pesanan</h6>
            
            <div id="no-selection" class="text-center text-muted py-3 small bg-light rounded">
                Pilih krupuk di sebelah kiri
            </div>

            <div id="input-area" style="display:none;">
                <h6 class="fw-bold text-dark mb-0" id="disp_nama">-</h6>
                <small class="text-success fw-bold mb-2 d-block">Sisa Stok: <span id="disp_stok">0</span></small>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Satuan</label>
                        <select id="input_satuan" class="form-select form-select-sm fw-bold bg-light" onchange="hitungHargaInput()">
                            <option value="eceran">Eceran</option>
                            <option value="kg">Kiloan</option>
                            <option value="set">Kaleng</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Jumlah</label>
                        <input type="number" id="input_qty" class="form-control form-select-sm fw-bold text-center" value="1" min="1" oninput="hitungHargaInput()">
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-2 align-items-center">
                    <small class="text-muted">Subtotal:</small>
                    <span class="fw-bold text-dark" id="disp_harga">Rp 0</span>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn-add-cart" onclick="tambahKeKeranjang()">
                        <i class="fas fa-plus me-1"></i> TAMBAH
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="tampilkanRestock()" title="Restock Stok Gudang">
                        <i class="fas fa-box"></i>
                    </button>
                </div>
            </div>

            <form method="POST" id="form-restock" style="display:none;" class="mt-2 p-2 bg-light rounded border">
                <input type="hidden" name="aksi_restock" value="1">
                <input type="hidden" name="id_produk_restock" id="res_id">
                <input type="hidden" name="nama_produk_restock" id="res_nama">
                <label class="small fw-bold">Tambah Stok Gudang:</label>
                <div class="input-group input-group-sm mt-1">
                    <input type="number" name="jumlah_restock" class="form-control" placeholder="Jml" required>
                    <button class="btn btn-secondary">Simpan</button>
                </div>
            </form>
        </div>

        <div class="cart-items" id="cart-container">
            <div class="text-center text-muted mt-5 opacity-50">
                <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                <p class="small">Keranjang masih kosong</p>
            </div>
        </div>

        <div class="cart-footer">
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted fw-bold">Total Belanja</span>
                <span class="fs-4 fw-bold text-dark" id="grand_total">Rp 0</span>
            </div>
            
            <div class="mb-3">
                <input type="number" id="uang_bayar" class="form-control fw-bold" placeholder="Uang Tunai (Rp)" oninput="hitungKembalian()">
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted fw-bold" style="font-size: 11px;">KEMBALIAN</small>
                    <small class="fw-bold text-success" id="text_kembalian">Rp 0</small>
                </div>
            </div>

            <form method="POST" id="form-checkout">
                <input type="hidden" name="cart_data" id="json_cart">
                <button type="button" onclick="submitTransaksi()" class="btn-checkout shadow-sm" id="btn-pay" disabled>
                    BAYAR SEKARANG
                </button>
            </form>
        </div>
    </div>

<script>
// --- VARIABEL GLOBAL ---
let produkTerpilih = null;
let keranjang = [];
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);

// --- 1. PILIH PRODUK DARI DAFTAR ---
function pilihProduk(data, el) {
    document.querySelectorAll('.product-card').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    
    produkTerpilih = data;
    
    // Tampilkan Form Input
    document.getElementById('no-selection').style.display = 'none';
    document.getElementById('input-area').style.display = 'block';
    document.getElementById('form-restock').style.display = 'none'; // Sembunyikan form restock jk ada
    
    // Isi Data ke Form Input
    document.getElementById('disp_nama').innerText = data.nama;
    document.getElementById('disp_stok').innerText = data.stok;
    
    // Reset Input
    document.getElementById('input_qty').value = 1;
    
    // Update Dropdown Harga
    let sel = document.getElementById('input_satuan');
    sel.options[0].text = "Eceran (" + formatRupiah(data.ecer) + ")";
    sel.options[1].text = "Kiloan (" + formatRupiah(data.kg) + ")";
    sel.options[2].text = "Set/Kaleng (" + formatRupiah(data.set) + ")";
    sel.value = "eceran"; // Default eceran

    hitungHargaInput();
}

// --- 2. HITUNG SUB-TOTAL DI FORM INPUT ---
function hitungHargaInput() {
    if(!produkTerpilih) return;
    
    let qty = parseInt(document.getElementById('input_qty').value) || 0;
    let satuan = document.getElementById('input_satuan').value;
    
    let harga = 0;
    if(satuan === 'eceran') harga = produkTerpilih.ecer;
    else if(satuan === 'kg') harga = produkTerpilih.kg;
    else if(satuan === 'set') harga = produkTerpilih.set;
    
    let subtotal = qty * harga;
    document.getElementById('disp_harga').innerText = formatRupiah(subtotal);
}

// --- 3. TAMBAH KE KERANJANG (ARRAY) ---
function tambahKeKeranjang() {
    if(!produkTerpilih) return;
    
    let qty = parseInt(document.getElementById('input_qty').value) || 0;
    if(qty <= 0) { alert("Jumlah minimal 1"); return; }
    
    // Cek Stok Client Side
    if(qty > produkTerpilih.stok) { alert("Stok tidak cukup!"); return; }

    let satuan = document.getElementById('input_satuan').value;
    let hargaSatuan = 0;
    let labelSatuan = "";
    
    if(satuan === 'eceran') { hargaSatuan = produkTerpilih.ecer; labelSatuan = "Ecer"; }
    else if(satuan === 'kg') { hargaSatuan = produkTerpilih.kg; labelSatuan = "Kg"; }
    else if(satuan === 'set') { hargaSatuan = produkTerpilih.set; labelSatuan = "Set"; }

    let total = qty * hargaSatuan;

    // Masukkan ke Array Keranjang
    keranjang.push({
        id: produkTerpilih.id,
        nama: produkTerpilih.nama,
        qty: qty,
        satuan: satuan,
        labelSatuan: labelSatuan,
        harga: hargaSatuan,
        total: total
    });

    // Reset Form Input
    document.getElementById('input-area').style.display = 'none';
    document.getElementById('no-selection').style.display = 'block';
    document.querySelectorAll('.product-card').forEach(e => e.classList.remove('selected'));
    produkTerpilih = null;

    renderKeranjang();
}

// --- 4. RENDER TAMPILAN KERANJANG ---
function renderKeranjang() {
    let container = document.getElementById('cart-container');
    container.innerHTML = "";
    
    let grandTotal = 0;

    if(keranjang.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted mt-5 opacity-50">
                <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                <p class="small">Keranjang masih kosong</p>
            </div>`;
        document.getElementById('btn-pay').disabled = true;
    } else {
        keranjang.forEach((item, index) => {
            grandTotal += item.total;
            
            let row = `
            <div class="cart-item-row">
                <div style="flex-grow:1;">
                    <div class="cart-item-title">${item.nama}</div>
                    <div class="cart-item-meta">
                        ${item.qty} x ${formatRupiah(item.harga)} (${item.labelSatuan})
                    </div>
                </div>
                <div class="text-end">
                    <div class="cart-item-price">${formatRupiah(item.total)}</div>
                    <i class="fas fa-trash-alt btn-remove" onclick="hapusItem(${index})"></i>
                </div>
            </div>`;
            container.innerHTML += row;
        });
        document.getElementById('btn-pay').disabled = false;
    }

    document.getElementById('grand_total').innerText = formatRupiah(grandTotal);
    document.getElementById('grand_total').dataset.value = grandTotal; // Simpan angka asli
    hitungKembalian();
}

// --- 5. HAPUS ITEM DARI KERANJANG ---
function hapusItem(index) {
    keranjang.splice(index, 1);
    renderKeranjang();
}

// --- 6. HITUNG KEMBALIAN ---
function hitungKembalian() {
    let total = parseInt(document.getElementById('grand_total').dataset.value) || 0;
    let bayar = parseInt(document.getElementById('uang_bayar').value) || 0;
    let kembalian = bayar - total;
    
    let el = document.getElementById('text_kembalian');
    if(bayar > 0) {
        if(kembalian >= 0) {
            el.innerText = formatRupiah(kembalian);
            el.className = "fw-bold text-success";
        } else {
            el.innerText = "Kurang " + formatRupiah(Math.abs(kembalian));
            el.className = "fw-bold text-danger";
        }
    } else {
        el.innerText = "Rp 0";
    }
}

// --- 7. SUBMIT FORM (CHECKOUT) ---
function submitTransaksi() {
    if(keranjang.length === 0) return;
    
    // Masukkan data array JS ke Input Hidden HTML
    document.getElementById('json_cart').value = JSON.stringify(keranjang);
    
    // Submit Form
    document.getElementById('form-checkout').submit();
}

// --- FITUR TAMBAHAN: RESTOCK CEPAT ---
function tampilkanRestock() {
    if(!produkTerpilih) return;
    document.getElementById('input-area').style.display = 'none';
    document.getElementById('form-restock').style.display = 'block';
    
    document.getElementById('res_id').value = produkTerpilih.id;
    document.getElementById('res_nama').value = produkTerpilih.nama;
}
</script>
</body>
</html>