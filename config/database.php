<?php
define('DB_HOST', 'sql305.infinityfree.com');
define('DB_USER', 'if0_41333560');
define('DB_PASS', 'IuGMEY6SXDidMZ4');
define('DB_NAME', 'if0_41333560_tokoku');
define('DB_CHARSET', 'utf8mb4');
define('BASE_PATH', '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="background:#f8d7da;padding:20px;border-radius:8px;font-family:sans-serif;">
                <h3>❌ Koneksi Database Gagal</h3>
                <p>' . $e->getMessage() . '</p>
            </div>');
        }
    }
    return $pdo;
}

function rupiahFormat($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function generateKodePesanan() {
    return 'ORD-' . strtoupper(uniqid()) . '-' . date('Ymd');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function isLogin() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLogin()) {
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . '/index.php');
        exit;
    }
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('<div style="background:#f8d7da;padding:20px;border-radius:8px;font-family:sans-serif;">
            <h3>❌ Request tidak valid (CSRF token mismatch)</h3>
            <p><a href="javascript:history.back()">Kembali</a></p>
        </div>');
    }
    unset($_SESSION['csrf_token']);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
?>