<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$token = trim($_GET['token'] ?? '');
$step  = 'form';
$msgErr = '';
$tokenRow = null;

// Validasi token
if ($token) {
    $stmt = $pdo->prepare("SELECT pr.*, u.nama FROM password_resets pr 
                           JOIN users u ON u.id = pr.user_id 
                           WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch();
}

if (!$tokenRow) {
    $step = 'invalid';
}

// Proses form ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step !== 'invalid') {
    $password  = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (strlen($password) < 8) {
        $msgErr = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $msgErr = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $tokenRow['user_id']]);
        $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);
        $step = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - TokoKu</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;min-height:100vh;display:flex;background:#f5f5f5}
.left-panel{flex:1;background:linear-gradient(135deg,#ee4d2d 0%,#ff6b35 50%,#f5a623 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;position:relative;overflow:hidden}
.left-panel::before{content:'';position:absolute;width:400px;height:400px;background:rgba(255,255,255,.08);border-radius:50%;top:-100px;left:-100px}
.left-panel::after{content:'';position:absolute;width:300px;height:300px;background:rgba(255,255,255,.06);border-radius:50%;bottom:-80px;right:-80px}
.left-logo{font-size:2.5rem;font-weight:900;color:white;margin-bottom:8px;letter-spacing:-1px;position:relative;z-index:1}
.left-logo span{color:#ffe066}
.left-tagline{color:rgba(255,255,255,.9);font-size:1rem;margin-bottom:40px;position:relative;z-index:1}
.left-features{display:flex;flex-direction:column;gap:16px;position:relative;z-index:1;width:100%;max-width:320px}
.feature-item{display:flex;align-items:center;gap:14px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);border-radius:12px;padding:14px 16px;color:white}
.feature-icon{font-size:1.6rem}
.feature-text strong{display:block;font-size:.88rem;font-weight:700}
.feature-text span{font-size:.75rem;opacity:.85}
.right-panel{width:460px;background:white;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 40px;box-shadow:-4px 0 24px rgba(0,0,0,.08)}
.form-logo{font-size:1.6rem;font-weight:900;color:#ee4d2d;margin-bottom:6px;letter-spacing:-.5px}
.form-logo span{color:#f5a623}
.form-subtitle{color:#757575;font-size:.88rem;margin-bottom:28px}
.form-group{width:100%;margin-bottom:18px}
.form-group label{display:block;font-size:.82rem;font-weight:600;color:#424242;margin-bottom:6px}
.form-group input{width:100%;padding:12px 44px 12px 14px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.92rem;outline:none;background:#fafafa;transition:all .2s}
.form-group input:focus{border-color:#ee4d2d;background:white;box-shadow:0 0 0 3px rgba(238,77,45,.1)}
.pass-wrapper{position:relative}
.pass-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:1rem;color:#9e9e9e;user-select:none}
.pass-toggle:hover{color:#ee4d2d}
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear,
input[type="password"]::-webkit-contacts-auto-fill-button,
input[type="password"]::-webkit-credentials-auto-fill-button{display:none!important;visibility:hidden;pointer-events:none}
.btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;transition:all .2s}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(238,77,45,.3)}
.strength-bar{height:4px;border-radius:2px;margin-top:6px;transition:all .3s;background:#e0e0e0}
.strength-text{font-size:.72rem;margin-top:4px;font-weight:600}
.auth-icon{width:68px;height:68px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px}
@media(max-width:768px){.left-panel{display:none}.right-panel{width:100%;padding:32px 24px}}
</style>
</head>
<body>
<div class="left-panel">
    <div class="left-logo">Toko<span>Ku</span></div>
    <div class="left-tagline">Belanja Online Terpercaya #1</div>
    <div class="left-features">
        <div class="feature-item"><span class="feature-icon">🔑</span><div class="feature-text"><strong>Buat Password Baru</strong><span>Minimal 8 karakter untuk keamanan</span></div></div>
        <div class="feature-item"><span class="feature-icon">🔒</span><div class="feature-text"><strong>Password Terenkripsi</strong><span>Data kamu aman & terlindungi</span></div></div>
        <div class="feature-item"><span class="feature-icon">✅</span><div class="feature-text"><strong>Langsung Aktif</strong><span>Login dengan password baru setelah reset</span></div></div>
    </div>
</div>

<div class="right-panel">
    <div class="form-logo">Toko<span>Ku</span></div>
    <div class="form-subtitle">Platform belanja online terpercaya</div>

    <?php if ($step === 'invalid'): ?>
        <div class="auth-icon" style="background:#ffebee">❌</div>
        <div style="font-size:1.2rem;font-weight:800;color:#c62828;margin-bottom:8px;text-align:center">Link Tidak Valid</div>
        <p style="text-align:center;color:#9e9e9e;font-size:.84rem;margin-bottom:20px;line-height:1.6">
            Link reset password sudah kadaluarsa, sudah digunakan, atau tidak valid.<br>Silakan minta kode reset baru.
        </p>
        <a href="<?= BASE_PATH ?>/lupa-password.php"
           style="display:block;width:100%;padding:13px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border-radius:8px;font-size:.95rem;font-weight:700;text-align:center;text-decoration:none;margin-bottom:12px">
            🔑 Minta Kode Baru
        </a>
        <div style="text-align:center;font-size:.84rem">
            <a href="<?= BASE_PATH ?>/login.php" style="color:#ee4d2d;font-weight:600;text-decoration:none">← Kembali Login</a>
        </div>

    <?php elseif ($step === 'success'): ?>
        <div class="auth-icon" style="background:#e8f5e9">✅</div>
        <div style="font-size:1.2rem;font-weight:800;color:#2e7d32;margin-bottom:8px;text-align:center">Password Berhasil Diubah!</div>
        <p style="text-align:center;color:#9e9e9e;font-size:.84rem;margin-bottom:20px;line-height:1.6">
            Password akun <strong><?= htmlspecialchars($tokenRow['email']) ?></strong> sudah berhasil diperbarui. Silakan login dengan password baru kamu.
        </p>
        <a href="<?= BASE_PATH ?>/login.php"
           style="display:block;width:100%;padding:13px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border-radius:8px;font-size:.95rem;font-weight:700;text-align:center;text-decoration:none">
            Masuk Sekarang →
        </a>

    <?php else: ?>
        <div style="font-size:1.3rem;font-weight:800;color:#212121;margin-bottom:4px;width:100%">Buat Password Baru</div>
        <p style="color:#9e9e9e;font-size:.84rem;margin-bottom:22px;width:100%;line-height:1.6">
            Halo <strong><?= htmlspecialchars(explode(' ', $tokenRow['nama'])[0]) ?></strong>! Masukkan password baru untuk akun kamu.
        </p>

        <?php if ($msgErr): ?>
            <div style="width:100%;background:#ffebee;color:#c62828;padding:11px 14px;border-radius:8px;font-size:.84rem;margin-bottom:16px;border-left:3px solid #f44336">
                ❌ <?= htmlspecialchars($msgErr) ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="width:100%">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label>Password Baru</label>
                <div class="pass-wrapper">
                    <input type="password" name="password" id="pass1" placeholder="Minimal 8 karakter" required
                           oninput="cekKekuatan(this.value)">
                    <span class="pass-toggle" onclick="togglePass('pass1',this)">👁️</span>
                </div>
                <div class="strength-bar" id="strengthBar"></div>
                <div class="strength-text" id="strengthText"></div>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <div class="pass-wrapper">
                    <input type="password" name="konfirmasi" id="pass2" placeholder="Ulangi password baru" required
                           oninput="cekKonfirmasi()">
                    <span class="pass-toggle" onclick="togglePass('pass2',this)">👁️</span>
                </div>
                <div id="matchText" style="font-size:.72rem;margin-top:4px;font-weight:600"></div>
            </div>

            <button type="submit" class="btn-submit">🔐 Simpan Password Baru</button>
        </form>

        <div style="text-align:center;margin-top:16px;font-size:.84rem">
            <a href="<?= BASE_PATH ?>/login.php" style="color:#ee4d2d;font-weight:600;text-decoration:none">← Kembali Login</a>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePass(id, el) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') { inp.type = 'text'; el.textContent = '🙈'; }
    else { inp.type = 'password'; el.textContent = '👁️'; }
}

function cekKekuatan(val) {
    const bar  = document.getElementById('strengthBar');
    const txt  = document.getElementById('strengthText');
    let score  = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        {w:'0%',   c:'#e0e0e0', t:''},
        {w:'25%',  c:'#f44336', t:'Lemah'},
        {w:'50%',  c:'#ff9800', t:'Cukup'},
        {w:'75%',  c:'#ffc107', t:'Kuat'},
        {w:'100%', c:'#4caf50', t:'Sangat Kuat'},
    ];
    const lv = levels[score] || levels[0];
    bar.style.width = lv.w; bar.style.background = lv.c;
    txt.textContent = lv.t; txt.style.color = lv.c;
}

function cekKonfirmasi() {
    const p1  = document.getElementById('pass1').value;
    const p2  = document.getElementById('pass2').value;
    const txt = document.getElementById('matchText');
    if (!p2) { txt.textContent = ''; return; }
    if (p1 === p2) { txt.textContent = '✅ Password cocok'; txt.style.color = '#4caf50'; }
    else           { txt.textContent = '❌ Password tidak cocok'; txt.style.color = '#f44336'; }
}
</script>
</body>
</html>