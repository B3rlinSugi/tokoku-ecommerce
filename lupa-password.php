<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$step      = 'form';
$msgErr    = '';
$tokenInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msgErr = 'Format email tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nama FROM users WHERE email = ? AND role = 'pelanggan'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
                $token     = bin2hex(random_bytes(32));
                // Gunakan NOW() MySQL agar tidak ada masalah timezone
                $pdo->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR))")
                    ->execute([$user["id"], $email, $token]);
                $tokenInfo = ['nama' => $user['nama'], 'token' => $token, 'email' => $email];
            }
            $step = 'sent'; // selalu sent agar tidak bocor info email
        } catch (Exception $e) {
            $msgErr = 'Terjadi kesalahan sistem. Pastikan tabel password_resets sudah dibuat di database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lupa Password - TokoKu</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;min-height:100vh;display:flex;background:#f5f5f5;}
.left-panel{flex:1;background:linear-gradient(135deg,#ee4d2d 0%,#ff6b35 50%,#f5a623 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;position:relative;overflow:hidden;}
.left-panel::before{content:'';position:absolute;width:400px;height:400px;background:rgba(255,255,255,.08);border-radius:50%;top:-100px;left:-100px;}
.left-panel::after{content:'';position:absolute;width:300px;height:300px;background:rgba(255,255,255,.06);border-radius:50%;bottom:-80px;right:-80px;}
.left-logo{font-size:2.5rem;font-weight:900;color:white;margin-bottom:8px;letter-spacing:-1px;position:relative;z-index:1;}
.left-logo span{color:#ffe066}
.left-tagline{color:rgba(255,255,255,.9);font-size:1rem;margin-bottom:40px;position:relative;z-index:1;}
.left-features{display:flex;flex-direction:column;gap:16px;position:relative;z-index:1;width:100%;max-width:320px;}
.feature-item{display:flex;align-items:center;gap:14px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);border-radius:12px;padding:14px 16px;color:white;}
.feature-icon{font-size:1.6rem;}
.feature-text strong{display:block;font-size:.88rem;font-weight:700;}
.feature-text span{font-size:.75rem;opacity:.85;}
.right-panel{width:460px;background:white;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 40px;box-shadow:-4px 0 24px rgba(0,0,0,.08);}
.form-logo{font-size:1.6rem;font-weight:900;color:#ee4d2d;margin-bottom:6px;letter-spacing:-.5px;}
.form-logo span{color:#f5a623}
.form-subtitle{color:#757575;font-size:.88rem;margin-bottom:28px;}
.auth-icon{width:68px;height:68px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px;}
.token-box{background:#f8f9fa;border:2px dashed #dee2e6;border-radius:10px;padding:16px;margin:12px 0;text-align:center;cursor:pointer;}
.token-code{font-family:monospace;font-size:.78rem;color:#1a1a2e;word-break:break-all;font-weight:600;}
@media(max-width:768px){.left-panel{display:none}.right-panel{width:100%;padding:32px 24px}}
</style>
    <link rel="shortcut icon" href="/assets/images/favicon.svg" type="image/svg+xml">
</head>
<body>

<div class="left-panel">
    <div class="left-logo">Toko<span>Ku</span></div>
    <div class="left-tagline">Belanja Online Terpercaya #1</div>
    <div class="left-features">
        <div class="feature-item"><span class="feature-icon">🔑</span><div class="feature-text"><strong>Reset Password Mudah</strong><span>Masukkan email & ikuti langkahnya</span></div></div>
        <div class="feature-item"><span class="feature-icon">🔒</span><div class="feature-text"><strong>Keamanan Terjamin</strong><span>Token berlaku hanya 1 jam</span></div></div>
        <div class="feature-item"><span class="feature-icon">⚡</span><div class="feature-text"><strong>Proses Cepat</strong><span>Akun aktif kembali dalam hitungan menit</span></div></div>
    </div>
</div>

<div class="right-panel">
    <div class="form-logo">Toko<span>Ku</span></div>
    <div class="form-subtitle">Platform belanja online terpercaya</div>

    <?php if ($step === 'form'): ?>

        <div style="font-size:1.3rem;font-weight:800;color:#212121;margin-bottom:6px;width:100%;">Lupa Password?</div>
        <p style="color:#9e9e9e;font-size:.86rem;margin-bottom:24px;width:100%;line-height:1.6;">
            Masukkan email akun kamu. Kami akan memberikan kode reset password.
        </p>

        <?php if ($msgErr): ?>
            <div style="width:100%;background:#ffebee;color:#c62828;padding:11px 14px;border-radius:8px;font-size:.84rem;margin-bottom:16px;border-left:3px solid #f44336;">
                ❌ <?= htmlspecialchars($msgErr) ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="width:100%;">
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#424242;margin-bottom:6px;">📧 Alamat Email</label>
                <input type="email" name="email"
                       placeholder="contoh@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autofocus
                       style="width:100%;padding:12px 14px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.92rem;outline:none;background:#fafafa;transition:all .2s;"
                       onfocus="this.style.borderColor='#ee4d2d';this.style.background='white';this.style.boxShadow='0 0 0 3px rgba(238,77,45,.1)'"
                       onblur="this.style.borderColor='#e0e0e0';this.style.background='#fafafa';this.style.boxShadow='none'">
            </div>
            <button type="submit"
                    style="width:100%;padding:13px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;transition:all .2s;"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(238,77,45,.3)'"
                    onmouseout="this.style.transform='';this.style.boxShadow=''">
                🔐 Kirim Kode Reset
            </button>
        </form>

        <div style="text-align:center;margin-top:20px;font-size:.84rem;color:#757575;">
            Ingat password? <a href="<?= BASE_PATH ?>/login.php" style="color:#ee4d2d;font-weight:700;text-decoration:none;">Masuk sekarang</a>
        </div>

    <?php elseif ($step === 'sent'): ?>

        <?php if ($tokenInfo): ?>
        <!-- USER TERDAFTAR — tampilkan token (simulasi XAMPP) -->
        <div class="auth-icon" style="background:#e8f5e9;">✅</div>
        <div style="font-size:1.2rem;font-weight:800;color:#212121;margin-bottom:6px;text-align:center;">Kode Reset Ditemukan!</div>
        <p style="text-align:center;color:#9e9e9e;font-size:.84rem;margin-bottom:16px;line-height:1.6;">
            Halo <strong><?= htmlspecialchars(explode(' ', $tokenInfo['nama'])[0]) ?></strong>!
            Di aplikasi nyata kode dikirim via email.<br>
            Karena mode lokal (XAMPP), kode tampil di sini:
        </p>

        <div style="width:100%;background:#fff8e1;border:1.5px solid #ffe066;border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="font-size:.72rem;color:#f5a623;font-weight:700;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;">🔑 Kode Reset Password</div>
            <div class="token-box" onclick="salinToken(this)" title="Klik untuk salin">
                <div class="token-code" id="tokenCode"><?= htmlspecialchars($tokenInfo['token']) ?></div>
                <div style="font-size:.7rem;color:#9e9e9e;margin-top:6px;">Klik untuk menyalin · Berlaku 1 jam</div>
            </div>
            <div style="font-size:.73rem;color:#9e9e9e;text-align:center;">📧 Untuk: <?= htmlspecialchars($tokenInfo['email']) ?></div>
        </div>

        <a href="<?= BASE_PATH ?>/reset-password.php?token=<?= urlencode($tokenInfo['token']) ?>"
           style="display:block;width:100%;padding:13px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;text-align:center;text-decoration:none;"
           onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
            Lanjut Reset Password →
        </a>

        <?php else: ?>
        <!-- USER TIDAK TERDAFTAR — pesan generik (security best practice) -->
        <div class="auth-icon" style="background:#e3f2fd;">📧</div>
        <div style="font-size:1.2rem;font-weight:800;color:#212121;margin-bottom:6px;text-align:center;">Cek Email Kamu</div>
        <p style="text-align:center;color:#9e9e9e;font-size:.84rem;margin-bottom:16px;line-height:1.6;">
            Jika email tersebut terdaftar di sistem kami, kamu akan menerima instruksi reset password.
        </p>
        <div style="width:100%;background:#e3f2fd;border-radius:10px;padding:14px;margin-bottom:16px;font-size:.83rem;color:#1565c0;text-align:center;">
            ℹ️ Tidak menerima email? Hubungi admin toko untuk reset manual.
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:16px;font-size:.84rem;color:#757575;">
            <a href="<?= BASE_PATH ?>/lupa-password.php" style="color:#ee4d2d;font-weight:600;text-decoration:none;">← Coba email lain</a>
            &nbsp;·&nbsp;
            <a href="<?= BASE_PATH ?>/login.php" style="color:#ee4d2d;font-weight:600;text-decoration:none;">Kembali login</a>
        </div>

    <?php endif; ?>
</div>

<script>
function salinToken(el) {
    const teks = document.getElementById('tokenCode').innerText;
    navigator.clipboard.writeText(teks).then(() => {
        const info = el.querySelector('div:last-child');
        const semula = info.textContent;
        info.textContent = '✅ Tersalin!';
        info.style.color = '#00b14f';
        setTimeout(() => { info.textContent = semula; info.style.color = '#9e9e9e'; }, 2000);
    });
}
</script>
</body>
</html>