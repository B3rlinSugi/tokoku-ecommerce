<?php
require_once __DIR__ . '/config/database.php';

// Cek apakah tabel 'users' sudah ada
$db = getDB();
try {
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        die("<h3>✅ Database sudah dimigrasi sebelumnya!</h3>");
    }
} catch (Exception $e) {
    // Lanjut
}

// Baca file SQL
$sqlFile = __DIR__ . '/database/ecommerce.sql';
if (!file_exists($sqlFile)) {
    die("<h3>❌ File ecommerce.sql tidak ditemukan!</h3>");
}

$sql = file_get_contents($sqlFile);

try {
    // Eksekusi semua querry di dalam file SQL
    $db->exec($sql);
    echo "<h3>🎉 Migrasi Database Sukses!</h3>";
    echo "<p>Silakan kembali ke <a href='index.php'>Halaman Utama</a>.</p>";
} catch (PDOException $e) {
    echo "<h3>❌ Gagal mengeksekusi SQL:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
