<?php
session_start();

require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = $_POST['nama'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $pass  = $_POST['password'] ?? '';

    if (empty($nama) || empty($phone) || empty($pass)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap!']);
        exit;
    }

    // 1. Cek apakah nomor sudah terdaftar
    $stmt = $conn->prepare("SELECT id_cust FROM tbcustomer WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp sudah terdaftar!']);
        exit;
    }

    // 2. Simpan data ke database
    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO tbcustomer (nama_lengkap, phone, password) VALUES (?, ?, ?)");
    $ins->bind_param("sss", $nama, $phone, $hashed);

    if ($ins->execute()) {
        // --- PROSES SET SESSION (LOGIN OTOMATIS) ---
        $new_user_id = $ins->insert_id;

        $_SESSION['user_id']   = $new_user_id;
        $_SESSION['user_name'] = $nama;
        $_SESSION['user_role'] = 'customer'; // Optional untuk pembeda admin

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar ke database.']);
    }
}