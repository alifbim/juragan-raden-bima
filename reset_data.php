<?php
require 'vendor/autoload.php';
use MongoDB\Client;

try {
    // 1. Koneksi
    $client = new Client("mongodb://localhost:27017");
    $db = $client->toko_krupuk;
    $produkColl = $db->produk;

    // 2. BERSIHKAN DATA LAMA (Reset Database)
    $produkColl->drop();

    // 3. ISI DATA BARU (Menu Krupuk Lengkap + RADEN & PLOMPONG)
    $daftar_krupuk = [
        [
            "nama" => "Krupuk Putih (Mawar)",
            "harga_eceran" => 1000,
            "harga_kg" => 25000,
            "harga_set" => 150000,
            "stok" => 200
        ],
        [
            "nama" => "Krupuk Raden",   // <--- PRODUK BARU 1
            "harga_eceran" => 1000,
            "harga_kg" => 24000,
            "harga_set" => 140000,
            "stok" => 100
        ],
        [
            "nama" => "Krupuk Plompong", // <--- PRODUK BARU 2
            "harga_eceran" => 1500,
            "harga_kg" => 30000,
            "harga_set" => 180000,
            "stok" => 100
        ],
        [
            "nama" => "Krupuk Bawang Warna",
            "harga_eceran" => 500,
            "harga_kg" => 18000,
            "harga_set" => 100000,
            "stok" => 500
        ],
        [
            "nama" => "Krupuk Kulit (Jangek)",
            "harga_eceran" => 2000,
            "harga_kg" => 80000,
            "harga_set" => 350000,
            "stok" => 50
        ],
        [
            "nama" => "Krupuk Udang Premium",
            "harga_eceran" => 2500,
            "harga_kg" => 65000,
            "harga_set" => 280000,
            "stok" => 80
        ],
        [
            "nama" => "Emping Melinjo",
            "harga_eceran" => 3000,
            "harga_kg" => 90000,
            "harga_set" => 400000,
            "stok" => 40
        ],
        [
            "nama" => "Makaroni Pedas",
            "harga_eceran" => 1000,
            "harga_kg" => 35000,
            "harga_set" => 120000,
            "stok" => 150
        ],
        [
            "nama" => "Kemplang Palembang",
            "harga_eceran" => 3000,
            "harga_kg" => 75000,
            "harga_set" => 300000,
            "stok" => 60
        ],
        [
            "nama" => "Rengginang Terasi",
            "harga_eceran" => 2000,
            "harga_kg" => 50000,
            "harga_set" => 200000,
            "stok" => 75
        ],
        [
            "nama" => "Keripik Singkong",
            "harga_eceran" => 1000,
            "harga_kg" => 20000,
            "harga_set" => 80000,
            "stok" => 100
        ]
    ];

    // Masukkan semua data sekaligus
    $produkColl->insertMany($daftar_krupuk);

    echo "<h1>✅ DATA BERHASIL DIUPDATE!</h1>";
    echo "<p>Krupuk Raden & Plompong sudah masuk database.</p>";
    echo "<a href='index.php'>👉 Klik di sini untuk Buka Kasir</a>";

} catch (Exception $e) {
    echo "Gagal: " . $e->getMessage();
}
?>