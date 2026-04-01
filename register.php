<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi CSRF token
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    // Sanitasi semua input
    $nama    = sanitize($_POST['nama'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $alamat  = sanitize($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirm'] ?? '';

    // Validasi email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (!$nama || !$email || !$password) {
        $error = 'Isi semua field wajib!';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($telepon && !preg_match('/^[0-9+\-\s]{8,15}$/', $telepon)) {
        $error = 'Format nomor telepon tidak valid!';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar!';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (nama, email, password, alamat, telepon) VALUES (?,?,?,?,?)")
                ->execute([$nama, $email, $hash, $alamat, $telepon]);
            $success = true;
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - TokoKu</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; background: #f5f5f5; }
        input[type="password"]::-ms-reveal, input[type="password"]::-ms-clear, input[type="password"]::-webkit-contacts-auto-fill-button, input[type="password"]::-webkit-credentials-auto-fill-button { display: none !important; visibility: hidden; pointer-events: none; }
        .left-panel { flex: 1; background: linear-gradient(135deg, #ee4d2d 0%, #ff6b35 50%, #f5a623 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; position: relative; overflow: hidden; }
        .left-panel::before { content: ''; position: absolute; width: 400px; height: 400px; background: rgba(255,255,255,0.08); border-radius: 50%; top: -100px; left: -100px; }
        .left-panel::after { content: ''; position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,0.06); border-radius: 50%; bottom: -80px; right: -80px; }
        .left-logo { font-size: 2.5rem; font-weight: 900; color: white; margin-bottom: 8px; letter-spacing: -1px; position: relative; z-index: 1; }
        .left-logo span { color: #ffe066; }
        .left-tagline { color: rgba(255,255,255,0.9); font-size: 1rem; margin-bottom: 32px; position: relative; z-index: 1; }
        .benefit-list { position: relative; z-index: 1; width: 100%; max-width: 320px; }
        .benefit-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.15); color: white; font-size: 0.88rem; }
        .benefit-item:last-child { border-bottom: none; }
        .benefit-item .icon { font-size: 1.3rem; width: 30px; text-align: center; }
        .right-panel { width: 500px; background: white; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; box-shadow: -4px 0 24px rgba(0,0,0,0.08); overflow-y: auto; }
        .form-logo { font-size: 1.5rem; font-weight: 900; color: #ee4d2d; margin-bottom: 4px; }
        .form-logo span { color: #f5a623; }
        .form-subtitle { color: #757575; font-size: 0.82rem; margin-bottom: 24px; }
        .form-title { font-size: 1.2rem; font-weight: 800; color: #212121; margin-bottom: 20px; width: 100%; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; width: 100%; }
        .form-group { width: 100%; margin-bottom: 14px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: #424242; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 11px 13px; border: 1.5px solid #e0e0e0; border-radius: 8px; font-size: 0.88rem; transition: all 0.2s; outline: none; background: #fafafa; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color: #ee4d2d; background: white; box-shadow: 0 0 0 3px rgba(238,77,45,0.1); }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .pass-wrapper { position: relative; }
        .pass-wrapper input { padding-right: 44px; }
        .pass-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1rem; color: #9e9e9e; user-select: none; line-height: 1; z-index: 2; }
        .pass-toggle:hover { color: #ee4d2d; }
        .btn-register { width: 100%; padding: 13px; background: linear-gradient(135deg, #ee4d2d, #ff6b35); color: white; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 4px; }
        .btn-register:hover { background: linear-gradient(135deg, #d73211, #ee4d2d); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(238,77,45,0.3); }
        .strength-bar { height: 3px; border-radius: 2px; margin-top: 5px; transition: all 0.3s; background: #e0e0e0; width: 0; }
        .strength-text { font-size: 0.7rem; margin-top: 3px; }
        .alert-error { width: 100%; background: #fce4ec; color: #c62828; border-left: 3px solid #f44336; border-radius: 6px; padding: 10px 14px; font-size: 0.82rem; margin-bottom: 14px; }
        .alert-success { width: 100%; background: #e8f5e9; color: #2e7d32; border-left: 3px solid #4caf50; border-radius: 6px; padding: 20px; font-size: 0.88rem; text-align: center; }
        .login-link { text-align: center; font-size: 0.85rem; color: #757575; margin-top: 16px; }
        .login-link a { color: #ee4d2d; font-weight: 700; text-decoration: none; }
        .required { color: #ee4d2d; }
        @media (max-width: 768px) { .left-panel { display: none; } .right-panel { width: 100%; padding: 28px 20px; } .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="left-panel">
        <div class="left-logo">Toko<span>Ku</span></div>
        <div class="left-tagline">Gabung jutaan pembeli di TokoKu!</div>
        <div class="benefit-list">
            <div class="benefit-item"><span class="icon">🎁</span><span>Voucher selamat datang untuk member baru</span></div>
            <div class="benefit-item"><span class="icon">🚚</span><span>Gratis ongkir ke seluruh Indonesia</span></div>
            <div class="benefit-item"><span class="icon">🔔</span><span>Notifikasi promo & flash sale eksklusif</span></div>
            <div class="benefit-item"><span class="icon">📦</span><span>Lacak pesanan secara real-time</span></div>
            <div class="benefit-item"><span class="icon">⭐</span><span>Ulasan & rating produk terpercaya</span></div>
            <div class="benefit-item"><span class="icon">🛡️</span><span>Jaminan uang kembali 100%</span></div>
        </div>
    </div>

    <div class="right-panel">
        <div class="form-logo">Toko<span>Ku</span></div>
        <div class="form-subtitle">Buat akun gratis dalam 1 menit</div>

        <?php if ($success): ?>
            <div class="alert-success">
                <div style="font-size:2.5rem; margin-bottom:10px;">🎉</div>
                <div style="font-weight:700; font-size:1.1rem; margin-bottom:8px;">Registrasi Berhasil!</div>
                <div style="margin-bottom:16px; color:#555;">Akun Anda sudah aktif. Silakan login sekarang.</div>
                <a href="<?= BASE_PATH ?>/login.php" style="background:#ee4d2d; color:white; padding:11px 28px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.9rem; display:inline-block;">Login Sekarang →</a>
            </div>
        <?php else: ?>

        <div class="form-title">Buat Akun Baru</div>

        <?php if ($error): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="width:100%;">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>👤 Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" placeholder="Nama Anda"
                           value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label>📱 No. Telepon</label>
                    <input type="text" name="telepon" placeholder="08xxxxxxxxxx"
                           value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>📧 Alamat Email <span class="required">*</span></label>
                <input type="email" name="email" placeholder="contoh@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>🔒 Password <span class="required">*</span></label>
                    <div class="pass-wrapper">
                        <input type="password" name="password" id="passInput"
                               placeholder="Min. 6 karakter" required
                               oninput="checkStrength(this.value)">
                        <span class="pass-toggle" onclick="togglePass('passInput', this)">👁️</span>
                    </div>
                    <div class="strength-bar" id="strengthBar"></div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
                <div class="form-group">
                    <label>🔒 Konfirmasi <span class="required">*</span></label>
                    <div class="pass-wrapper">
                        <input type="password" name="konfirm" id="konfirmInput"
                               placeholder="Ulangi password" required>
                        <span class="pass-toggle" onclick="togglePass('konfirmInput', this)">👁️</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>📍 Alamat</label>
                <textarea name="alamat" placeholder="Alamat lengkap (opsional)"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
            </div>

            <div style="display:flex; align-items:flex-start; gap:8px; margin-bottom:16px;">
                <input type="checkbox" required id="agreeCheck" style="margin-top:3px; accent-color:#ee4d2d; width:auto;">
                <label for="agreeCheck" style="font-size:0.78rem; color:#757575; cursor:pointer; line-height:1.5;">
                    Saya setuju dengan <a href="#" style="color:#ee4d2d;">Syarat & Ketentuan</a> dan <a href="#" style="color:#ee4d2d;">Kebijakan Privasi</a> TokoKu
                </label>
            </div>

            <button type="submit" class="btn-register">🎉 Daftar Sekarang — Gratis!</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="<?= BASE_PATH ?>/login.php">Masuk di sini</a>
        </div>

        <?php endif; ?>
    </div>

<script>
function togglePass(inputId, iconEl) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') { input.type = 'text'; iconEl.textContent = '🙈'; }
    else { input.type = 'password'; iconEl.textContent = '👁️'; }
}
function checkStrength(val) {
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    if (!val) { bar.style.width = '0'; text.textContent = ''; return; }
    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        { color:'#f44336', label:'Sangat Lemah', width:'20%' },
        { color:'#ff5722', label:'Lemah',        width:'40%' },
        { color:'#ff9800', label:'Sedang',       width:'60%' },
        { color:'#8bc34a', label:'Kuat',         width:'80%' },
        { color:'#4caf50', label:'Sangat Kuat',  width:'100%' },
    ];
    const lvl = levels[Math.min(score - 1, 4)] || levels[0];
    bar.style.cssText = `height:3px;border-radius:2px;margin-top:5px;transition:all 0.3s;background:${lvl.color};width:${lvl.width}`;
    text.style.color = lvl.color;
    text.textContent = lvl.label;
}
</script>
</body>
</html>