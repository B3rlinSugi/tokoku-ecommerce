<?php
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $pass = $_POST['password'] ?? '';

    // 1. Validasi Input Kosong
    if (empty($phone) || empty($pass)) {
        echo json_encode(['status' => 'error', 'message' => 'WhatsApp dan Password harus diisi!']);
        exit;
    }

    // 2. Cari user berdasarkan nomor WhatsApp
    $stmt = $conn->prepare("SELECT id_cust, nama_lengkap, password FROM tbcustomer WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 3. Verifikasi Password (Hash)
        if (password_verify($pass, $user['password'])) {

            // --- SET SESSION LOGIN ---
            $_SESSION['user_id'] = $user['id_cust'];
            $_SESSION['user_name'] = $user['nama_lengkap'];
            $_SESSION['user_role'] = 'customer';

            echo json_encode(['status' => 'success', 'message' => 'Login berhasil!']);
        } else {
            // Password salah
            echo json_encode(['status' => 'error', 'message' => 'Password yang Anda masukkan salah.']);
        }
    } else {
        // Nomor tidak terdaftar
        echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak terdaftar.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode akses tidak diizinkan.']);
}