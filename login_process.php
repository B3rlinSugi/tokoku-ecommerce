<?php
session_start();

try {
    require __DIR__ . '/config/config.php';
} catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Server/database error'];
    header('Location: login.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Metode tidak valid'];
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Username dan password wajib diisi'];
    header('Location: login.php');
    exit;
}

$mysqli = db();

try {
    // Sesuaikan nama tabel & kolom: tbadmin(idadmin, username, hashadmin)
    $sql  = "SELECT idadmin, username, hashadmin FROM tbadmin WHERE username = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();

    // tanpa get_result() agar kompatibel tanpa mysqlnd
    $stmt->bind_result($idadmin, $username, $hashadmin);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Username tidak ditemukan'];
        header('Location: login.php');
        exit;
    }

    if (!password_verify($password, (string)$hashadmin)) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Password salah'];
        header('Location: login.php');
        exit;
    }

    // Sukses
    $_SESSION['user_id']  = (int)$idadmin;
    $_SESSION['username'] = (string)$username;
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Login berhasil'];
    header('Location: login.php'); // balik ke login untuk munculin alert dulu
    exit;
} catch (Throwable $e) {
    // error_log($e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Terjadi kesalahan server'];
    header('Location: login.php');
    exit;
}
