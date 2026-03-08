<?php
require_once __DIR__ . '/config/database.php';
requireLogin();
$pdo = getDB();

$msg   = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama    = trim($_POST['nama'] ?? '');
        $alamat  = trim($_POST['alamat'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');

        // Handle foto upload
        $foto = $user['foto'] ?? '';
        if (!empty($_FILES['foto']['name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed) && $_FILES['foto']['size'] < 2*1024*1024) {
                $namaFile = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $tujuan   = __DIR__ . '/uploads/' . $namaFile;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $tujuan)) {
                    // Hapus foto lama
                    if ($foto && file_exists(__DIR__ . '/uploads/' . $foto)) {
                        unlink(__DIR__ . '/uploads/' . $foto);
                    }
                    $foto = $namaFile;
                }
            } else {
                $error = 'Foto harus JPG/PNG/WEBP dan maksimal 2MB.';
            }
        }

        if (!$error) {
            $pdo->prepare("UPDATE users SET nama=?, alamat=?, telepon=?, foto=? WHERE id=?")
                ->execute([$nama, $alamat, $telepon, $foto, $_SESSION['user_id']]);
            $_SESSION['nama'] = $nama;
            $msg = 'Profil berhasil diperbarui!';
        }

    } elseif ($action === 'ganti_password') {
        $lama    = $_POST['password_lama'] ?? '';
        $baru    = $_POST['password_baru'] ?? '';
        $konfirm = $_POST['konfirm'] ?? '';

        if (!password_verify($lama, $user['password'])) {
            $error = 'Password lama salah!';
        } elseif (strlen($baru) < 8) {
            $error = 'Password baru minimal 8 karakter!';
        } elseif ($baru !== $konfirm) {
            $error = 'Konfirmasi password tidak cocok!';
        } else {
            $hash = password_hash($baru, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $_SESSION['user_id']]);
            $msg = 'Password berhasil diubah!';
        }
    }

    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Statistik user
$totalPesanan  = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE user_id = ?");
$totalPesanan->execute([$_SESSION['user_id']]);
$totalPesanan  = $totalPesanan->fetchColumn();

$totalSelesai  = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE user_id = ? AND status = 'selesai'");
$totalSelesai->execute([$_SESSION['user_id']]);
$totalSelesai  = $totalSelesai->fetchColumn();

$totalBelanja  = $pdo->prepare("SELECT COALESCE(SUM(total_harga),0) FROM pesanan WHERE user_id = ? AND status = 'selesai'");
$totalBelanja->execute([$_SESSION['user_id']]);
$totalBelanja  = $totalBelanja->fetchColumn();

