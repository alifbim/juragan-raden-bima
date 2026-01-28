<?php
echo "<h2>🔍 Diagnosa Koneksi MongoDB</h2>";

// 1. Cek Driver PHP
if (extension_loaded("mongodb")) {
    echo "<div style='color:green'>✅ Driver 'mongodb' terdeteksi di PHP.</div>";
} else {
    die("<div style='color:red; font-weight:bold'>❌ Driver 'mongodb' TIDAK AKTIF! <br>Cek php.ini dan pastikan 'extension=php_mongodb.dll' sudah ada.</div>");
}

// 2. Cek Library Composer
if (file_exists('vendor/autoload.php')) {
    echo "<div style='color:green'>✅ File 'vendor/autoload.php' ditemukan.</div>";
    require 'vendor/autoload.php';
} else {
    die("<div style='color:red'>❌ Folder 'vendor' tidak ada. Jalankan 'composer install' dulu.</div>");
}

// 3. Cek Koneksi Database
try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    // Coba ping database
    $client->listDatabases();
    echo "<div style='color:green; font-weight:bold'>✅ SUKSES! PHP sudah nyambung ke Database.</div>";
} catch (Exception $e) {
    echo "<div style='color:red'>❌ Gagal konek database: " . $e->getMessage() . "</div>";
}
?>