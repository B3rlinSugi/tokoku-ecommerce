<?php
require_once __DIR__ . '/config/database.php';

$pdo = getDB();
$email = 'admin@toko.com';
$newPass = 'password123';
$hash = password_hash($newPass, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
    $stmt->execute([$hash, $email]);
    echo "<h3>✅ Sandi Admin Berhasil Di-Reset!</h3>";
    echo "<p>Email: <b>{$email}</b><br>Sandi Baru: <b>{$newPass}</b></p>";
    echo "<a href='login.php'>Klik disini untuk Kembali ke Login</a>";
} catch (Exception $e) {
    echo "Gagal: " . $e->getMessage();
}