// Riwayat pesanan
$pesananList = $pdo->prepare("SELECT * FROM pesanan WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$pesananList->execute([$_SESSION['user_id']]);
$pesananList = $pesananList->fetchAll();

$tab = $_GET['tab'] ?? 'profil';
$pageTitle = 'Profil Saya - TokoKu';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.profil-layout{display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start}
.profil-card{background:white;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.07);overflow:hidden}
.profil-avatar-wrap{position:relative;width:90px;height:90px;margin:0 auto 12px}
.profil-avatar{width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #ee4d2d}
.profil-avatar-initial{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#ee4d2d,#ff6b35);display:flex;align-items:center;justify-content:center;color:white;font-size:2.2rem;font-weight:800;border:3px solid #ee4d2d}
.profil-avatar-btn{position:absolute;bottom:0;right:0;width:28px;height:28px;background:#ee4d2d;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid white;font-size:.8rem}
.profil-tab{display:flex;gap:4px;background:#f5f5f5;border-radius:12px;padding:4px;margin-bottom:20px}
.profil-tab a{flex:1;text-align:center;padding:9px 8px;border-radius:9px;text-decoration:none;font-size:.82rem;font-weight:600;color:#757575;transition:all .2s}
.profil-tab a.active{background:white;color:#ee4d2d;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.stat-box{background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border-radius:12px;padding:14px;text-align:center}
.stat-box.green{background:linear-gradient(135deg,#00b14f,#00c853)}
.stat-box.blue{background:linear-gradient(135deg,#1976d2,#42a5f5)}
.stat-num{font-size:1.4rem;font-weight:900;line-height:1}
.stat-lbl{font-size:.7rem;opacity:.85;margin-top:3px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.pass-wrap{position:relative}
.pass-wrap input{padding-right:44px}
.pass-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9e9e9e;font-size:.95rem;user-select:none}
.pass-eye:hover{color:#ee4d2d}
.strength-bar{height:4px;border-radius:2px;margin-top:5px;background:#e0e0e0;transition:all .3s}
.strength-txt{font-size:.7rem;font-weight:600;margin-top:3px}
.pesanan-item{border:1.5px solid #f0f0f0;border-radius:12px;padding:14px 16px;margin-bottom:10px;transition:all .2s}
.pesanan-item:hover{border-color:#ee4d2d;box-shadow:0 2px 8px rgba(238,77,45,.1)}
@media(max-width:768px){.profil-layout{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}}
</style>

<div class="container" style="padding-top:20px;padding-bottom:40px">
    <div class="breadcrumb" style="margin-bottom:16px">
        <a href="<?= BASE_PATH ?>/index.php">🏠 Beranda</a>
        <span class="sep">›</span>
        <span class="current">Profil Saya</span>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success" style="margin-bottom:16px">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:16px">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profil-layout">

        <!-- SIDEBAR KIRI -->
        <div>
            <!-- Kartu profil -->
            <div class="profil-card" style="padding:24px;text-align:center;margin-bottom:16px">
                <div class="profil-avatar-wrap">
                    <?php if (!empty($user['foto']) && file_exists(__DIR__.'/uploads/'.$user['foto'])): ?>
                        <img src="<?= BASE_PATH ?>/uploads/<?= htmlspecialchars($user['foto']) ?>" class="profil-avatar" alt="Foto Profil">
                    <?php else: ?>
                        <div class="profil-avatar-initial"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
                    <?php endif; ?>
                    <label for="fotoInput" class="profil-avatar-btn" title="Ganti foto">📷</label>
                </div>
                <div style="font-weight:800;font-size:1.05rem;color:#212121;margin-bottom:3px"><?= htmlspecialchars($user['nama']) ?></div>
                <div style="font-size:.8rem;color:#9e9e9e;margin-bottom:16px"><?= htmlspecialchars($user['email']) ?></div>
                <div style="text-align:left;font-size:.82rem;border-top:1px solid #f0f0f0;padding-top:14px">
                    <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f5f5f5;color:#616161">
                        <span>📱</span><span><?= htmlspecialchars($user['telepon'] ?? 'Belum diisi') ?></span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:8px;padding:7px 0;border-bottom:1px solid #f5f5f5;color:#616161">
                        <span>📍</span><span><?= htmlspecialchars(mb_strimwidth($user['alamat'] ?? 'Belum diisi',0,50,'...')) ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;padding:7px 0;color:#616161">
                        <span>📅</span><span>Bergabung <?= date('d M Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Statistik -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
                <div class="stat-box">
                    <div class="stat-num"><?= $totalPesanan ?></div>
                    <div class="stat-lbl">Total Pesanan</div>
                </div>
                <div class="stat-box green">
                    <div class="stat-num"><?= $totalSelesai ?></div>
                    <div class="stat-lbl">Selesai</div>
                </div>
            </div>
            <div class="stat-box blue" style="margin-bottom:16px">
                <div class="stat-num" style="font-size:1.1rem"><?= rupiahFormat($totalBelanja) ?></div>
                <div class="stat-lbl">Total Belanja</div>
            </div>

            <!-- Link cepat -->
            <div class="profil-card" style="padding:12px">
                <a href="<?= BASE_PATH ?>/produk.php" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;text-decoration:none;color:#424242;font-size:.84rem;font-weight:600;transition:all .2s" onmouseover="this.style.background='#fff5f3';this.style.color='#ee4d2d'" onmouseout="this.style.background='';this.style.color='#424242'">
                    <span>🛍️</span> Lanjut Belanja
                </a>
                <a href="<?= BASE_PATH ?>/keranjang.php" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;text-decoration:none;color:#424242;font-size:.84rem;font-weight:600;transition:all .2s" onmouseover="this.style.background='#fff5f3';this.style.color='#ee4d2d'" onmouseout="this.style.background='';this.style.color='#424242'">
                    <span>🛒</span> Keranjang Saya
                </a>
                <a href="<?= BASE_PATH ?>/logout.php" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;text-decoration:none;color:#f44336;font-size:.84rem;font-weight:600;transition:all .2s" onmouseover="this.style.background='#fce4ec'" onmouseout="this.style.background=''">
                    <span>🚪</span> Keluar
                </a>
            </div>
        </div>

        <!-- KONTEN KANAN -->
        <div>
            <!-- Tab navigasi -->
            <div class="profil-tab">
                <?php
                $tabs = ['profil'=>'👤 Edit Profil','password'=>'🔒 Ganti Password','pesanan'=>'📦 Riwayat Pesanan'];
                foreach ($tabs as $key => $label):
                ?>
                <a href="?tab=<?= $key ?>" class="<?= $tab===$key?'active':'' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($tab === 'profil'): ?>
            <!-- EDIT PROFIL -->
            <div class="profil-card" style="padding:24px">
                <div style="font-size:1rem;font-weight:800;color:#212121;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #ee4d2d">👤 Edit Profil</div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profil">
                    <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none" onchange="previewFoto(this)">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap <span style="color:#ee4d2d">*</span></label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color:#9e9e9e;font-size:.75rem">(tidak bisa diubah)</span></label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f5f5f5;color:#9e9e9e">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>Foto Profil <span style="color:#9e9e9e;font-size:.75rem">(JPG/PNG, maks 2MB)</span></label>
                            <div style="display:flex;align-items:center;gap:10px">
                                <img id="fotoPreview" src="<?= !empty($user['foto']) && file_exists(__DIR__.'/uploads/'.$user['foto']) ? BASE_PATH.'/uploads/'.htmlspecialchars($user['foto']) : '' ?>"
                                     style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #ee4d2d;<?= empty($user['foto']) ? 'display:none' : '' ?>">
                                <label for="fotoInput" class="btn btn-outline btn-sm" style="cursor:pointer;margin:0">📷 Pilih Foto</label>
                                <span id="fotoNama" style="font-size:.75rem;color:#9e9e9e"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Jl. Contoh No. 1, Kota, Provinsi"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:11px 28px">💾 Simpan Perubahan</button>
                </form>
            </div>

            <?php elseif ($tab === 'password'): ?>
            <!-- GANTI PASSWORD -->
            <div class="profil-card" style="padding:24px">
                <div style="font-size:1rem;font-weight:800;color:#212121;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #ee4d2d">🔒 Ganti Password</div>
                <form method="POST" style="max-width:420px">
                    <input type="hidden" name="action" value="ganti_password">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <div class="pass-wrap">
                            <input type="password" name="password_lama" id="passLama" class="form-control" required placeholder="Masukkan password lama">
                            <span class="pass-eye" onclick="togglePass('passLama',this)">👁️</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <div class="pass-wrap">
                            <input type="password" name="password_baru" id="passBaru" class="form-control" required placeholder="Min. 8 karakter" oninput="cekKekuatan(this.value)">
                            <span class="pass-eye" onclick="togglePass('passBaru',this)">👁️</span>
                        </div>
                        <div class="strength-bar" id="strengthBar"></div>
                        <div class="strength-txt" id="strengthTxt"></div>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <div class="pass-wrap">
                            <input type="password" name="konfirm" id="passKonfirm" class="form-control" required placeholder="Ulangi password baru" oninput="cekKonfirm()">
                            <span class="pass-eye" onclick="togglePass('passKonfirm',this)">👁️</span>
                        </div>
                        <div id="konfirmTxt" style="font-size:.7rem;font-weight:600;margin-top:3px"></div>
                    </div>
                    <div style="background:#fff8e1;border-radius:8px;padding:12px;margin-bottom:16px;font-size:.8rem;color:#f57f17">
                        💡 Tips password kuat: gabungkan huruf besar, angka, dan simbol
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:11px 28px">🔒 Ganti Password</button>
                </form>
            </div>

            <?php elseif ($tab === 'pesanan'): ?>
            <!-- RIWAYAT PESANAN -->
            <div class="profil-card" style="padding:24px">
                <div style="font-size:1rem;font-weight:800;color:#212121;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #ee4d2d">📦 Riwayat Pesanan</div>
                <?php if (empty($pesananList)): ?>
                    <div style="text-align:center;padding:48px 20px;color:#9e9e9e">
                        <div style="font-size:3.5rem;margin-bottom:12px">📦</div>
                        <div style="font-weight:700;font-size:1rem;color:#424242;margin-bottom:6px">Belum ada pesanan</div>
                        <p style="font-size:.84rem;margin-bottom:20px">Yuk mulai belanja dan temukan produk favoritmu!</p>
                        <a href="<?= BASE_PATH ?>/produk.php" class="btn btn-primary">🛍️ Mulai Belanja</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pesananList as $p):
                        $statusConfig = [
                            'pending'    => ['bg'=>'#fff8e1','color'=>'#f57f17','icon'=>'⏳','label'=>'Menunggu'],
                            'diproses'   => ['bg'=>'#e3f2fd','color'=>'#1565c0','icon'=>'🔄','label'=>'Diproses'],
                            'dikirim'    => ['bg'=>'#e8f5e9','color'=>'#2e7d32','icon'=>'🚚','label'=>'Dikirim'],
                            'selesai'    => ['bg'=>'#e8f5e9','color'=>'#2e7d32','icon'=>'✅','label'=>'Selesai'],
                            'dibatalkan' => ['bg'=>'#fce4ec','color'=>'#c62828','icon'=>'❌','label'=>'Dibatalkan'],
                        ];
                        $sc = $statusConfig[$p['status']] ?? ['bg'=>'#f5f5f5','color'=>'#757575','icon'=>'📋','label'=>ucfirst($p['status'])];
                    ?>
                    <div class="pesanan-item">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#212121;margin-bottom:3px">
                                    <?= $sc['icon'] ?> <code style="font-size:.78rem;background:#f5f5f5;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($p['kode_pesanan']) ?></code>
                                </div>
                                <div style="font-size:.75rem;color:#9e9e9e"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></div>
                            </div>
                            <div style="text-align:right">
                                <div style="font-size:1rem;font-weight:800;color:#ee4d2d"><?= rupiahFormat($p['total_harga']) ?></div>
                                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                                    <?= $sc['label'] ?>
                                </span>
                            </div>
                        </div>
                        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f5f5f5;display:flex;gap:8px">
                            <a href="<?= BASE_PATH ?>/invoice.php?kode=<?= $p['kode_pesanan'] ?>" target="_blank"
                               class="btn btn-outline btn-sm">🖨️ Invoice</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function togglePass(id, el) {
    const inp = document.getElementById(id);
    if (inp.type==='password'){inp.type='text';el.textContent='🙈';}
    else{inp.type='password';el.textContent='👁️';}
}
function cekKekuatan(val) {
    let score=0;
    if(val.length>=8)score++;
    if(/[A-Z]/.test(val))score++;
    if(/[0-9]/.test(val))score++;
    if(/[^A-Za-z0-9]/.test(val))score++;
    const levels=[
        {w:'0%',c:'#e0e0e0',t:''},
        {w:'25%',c:'#f44336',t:'Lemah'},
        {w:'50%',c:'#ff9800',t:'Cukup'},
        {w:'75%',c:'#ffc107',t:'Kuat'},
        {w:'100%',c:'#4caf50',t:'Sangat Kuat'},
    ];
    const lv=levels[score]||levels[0];
    const bar=document.getElementById('strengthBar');
    const txt=document.getElementById('strengthTxt');
    bar.style.width=lv.w;bar.style.background=lv.c;
    txt.textContent=lv.t;txt.style.color=lv.c;
}
function cekKonfirm() {
    const p1=document.getElementById('passBaru').value;
    const p2=document.getElementById('passKonfirm').value;
    const txt=document.getElementById('konfirmTxt');
    if(!p2){txt.textContent='';return;}
    if(p1===p2){txt.textContent='✅ Password cocok';txt.style.color='#4caf50';}
    else{txt.textContent='❌ Tidak cocok';txt.style.color='#f44336';}
}
function previewFoto(input) {
    if(input.files && input.files[0]){
        const reader=new FileReader();
        reader.onload=function(e){
            const img=document.getElementById('fotoPreview');
            img.src=e.target.result;
            img.style.display='block';
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('fotoNama').textContent=input.files[0].name;
        // Update avatar di sidebar juga
        const avatarEl=document.querySelector('.profil-avatar, .profil-avatar-initial');
        if(avatarEl && avatarEl.tagName==='IMG'){
            const reader2=new FileReader();
            reader2.onload=function(e){avatarEl.src=e.target.result;};
            reader2.readAsDataURL(input.files[0]);
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>