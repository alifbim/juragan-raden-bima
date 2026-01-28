<?php
// Memuat Library MongoDB (Pastikan folder vendor ada)
require 'vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

try {
    // --- KONFIGURASI DATABASE ---
    
    // 1. JIKA DI KOMPUTER SENDIRI (LOCALHOST):
    $uri = "mongodb://localhost:27017";

    // 2. JIKA NANTI SUDAH ONLINE (MONGODB ATLAS):
    // Hapus tanda // di baris bawah ini dan masukkan link dari Atlas
    // $uri = "mongodb+srv://username:password@cluster...mongodb.net/?retryWrites=true&w=majority";

    $client = new Client($uri);
    
    // NAMA DATABASE (Sesuai permintaan: toko_krupuk)
    $db = $client->toko_krupuk;
    
    // Definisi Tabel (Collection)
    $produkColl = $db->produk;
    $transaksiColl = $db->transaksi;

} catch (Exception $e) {
    die("<h3>Gagal Koneksi Database!</h3><p>Pastikan MongoDB sudah jalan.</p> Error: " . $e->getMessage());
}
?>