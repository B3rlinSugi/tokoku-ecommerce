<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_PATH . '/admin/dashboard.php');
            } else {
                header('Location: ' . BASE_PATH . '/index.php');
            }
            exit;
        } else {
            $error = 'Email atau password salah!';
        }
    } else {
        $error = 'Isi semua field!';
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - TokoKu</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; background: #f5f5f5; }
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear,
        input[type="password"]::-webkit-contacts-auto-fill-button,
        input[type="password"]::-webkit-credentials-auto-fill-button { display: none !important; visibility: hidden; pointer-events: none; }
        .left-panel { flex: 1; background: linear-gradient(135deg, #ee4d2d 0%, #ff6b35 50%, #f5a623 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; position: relative; overflow: hidden; }
        .left-panel::before { content: ''; position: absolute; width: 400px; height: 400px; background: rgba(255,255,255,0.08); border-radius: 50%; top: -100px; left: -100px; }
        .left-panel::after { content: ''; position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,0.06); border-radius: 50%; bottom: -80px; right: -80px; }
        .left-logo { font-size: 2.5rem; font-weight: 900; color: white; margin-bottom: 8px; letter-spacing: -1px; position: relative; z-index: 1; }
        .left-logo span { color: #ffe066; }
        .left-tagline { color: rgba(255,255,255,0.9); font-size: 1rem; margin-bottom: 40px; position: relative; z-index: 1; }
        .left-features { display: flex; flex-direction: column; gap: 16px; position: relative; z-index: 1; width: 100%; max-width: 320px; }
        .feature-item { display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.25); border-radius: 12px; padding: 14px 16px; color: white; }
        .feature-icon { font-size: 1.6rem; }
        .feature-text strong { display: block; font-size: 0.88rem; font-weight: 700; }
        .feature-text span { font-size: 0.75rem; opacity: 0.85; }
        .right-panel { width: 460px; background: white; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px 40px; box-shadow: -4px 0 24px rgba(0,0,0,0.08); }
        .form-logo { font-size: 1.6rem; font-weight: 900; color: #ee4d2d; margin-bottom: 6px; letter-spacing: -0.5px; }
        .form-logo span { color: #f5a623; }
        .form-subtitle { color: #757575; font-size: 0.88rem; margin-bottom: 28px; }
        .form-title { font-size: 1.3rem; font-weight: 800; color: #212121; margin-bottom: 24px; width: 100%; }
        .form-group { width: 100%; margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #424242; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 12px 14px; border: 1.5px solid #e0e0e0; border-radius: 8px; font-size: 0.92rem; transition: all 0.2s; outline: none; background: #fafafa; }
        .form-group input:focus { border-color: #ee4d2d; background: white; box-shadow: 0 0 0 3px rgba(238,77,45,0.1); }
        .btn-login { width: 100%; padding: 13px; background: linear-gradient(135deg, #ee4d2d, #ff6b35); color: white; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 4px; letter-spacing: 0.3px; }
        .btn-login:hover { background: linear-gradient(135deg, #d73211, #ee4d2d); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(238,77,45,0.3); }
        .divider { display: flex; align-items: center; gap: 12px; width: 100%; margin: 20px 0; color: #bdbdbd; font-size: 0.78rem; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e0e0e0; }
        .register-link { text-align: center; font-size: 0.85rem; color: #757575; }
        .register-link a { color: #ee4d2d; font-weight: 700; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
        .alert-error { width: 100%; background: #fce4ec; color: #c62828; border-left: 3px solid #f44336; border-radius: 6px; padding: 10px 14px; font-size: 0.82rem; margin-bottom: 16px; }
        .pass-wrapper { position: relative; }
        .pass-wrapper input { padding-right: 44px; }
        .pass-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1rem; color: #9e9e9e; user-select: none; line-height: 1; z-index: 2; }
        .pass-toggle:hover { color: #ee4d2d; }
        @media (max-width: 768px) { .left-panel { display: none; } .right-panel { width: 100%; padding: 32px 24px; } }
    </style>
    <link rel="shortcut icon" href="/assets/images/favicon.svg" type="image/svg+xml">
</head>
<body>
    <div class="left-panel">
        <div class="left-logo">Toko<span>Ku</span></div>
        <div class="left-tagline">Belanja Online Terpercaya #1</div>
        <div class="left-features">
            <div class="feature-item"><span class="feature-icon">🚚</span><div class="feature-text"><strong>Gratis Ongkir</strong><span>Untuk semua pesanan ke seluruh Indonesia</span></div></div>
            <div class="feature-item"><span class="feature-icon">🔒</span><div class="feature-text"><strong>Transaksi 100% Aman</strong><span>Pembayaran terenkripsi & terjamin</span></div></div>
            <div class="feature-item"><span class="feature-icon">🎟️</span><div class="feature-text"><strong>Voucher & Diskon</strong><span>Hemat lebih banyak setiap hari</span></div></div>
            <div class="feature-item"><span class="feature-icon">⭐</span><div class="feature-text"><strong>Produk Berkualitas</strong><span>Ribuan produk pilihan terbaik</span></div></div>
        </div>
    </div>

    <div class="right-panel">
        <div class="form-logo">Toko<span>Ku</span></div>
        <div class="form-subtitle">Platform belanja online terpercaya</div>
        <div class="form-title">Masuk ke Akun Anda</div>

        <?php if ($error): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="width:100%;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label>Alamat Email</label>
                <input type="email" name="email"
                       placeholder="contoh@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="pass-wrapper">
                    <input type="password" name="password" id="passInput" placeholder="Masukkan password" required>
                    <span class="pass-toggle" onclick="togglePass('passInput', this)">👁️</span>
                </div>
            </div>

            <!-- ✅ DIPERBAIKI: href="#" → href lupa-password.php -->
            <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
                <a href="<?= BASE_PATH ?>/lupa-password.php"
                   style="font-size:0.8rem; color:#ee4d2d; text-decoration:none;">
                   Lupa password?
                </a>
            </div>

            <button type="submit" class="btn-login">Masuk →</button>
        </form>

        <div class="divider">atau</div>
        <div class="register-link">
            Belum punya akun? <a href="<?= BASE_PATH ?>/register.php">Daftar Gratis Sekarang</a>
        </div>
    </div>

<script>
function togglePass(inputId, iconEl) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') { input.type = 'text'; iconEl.textContent = '🙈'; }
    else { input.type = 'password'; iconEl.textContent = '👁️'; }
}
</script>
</body>
</html>