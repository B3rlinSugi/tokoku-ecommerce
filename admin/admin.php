<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    // optional: kirim flash juga
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Silakan login terlebih dahulu'];
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/config.php';
$foto_path="/../uploads/adat/";

try {
    // DSN (pakai port kalau perlu)
    if (!empty($DB_SOCKET ?? '')) {
        $dsn = "mysql:unix_socket={$DB_SOCKET};dbname={$DB_NAME};charset=utf8mb4";
    } else {
        $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    }
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // <<< ubah tabel ke tbkatalog
    $stmt = $pdo->query("
        SELECT idadat, nama_adat, foto_path, isi_desk, harga
        FROM tbkatalog
        ORDER BY idadat DESC
    ");
    $katalog = $stmt->fetchAll();
} catch (Throwable $e) {
    $katalog = [];
    // echo '<pre>DB ERROR: '.$e->getMessage().'</pre>'; // debug sementara
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Serenity Admin — Dashboard</title>

    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Icons -->
    <link href="https://unpkg.com/lucide-static@0.321.0/font/lucide.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg: #0f172a;
            /* slate-900 */
            --panel: #111827;
            /* gray-900 */
            --muted: #94a3b8;
            /* slate-400 */
            --text: #e5e7eb;
            /* gray-200 */
            --brand: #2563eb;
            /* blue-600 */
            --brand-2: #60a5fa;
            /* blue-400 */
            --ok: #10b981;
            /* green-500 */
            --warn: #f59e0b;
            /* amber-500 */
            --danger: #ef4444;
            /* red-500 */
        }

        body {
            background: linear-gradient(180deg, #0b1225 0%, #0f172a 100%);
            color: var(--text);
        }

        .card {
            background: rgba(17, 24, 39, .7);
            backdrop-filter: saturate(120%) blur(6px);
            border: 1px solid rgba(255, 255, 255, .06)
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: 12px;
            padding: .5rem 1rem;
            font-size: .875rem;
            font-weight: 500;
            transition: all .2s
        }

        .btn-primary {
            background: #2563eb;
            color: #fff
        }

        .btn-primary:hover {
            background: #1d4ed8
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .12);
            color: #e5e7eb
        }

        .btn-ghost:hover {
            border-color: rgba(255, 255, 255, .3)
        }

        .btn-danger {
            background: #ef4444;
            color: #fff
        }

        .btn-danger:hover {
            background: #dc2626
        }

        .btn-ok {
            background: #10b981;
            color: #fff
        }

        .btn-ok:hover {
            background: #059669
        }

        .btn-warn {
            background: #f59e0b;
            color: #fff
        }

        .btn-warn:hover {
            background: #d97706
        }

        table th {
            text-align: left;
            font-size: .75rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #cbd5e1
        }

        table td {
            font-size: .875rem;
            color: #e5e7eb
        }

        .badge {
            padding: .25rem .5rem;
            font-size: .75rem;
            border-radius: .5rem;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .08)
        }

        .status-paid {
            background: rgba(16, 185, 129, .12);
            border-color: rgba(16, 185, 129, .35);
            color: #a7f3d0
        }

        .status-unpaid {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .35);
            color: #fecaca
        }

        .status-pending {
            background: rgba(245, 158, 11, .12);
            border-color: rgba(245, 158, 11, .35);
            color: #fde68a
        }

        .nav-link {
            @apply px-3 py-2 rounded-lg text-sm font-medium;
            color: #cbd5e1;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, .06);
            color: #fff;
        }

        .nav-link.active {
            background: rgba(37, 99, 235, .18);
            color: #fff;
            border: 1px solid rgba(96, 165, 250, .35);
        }

        .logo {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: conic-gradient(from 180deg, var(--brand), var(--brand-2));
            box-shadow: 0 4px 16px rgba(37, 99, 235, .45);
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 800;
        }

        /* Rapikan field di dalam SweetAlert */
        .swal-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            text-align: left
        }

        .swal-form .field {
            display: flex;
            flex-direction: column;
            gap: 6px
        }

        .swal-form .field>label {
            font-size: .9rem;
            color: #94a3b8
        }

        /* Samakan margin default swal2 */
        .swal2-popup .swal2-input,
        .swal2-popup .swal2-textarea {
            margin: 0 !important;
        }

        /* Style input file agar sejajar dan full-width */
        .swal-form input[type="file"] {
            width: 100%;
            padding: .6rem;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
            background: rgba(255, 255, 255, .04);
            color: #e5e7eb
        }

        .swal-form input[type="file"]::-webkit-file-upload-button {
            margin-right: .5rem;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 6px;
            background: rgba(255, 255, 255, .08);
            padding: .4rem .6rem;
            color: #e5e7eb;
            cursor: pointer
        }

        /* Preview */
        .swal-form .preview {
            display: flex;
            gap: 12px;
            align-items: center
        }

        .swal-form .preview img {
            width: 180px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, .15)
        }
    </style>
    <link rel="shortcut icon" href="/assets/images/favicon.svg" type="image/svg+xml">
</head>

<body class="min-h-screen">

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-30 border-b border-white/10 bg-[#0b1225]/80 backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">

                <!-- Kiri: Logo + Title (klik = refresh admin.php) -->
                <a href="admin.php" class="flex items-center gap-3 group">
                    <div class="logo grid place-items-center text-white font-extrabold transition-transform group-hover:scale-105"
                        style="width:38px;height:38px;border-radius:12px;background:conic-gradient(from 180deg,#2563eb,#60a5fa);box-shadow:0 4px 16px rgba(37,99,235,.45)">
                        SA
                    </div>
                    <span class="text-lg font-semibold text-white hover:opacity-90">Serenity Admin</span>
                </a>

                <!-- Kanan: Nav + Avatar -->
                <div class="flex items-center gap-3">
                    <nav class="hidden sm:flex items-center gap-1">
                        <a class="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white" href="#katalog">Katalog</a>
                        <a class="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white" href="#sewa">Sewa</a>
                    </nav>

                    <!-- Avatar + Dropdown -->
                    <div class="relative" id="userMenu">
                        <button id="avatarBtn" class="flex items-center gap-2 rounded-full border border-white/15 p-1.5 hover:border-white/30">
                            <!-- Foto profil (ganti src dengan foto admin jika ada di session) -->
                            <img src="https://ui-avatars.com/api/?name=Admin&background=2563eb&color=fff"
                                alt="Admin" class="h-8 w-8 rounded-full object-cover" />
                            <span class="hidden sm:inline text-sm text-slate-200 pr-1">Admin</span>
                        </button>

                        <!-- Dropdown -->
                        <div id="avatarMenu"
                            class="absolute right-0 mt-2 w-44 rounded-xl border border-white/10 bg-[#0f172a] p-1 shadow-xl hidden">
                            <!-- <a href="profile.php" class="block rounded-lg px-3 py-2 text-sm text-slate-200 hover:bg-white/10">Profil</a> -->
                            <form action="logout.php" method="post">
                                <button type="submit"
                                    class="w-full text-left rounded-lg px-3 py-2 text-sm text-red-300 hover:bg-red-500/10 hover:text-red-200">
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- Banner -->
    <section aria-label="Banner" class="relative">
        <div class="h-48 sm:h-56 bg-[url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1600&auto=format&fit=crop')] bg-cover bg-center"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-transparent to-transparent"></div>
    </section>

    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10 py-8">

        <!-- Manajemen Katalog Adat -->
        <section id="katalog" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Manajemen Katalog Adat</h2>
                <div class="flex items-center gap-2">
                    <button id="btnTambahKatalog"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white">
                        <i class="lucide-plus-circle"></i>Tambah Data
                    </button>

                    <button id="btnHapusSemuaKatalog"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm bg-red-500 hover:bg-red-600 text-white">
                        <i class="lucide-trash-2"></i>Hapus Semua
                    </button>
                </div>
            </div>

            <div class="card rounded-2xl p-4">
                <div class="table-wrap overflow-x-auto">
                    <table class="w-full table-fixed border-collapse">
                        <colgroup>
                            <col style="width:64px"> <!-- No -->
                            <col style="width:22%"> <!-- Nama Adat -->
                            <col style="width:150px"> <!-- Gambar -->
                            <col> <!-- Deskripsi (auto sisa) -->
                            <col style="width:160px"> <!-- Harga -->
                            <col style="width:160px"> <!-- Aksi -->
                        </colgroup>
                        <thead>
                            <tr class="text-slate-300 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left align-middle">No</th>
                                <th class="px-4 py-3 text-left align-middle">Nama Adat</th>
                                <th class="px-4 py-3 text-left align-middle">Gambar</th>
                                <th class="px-4 py-3 text-left align-middle">Deskripsi</th>
                                <th class="px-4 py-3 text-left align-middle">Harga Paket</th>
                                <th class="px-4 py-3 text-left align-middle">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyKatalog">
                            <?php $no = 1;
                            foreach ($katalog as $row): ?>
                                <tr class="border-t border-white/10 hover:bg-white/5">
                                    <td class="px-4 py-3 align-middle"><?= $no++ ?></td>
                                    <td class="px-4 py-3 align-middle" data-field="nama_adat">
                                        <?= htmlspecialchars($row['nama_adat']) ?>
                                    </td>
                                    <td class="px-4 py-3 align-middle" data-field="foto_path">
                                        <img src="<?= htmlspecialchars($foto_path.$row['foto_path']) ?>" alt="Foto Adat"
                                            class="h-14 w-20 object-cover rounded-lg border border-white/10">
                                    </td>
                                    <td class="px-4 py-3 align-middle max-w-[28rem]" data-field="isi_desk">
                                        <p class="text-slate-200/90 line-clamp-3"><?= htmlspecialchars($row['isi_desk']) ?></p>
                                    </td>
                                    <td class="px-4 py-3 align-middle font-semibold" data-field="harga">
                                        Rp <?= number_format((int)$row['harga'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex flex-wrap gap-2">
                                            <button class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm border border-white/20 hover:border-white/40"
                                                onclick="onEditKatalog(<?= (int)$row['idadat'] ?>)">
                                                <i class="lucide-pencil"></i><span>Edit</span>
                                            </button>
                                            <button class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm bg-red-500 hover:bg-red-600 text-white"
                                                onclick="onHapusKatalog(<?= (int)$row['idadat'] ?>)">
                                                <i class="lucide-trash-2"></i><span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
            </div>
        </section>

        <!-- Manajemen Sewa -->
        <section id="sewa" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Manajemen Sewa</h2>
                <button id="btnHapusSemuaSewa"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm bg-red-500 hover:bg-red-600 text-white">
                    <i class="lucide-trash-2"></i>Hapus Semua Data Sewa
                </button>
            </div>

            <div class="card rounded-2xl p-4">
                <div class="table-wrap">
                    <table class="min-w-full border-separate" style="border-spacing:0 10px">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">No</th>
                                <th class="px-4 py-2">Nama Pemesan</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2">Nomor HP</th>
                                <th class="px-4 py-2">Kota</th>
                                <th class="px-4 py-2">Pilihan Adat</th>
                                <th class="px-4 py-2">Tanggal Sewa</th>
                                <th class="px-4 py-2">Jenis Bayar</th>
                                <th class="px-4 py-2">Harga Total</th>
                                <th class="px-4 py-2">Status Bayar</th>
                                <th class="px-4 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySewa">
                            <!-- baris akan di-render oleh loadSewaAwal() -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <!-- JS Aksi -->
    <script>
        /** Pilihan paket untuk dropdown edit sewa (diambil dari $katalog PHP) */
        const KATALOG_OPTS = <?php
                                echo json_encode(
                                    array_map(fn($r) => ['idadat' => (int)$r['idadat'], 'nama_adat' => $r['nama_adat']], $katalog),
                                    JSON_UNESCAPED_UNICODE
                                );
                                ?>;
    </script>


    <script>
        // ===================== Util umum =====================
        // ====== util tampil angka & escape ======
        function rupiah(n) {
            n = Number(n || 0);
            return 'Rp ' + n.toLocaleString('id-ID');
        }

        function esc(s) {
            const d = document.createElement('div');
            d.textContent = String(s ?? '');
            return d.innerHTML;
        }

        function badgeClass(status) {
            if (status === 'Lunas Full' || status === 'Lunas DP') return 'status-paid';
            if (status === 'Belum Bayar') return 'status-unpaid';
            if (status === 'Dibatalkan') return 'status-unpaid'; // atau kelas khusus
            // default termasuk "Menunggu Pembayaran"
            return 'status-pending';
        }


        // ===================== Helper tombol (gaya katalog) =====================
        function buttonGhost(label, icon, onclick) {
            return `
  <button class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm border border-white/20 hover:border-white/40"
          onclick="${onclick}">
    <i class="${icon}"></i><span>${label}</span>
  </button>`;
        }


        function buttonPrimary(label, icon, onclick) {
            return `
  <button class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white"
          onclick="${onclick}">
    <i class="${icon}"></i><span>${label}</span>
  </button>`;
        }

        function buttonWarn(label, icon, onclick) {
            return `
  <button class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm bg-amber-500 hover:bg-amber-600 text-white"
          onclick="${onclick}">
    <i class="${icon}"></i><span>${label}</span>
  </button>`;
        }

        // --- helper normalisasi status ---
        function normStatus(s) {
            return String(s || '').trim().toLowerCase();
        }

        // ===================== ACTION BUILDER (Manajemen Sewa) =====================
        function renderActionsForStatus(idsewa, statusbayarRaw) {
            const s = normStatus(statusbayarRaw);

            // Dibatalkan → hanya Edit
            if (s === 'dibatalkan') {
                return `
      <div class="flex flex-wrap gap-2">
        ${buttonGhost('Edit','lucide-pencil',`onEditSewa(${Number(idsewa)})`)}
      </div>`;
            }

            // Menunggu Bayaran / Menunggu Pembayaran → Kirim Email, Konfirmasi Pembayaran, Tolak, Edit
            if (s === 'menunggu pembayaran') {
                return `
      <div class="flex flex-wrap gap-2">
        ${buttonGhost('Kirim Email','lucide-mail',`onKirimEmail(${Number(idsewa)})`)}
        ${buttonPrimary('Konfirmasi Pembayaran','lucide-badge-check',`onKonfirmasiPembayaran(${Number(idsewa)})`)}
        ${buttonWarn('Tolak','lucide-x-circle',`onTolakPesanan(${Number(idsewa)})`)}
        ${buttonGhost('Edit','lucide-pencil',`onEditSewa(${Number(idsewa)})`)}
      </div>`;
            }

            // Lunas DP → Pelunasan DP + Edit
            if (s === 'lunas dp') {
                return `
      <div class="flex flex-wrap gap-2">
        ${buttonPrimary('Pelunasan DP','lucide-badge-check',`onKonfirmasiPembayaran(${Number(idsewa)},{pelunasan:true})`)}
        ${buttonGhost('Edit','lucide-pencil',`onEditSewa(${Number(idsewa)})`)}
      </div>`;
            }

            // Lunas Full → hanya Edit
            if (s === 'lunas full') {
                return `
      <div class="flex flex-wrap gap-2">
        ${buttonGhost('Edit','lucide-pencil',`onEditSewa(${Number(idsewa)})`)}
      </div>`;
            }

            // default (Belum Bayar / lainnya sebelum dikonfirmasi) → Konfirmasi Pesanan, Tolak, Edit
            return `
    <div class="flex flex-wrap gap-2">
      ${buttonPrimary('Konfirmasi Pesanan','lucide-check-circle-2',`onKonfirmasiPesanan(${Number(idsewa)})`)}
      ${buttonWarn('Tolak','lucide-x-circle',`onTolakPesanan(${Number(idsewa)})`)}
      ${buttonGhost('Edit','lucide-pencil',`onEditSewa(${Number(idsewa)})`)}
    </div>`;
        }
        // ===================== Render baris SEWA =====================
        function renderRowSewa(rowData, noUrut) {
            const {
                idsewa,
                nama_cust,
                email,
                notelp,
                kota,
                idadat,
                nama_adat,
                tgl_sewa,
                jenisbayar,
                harga_total,
                statusbayar
            } = rowData;

            const tr = document.createElement('tr');
            tr.setAttribute('data-idsewa', idsewa);

            tr.innerHTML = `
    <td class="px-4 py-3">${noUrut}</td>
    <td class="px-4 py-3" data-field="nama_cust">${esc(nama_cust)}</td>
    <td class="px-4 py-3" data-field="email">${esc(email)}</td>
    <td class="px-4 py-3" data-field="notelp">${esc(notelp)}</td>
    <td class="px-4 py-3" data-field="kota">${esc(kota)}</td>
    <td class="px-4 py-3" data-field="idadat" data-idadat="${esc(idadat)}">${esc(nama_adat || idadat)}</td>
    <td class="px-4 py-3" data-field="tgl_sewa">${esc(tgl_sewa)}</td>
    <td class="px-4 py-3" data-field="jenisbayar">${esc(jenisbayar)}</td>
    <td class="px-4 py-3 font-semibold" data-field="harga_total">${rupiah(harga_total)}</td>
    <td class="px-4 py-3" data-field="statusbayar">
      <span class="badge ${badgeClass(statusbayar)}">${esc(statusbayar)}</span>
    </td>
    <td class="px-4 py-3">
      ${renderActionsForStatus(idsewa, statusbayar)}
    </td>
  `;
            document.querySelector('#tbodySewa')?.appendChild(tr);
        }

        // ===================== Load awal SEWA =====================
        async function loadSewaAwal() {
            try {
                const res = await fetch('sewa_list.php'); // pastikan path
                const data = await res.json(); // {ok:true, rows:[...]}
                if (!data.ok) throw new Error(data.message || 'Gagal load');
                const tbody = document.getElementById('tbodySewa');
                if (!tbody) return;
                tbody.innerHTML = '';
                let no = 1;
                for (const r of data.rows) {
                    renderRowSewa(r, no++);
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Oops', 'Gagal memuat data sewa', 'error');
            }
        }

        // ===================== Handler Manajemen Sewa =====================
        window.onKonfirmasiPesanan = async function(idsewa) {
            const ok = await Swal.fire({
                icon: 'question',
                title: `Konfirmasi pesanan ini?`,
                text: 'Status akan menjadi "Menunggu Bayaran" dan invoice akan dikirim via email.',
                showCancelButton: true,
                confirmButtonText: 'Konfirmasi & Kirim Email'
            });
            if (!ok.isConfirmed) return;

            try {
                const body = new URLSearchParams();
                body.set('idsewa', String(idsewa));
                const r = await fetch('sewa_confirm.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body.toString()
                });
                const out = await r.json();
                if (!r.ok || !out.ok) throw new Error(out.message || 'Gagal konfirmasi & kirim email');

                // Update status badge
                const badge = document.querySelector(`tr[data-idsewa="${idsewa}"] [data-field="statusbayar"] .badge`);
                if (badge) {
                    badge.className = 'badge ' + badgeClass('Menunggu Pembayaran');
                    badge.textContent = 'Menunggu Pembayaran';
                }

                // Ganti tombol aksi
                switchToPostConfirmButtons(idsewa);

                Swal.fire('Berhasil', 'Invoice terkirim & status diubah ke "Menunggu Bayaran".', 'success');
            } catch (e) {
                Swal.fire('Gagal', String(e.message || e), 'error');
            }
        };

        // ====== helper untuk update tombol pada 1 baris ======
        function updateActionButtons(idsewa, statusbayar) {
            const tdAksi = document.querySelector(`tr[data-idsewa="${idsewa}"] td:last-child`);
            if (tdAksi) tdAksi.innerHTML = renderActionsForStatus(idsewa, statusbayar);
        }
        // helper untuk switch ke set tombol "Menunggu Bayaran"
        function switchToPostConfirmButtons(idsewa) {
            const tdAksi = document.querySelector(`tr[data-idsewa="${idsewa}"] td:last-child`);
            if (tdAksi) tdAksi.innerHTML = renderActionsForStatus(idsewa, 'Menunggu Pembayaran');
        }

        window.onKirimEmail = async function(idsewa) {
            try {
                const r = await fetch('sewa_resend_invoice.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        idsewa
                    })
                });
                const out = await r.json();
                if (!r.ok || !out.ok) throw new Error(out.message || 'Gagal mengirim ulang email');
                Swal.fire('Terkirim', 'Email invoice berhasil dikirim ulang.', 'success');
            } catch (e) {
                Swal.fire('Gagal', String(e.message || e), 'error');
            }
        };

        window.onTolakPesanan = async function(idsewa) {
            const ask = await Swal.fire({
                icon: 'warning',
                title: `Tolak pesanan?`,
                input: 'textarea',
                inputPlaceholder: 'Tulis alasan penolakan…',
                inputValidator: (v) => (!v || v.trim().length < 5) ? 'Alasan minimal 5 karakter.' : undefined,
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Tolak'
            });
            if (!ask.isConfirmed) return;

            const alasan = ask.value.trim();

            try {
                const r = await fetch('sewa_reject.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        idsewa,
                        alasan
                    })
                });
                const text = await r.text();
                let out;
                try {
                    out = JSON.parse(text)
                } catch {
                    throw new Error('Respons tidak valid: ' + text.slice(0, 200));
                }
                if (!r.ok || !out.ok) throw new Error(out.message || 'Gagal menolak pesanan');

                // update badge status → Dibatalkan
                const badge = document.querySelector(`tr[data-idsewa="${idsewa}"] [data-field="statusbayar"] .badge`);
                if (badge) {
                    badge.className = 'badge ' + badgeClass('Dibatalkan'); // akan masuk status-pending (atau bikin class khusus)
                    badge.textContent = 'Dibatalkan';
                }

                // opsional: ubah tombol aksi jadi hanya "Edit"
                const tdAksi = document.querySelector(`tr[data-idsewa="${idsewa}"] td:last-child`);
                if (tdAksi) {
                    tdAksi.innerHTML = `
        <button class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm border border-white/20 hover:border-white/40"
                onclick="onEditSewa(${idsewa})">
          <i class="lucide-pencil"></i><span>Edit</span>
        </button>`;
                }

                Swal.fire('Ditolak', 'Pesanan telah ditolak dan email terkirim ke pelanggan.', 'success');
            } catch (e) {
                Swal.fire('Gagal', String(e.message || e), 'error');
            }
        };

        window.onKonfirmasiPembayaran = async function(idsewa, opts = {}) {
            const isPelunasan = !!opts.pelunasan;

            const title = isPelunasan ? `Konfirmasi pelunasan DP #${idsewa}?` : `Konfirmasi pembayaran #${idsewa}?`;
            const text = isPelunasan ?
                'Status akan diset menjadi "Lunas Full" dan email pelunasan akan dikirim.' :
                'Status akan disesuaikan: "Lunas DP" untuk DP, atau "Lunas Full" untuk Full.';

            const ok = await Swal.fire({
                icon: 'info',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: isPelunasan ? 'Konfirmasi Pelunasan' : 'Konfirmasi Pembayaran'
            });
            if (!ok.isConfirmed) return;

            try {
                const params = new URLSearchParams();
                params.set('idsewa', String(idsewa));
                if (isPelunasan) params.set('pelunasan', '1');
                const r = await fetch('sewa_pay_confirm.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params.toString()
                });
                const out = await r.json();
                if (!r.ok || !out.ok) throw new Error(out.message || 'Gagal konfirmasi pembayaran');

                const newStatus = out.new_status || (isPelunasan ? 'Lunas Full' : 'Lunas DP');
                // update badge
                const badge = document.querySelector(`tr[data-idsewa="${idsewa}"] [data-field="statusbayar"] .badge`);
                if (badge) {
                    badge.className = 'badge ' + badgeClass(newStatus);
                    badge.textContent = newStatus;
                }

                // update tombol sesuai status baru
                updateActionButtons(idsewa, newStatus);

                Swal.fire('Sukses', `Status: ${newStatus}`, 'success');
            } catch (e) {
                Swal.fire('Gagal', String(e.message || e), 'error');
            }
        };

        window.onPelunasanDP = async function(idsewa) {
            const ok = await Swal.fire({
                icon: 'info',
                title: `Pelunasan DP untuk #${idsewa}?`,
                text: 'Status akan diubah menjadi "Lunas Full".',
                showCancelButton: true,
                confirmButtonText: 'Konfirmasi Pelunasan'
            });
            if (!ok.isConfirmed) return;

            try {
                const p = new URLSearchParams();
                p.set('idsewa', String(idsewa));
                p.set('pelunasan', '1');
                const r = await fetch('sewa_pay_confirm.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    // server-side: tangani pelunasan=1 untuk upgrade Lunas DP -> Lunas Full
                    body: new URLSearchParams({
                        idsewa,
                        pelunasan: '1'
                    })
                });
                const out = await r.json();
                if (!r.ok || !out.ok) throw new Error(out.message || 'Gagal konfirmasi pelunasan');

                const newStatus = out.new_status || 'Lunas Full';
                const badge = document.querySelector(`tr[data-idsewa="${idsewa}"] [data-field="statusbayar"] .badge`);
                if (badge) {
                    badge.className = 'badge ' + badgeClass(newStatus);
                    badge.textContent = newStatus;
                }

                // Lunas Full → tombol hanya Edit
                const tdAksi = document.querySelector(`tr[data-idsewa="${idsewa}"] td:last-child`);
                if (tdAksi) tdAksi.innerHTML = renderActionsForStatus(idsewa, newStatus);

                Swal.fire('Sukses', 'Pelunasan berhasil. Status menjadi "Lunas Full".', 'success');
            } catch (e) {
                Swal.fire('Gagal', String(e.message || e), 'error');
            }
        };


        /* util kecil */
        function toISODate(d) {
            // terima "YYYY-MM-DD" (balikkan apa adanya) atau "DD/MM/YYYY" → "YYYY-MM-DD"
            if (/^\d{4}-\d{2}-\d{2}$/.test(d)) return d;
            const m = d.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            return m ? `${m[3]}-${m[2]}-${m[1]}` : '';
        }

        function optionHtml(list, selectedId) {
            return list.map(o => `<option value="${o.idadat}" ${String(o.idadat)===String(selectedId)?'selected':''}>${esc(o.nama_adat)}</option>`).join('');
        }

        window.onEditSewa = async function(idsewa) {
            const tr = document.querySelector(`tr[data-idsewa="${idsewa}"]`);
            if (!tr) {
                Swal.fire('Oops', 'Baris tidak ditemukan.', 'error');
                return;
            }

            const get = sel => tr.querySelector(sel)?.textContent?.trim() || '';
            const getAttr = (sel, attr) => tr.querySelector(sel)?.getAttribute(attr) || '';

            const nama_cust = get('[data-field="nama_cust"]');
            const email = get('[data-field="email"]');
            const notelp = get('[data-field="notelp"]');
            const kota = get('[data-field="kota"]');
            const idadat = getAttr('[data-field="idadat"]', 'data-idadat') || get('[data-field="idadat"]');
            const tgl_sewa = toISODate(get('[data-field="tgl_sewa"]'));
            const jenisbayar = get('[data-field="jenisbayar"]');
            const harga_total = (get('[data-field="harga_total"]') || '').replace(/[^\d]/g, '');
            const statusbayar = get('[data-field="statusbayar"] .badge');

            const html = `

    <style>
      .swal-form{display:grid;gap:14px}
      @media(min-width:768px){.swal-form{grid-template-columns:1fr 1fr}}
      .field{display:flex;flex-direction:column;gap:6px}
      .field label{font-size:.9rem;color:#64748b}
      .inpt,.sel{
        width:100%;padding:.75rem;border:1px solid #94a3b8; border-radius:10px;
        background:rgba(255,255,255,.04); color:#e5e7eb; outline:none
      }
      .inpt:focus,.sel:focus{border-color:#60a5fa; box-shadow:0 0 0 3px rgba(96,165,250,.25)}
      /* full width untuk baris panjang */
      .col-span-2{grid-column:1 / -1}
    </style>

    <div class="swal-form">
      <div class="field col-span-2">
        <label>Nama Pemesan</label>
        <input id="ed_nama_cust" class="inpt" placeholder="Nama" value="${nama_cust.replace(/"/g,'&quot;')}">
      </div>

      <div class="field">
        <label>Email</label>
        <input id="ed_email" type="email" class="inpt" placeholder="email@domain.com" value="${email.replace(/"/g,'&quot;')}">
      </div>
      <div class="field">
        <label>No. HP</label>
        <input id="ed_notelp" class="inpt" placeholder="08xxxx" value="${notelp.replace(/"/g,'&quot;')}">
      </div>

      <div class="field">
        <label>Kota</label>
        <input id="ed_kota" class="inpt" placeholder="Kota" value="${kota.replace(/"/g,'&quot;')}">
      </div>
      <div class="field">
        <label>Paket Adat</label>
        <select id="ed_idadat" class="sel">${optionHtml(KATALOG_OPTS, idadat)}</select>
      </div>

      <div class="field">
        <label>Tanggal Sewa</label>
        <input id="ed_tgl_sewa" type="date" class="inpt" value="${tgl_sewa}">
      </div>
      <div class="field">
        <label>Jenis Bayar</label>
        <select id="ed_jenisbayar" class="sel">
          <option value="Full" ${jenisbayar==='Full'?'selected':''}>Full</option>
          <option value="DP"   ${jenisbayar==='DP'  ?'selected':''}>DP</option>
        </select>
      </div>

      <div class="field">
        <label>Harga Total (Rp)</label>
        <input id="ed_harga_total" type="number" min="0" step="1" class="inpt" value="${harga_total}">
      </div>
      <div class="field">
        <label>Status Bayar</label>
        <select id="ed_statusbayar" class="sel">
          <option value="Belum Bayar" ${statusbayar==='Belum Bayar'?'selected':''}>Belum Bayar</option>
          <option value="Menunggu Pembayaran" ${statusbayar==='Menunggu Pembayaran'?'selected':''}>Menunggu Pembayaran</option>
          <option value="Lunas DP" ${statusbayar==='Lunas DP'?'selected':''}>Lunas DP</option>
          <option value="Lunas Full" ${statusbayar==='Lunas Full'?'selected':''}>Lunas Full</option>
          <option value="Dibatalkan" ${statusbayar==='Dibatalkan'?'selected':''}>Dibatalkan</option>
        </select>
      </div>
    </div>
  `;

            const res = await Swal.fire({
                title: `Edit Sewa #${idsewa}`,
                html,
                width: 650,
                focusConfirm: false,
                confirmButtonText: 'Simpan',
                showCancelButton: true,
                preConfirm: () => {
                    const v = (id) => document.getElementById(id)?.value?.trim() || '';
                    const payload = {
                        idsewa,
                        nama_cust: v('ed_nama_cust'),
                        email: v('ed_email'),
                        notelp: v('ed_notelp'),
                        kota: v('ed_kota'),
                        idadat: document.getElementById('ed_idadat')?.value || '',
                        tgl_sewa: v('ed_tgl_sewa'),
                        jenisbayar: document.getElementById('ed_jenisbayar')?.value || '',
                        harga_total: v('ed_harga_total'),
                        statusbayar: document.getElementById('ed_statusbayar')?.value || ''
                    };
                    // validasi cepat
                    if (!payload.nama_cust || !payload.email || !payload.notelp || !payload.kota || !payload.idadat || !payload.tgl_sewa) {
                        Swal.showValidationMessage('Nama, Email, HP, Kota, Paket, Tanggal wajib diisi.');
                        return false;
                    }
                    if (!/^\d{4}-\d{2}-\d{2}$/.test(payload.tgl_sewa)) {
                        Swal.showValidationMessage('Format tanggal harus YYYY-MM-DD.');
                        return false;
                    }
                    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(payload.email)) {
                        Swal.showValidationMessage('Format email tidak valid.');
                        return false;
                    }
                    if (payload.harga_total !== '' && (+payload.harga_total < 0 || !/^\d+$/.test(payload.harga_total))) {
                        Swal.showValidationMessage('Harga total harus angka ≥ 0.');
                        return false;
                    }
                    return payload;
                }
            });

            if (!res.isConfirmed) return;

            try {
                const r = await fetch('sewa_update.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(res.value)
                });

                const text = await r.text();
                let out;
                try {
                    out = JSON.parse(text);
                } catch {
                    throw new Error('Respons tidak valid: ' + text.slice(0, 200));
                }

                if (!r.ok || !out.ok) throw new Error(out.message || 'Gagal menyimpan perubahan');

                // Tampilkan notif lalu REFRESH halaman
                await Swal.fire('Tersimpan', 'Perubahan pesanan berhasil disimpan.', 'success');
                location.reload(); // <— auto refresh

            } catch (e) {
                Swal.fire('Gagal', String(e.message || e), 'error');
            }

        };


        // ===================== Handler tombol "Hapus Semua Sewa" =====================
        document.getElementById('btnHapusSemuaSewa')?.addEventListener('click', async () => {
            const ask = await Swal.fire({
                icon: 'warning',
                title: 'Hapus semua pesanan?',
                text: 'Hanya jika semua status adalah Lunas Full / Dibatalkan.',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Hapus Semua'
            });
            if (!ask.isConfirmed) return;

            const btn = document.getElementById('btnHapusSemuaSewa');
            btn.disabled = true;

            try {
                const r = await fetch('delete_all_sewa.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const text = await r.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    throw new Error('Respons tidak valid: ' + text.slice(0, 200));
                }

                if (r.status === 409) throw new Error(data.message || 'Masih ada pesanan yang belum Lunas.');
                if (!r.ok || !data.success) throw new Error(data.message || 'Gagal menghapus data');

                await Swal.fire('Terhapus', `Total dihapus: ${data.deleted_count}`, 'success');

                // Tanpa reload: kosongkan tabel
                const tbody = document.getElementById('tbodySewa');
                if (tbody) tbody.innerHTML = '';
                // Atau kalau mau re-fetch dari server:
                // await loadSewaAwal();

            } catch (e) {
                Swal.fire('Gagal', String(e.message || e), 'error');
            } finally {
                btn.disabled = false;
            }
        });

        // ===================== Manajemen Katalog =====================
        document.getElementById('btnTambahKatalog')?.addEventListener('click', () => {
            Swal.fire({
                title: 'Tambah Data Katalog',
                html: `
      <div class="space-y-3 text-left">
        <input id="nama_adat" class="swal2-input" placeholder="Nama Adat">
        <input id="foto_file" type="file" accept="image/*" class="swal2-file" />
        <img id="preview_img" style="display:none;max-height:120px;border-radius:10px;border:1px solid rgba(255,255,255,.15)" />
        <textarea id="isi_desk" class="swal2-textarea" placeholder="Deskripsi (isi_desk)"></textarea>
        <input id="harga" type="number" class="swal2-input" placeholder="Harga (Rp)">
      </div>
    `,
                didOpen: () => {
                    const fileInput = document.getElementById('foto_file');
                    const preview = document.getElementById('preview_img');
                    fileInput.addEventListener('change', () => {
                        const f = fileInput.files?.[0];
                        if (!f) {
                            preview.style.display = 'none';
                            preview.src = '';
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = e => {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(f);
                    });
                },
                confirmButtonText: 'Simpan',
                showCancelButton: true,
                preConfirm: () => {
                    const nama_adat = document.getElementById('nama_adat').value.trim();
                    const isi_desk = document.getElementById('isi_desk').value.trim();
                    const harga = document.getElementById('harga').value.trim();
                    const file = document.getElementById('foto_file').files?.[0];

                    if (!nama_adat) {
                        Swal.showValidationMessage('Nama adat wajib diisi');
                        return false;
                    }
                    if (!file) {
                        Swal.showValidationMessage('Pilih gambar untuk diunggah');
                        return false;
                    }
                    if (!harga || Number(harga) < 0) {
                        Swal.showValidationMessage('Harga tidak valid');
                        return false;
                    }

                    return {
                        nama_adat,
                        isi_desk,
                        harga
                    };
                }
            }).then(async (res) => {
                if (!res.isConfirmed) return;
                try {
                    const fd = new FormData();
                    fd.append('nama_adat', res.value.nama_adat);
                    fd.append('isi_desk', res.value.isi_desk);
                    fd.append('harga', res.value.harga);
                    fd.append('foto_file', document.getElementById('foto_file').files[0]);

                    const r = await fetch('crupdate.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await r.json();

                    if (!r.ok || !data.success) throw new Error(data.message || 'Gagal menyimpan');

                    Swal.fire('Tersimpan', 'Data katalog berhasil ditambahkan.', 'success')
                        .then(() => location.reload());
                } catch (err) {
                    Swal.fire('Gagal', err.message || 'Terjadi kesalahan server', 'error');
                }
            });
        });

        document.getElementById('btnHapusSemuaKatalog')?.addEventListener('click', async () => {
            const res = await Swal.fire({
                icon: 'warning',
                title: 'Hapus semua data?',
                text: 'Tindakan ini tidak dapat dibatalkan.',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Hapus Semua'
            });
            if (!res.isConfirmed) return;

            try {
                const r = await fetch('delete_all.php', {
                    method: 'POST'
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) throw new Error(data.message || 'Gagal menghapus semua data');

                await Swal.fire('Terhapus', 'Seluruh data katalog dihapus.', 'success');
                location.reload();
            } catch (err) {
                Swal.fire('Gagal', err.message || 'Terjadi kesalahan server', 'error');
            }
        });

        window.onEditKatalog = function(idadat) {
            // ambil nilai dari baris tabel
            const row = document.querySelector(`tr[data-idadat="${idadat}"]`);
            const namaAwal = row?.querySelector('[data-field="nama_adat"]')?.textContent?.trim() || '';
            const deskAwal = row?.querySelector('[data-field="isi_desk"]')?.textContent?.trim() || '';
            const hargaAwal = row?.querySelector('[data-field="harga"]')?.textContent?.replace(/[^\d]/g, '') || '';
            const imgSrc = row?.querySelector('[data-field="foto_path"] img')?.getAttribute('src') || '';

            Swal.fire({
                title: `Edit Katalog ${idadat}`,
                html: `
      <div class="space-y-3 text-left">
        <input id="ed_nama_adat" class="swal2-input" placeholder="Nama Adat" value="${namaAwal.replace(/"/g,'&quot;')}">
        <input id="ed_foto_file" type="file" accept="image/*" class="swal2-file" />
        <img id="ed_preview_img" src="${imgSrc}" style="display:${imgSrc?'block':'none'};max-height:120px;border-radius:10px;border:1px solid rgba(255,255,255,.15)" />
        <textarea id="ed_isi_desk" class="swal2-textarea" placeholder="Deskripsi (isi_desk)">${deskAwal.replace(/</g,'&lt;')}</textarea>
        <input id="ed_harga" type="number" class="swal2-input" placeholder="Harga (Rp)" value="${hargaAwal}">
      </div>
    `,
                didOpen: () => {
                    const fileInput = document.getElementById('ed_foto_file');
                    const preview = document.getElementById('ed_preview_img');
                    fileInput.addEventListener('change', () => {
                        const f = fileInput.files?.[0];
                        if (!f) {
                            preview.style.display = 'none';
                            preview.src = '';
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = e => {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(f);
                    });
                },
                confirmButtonText: 'Simpan',
                showCancelButton: true,
                preConfirm: () => {
                    const nama = document.getElementById('ed_nama_adat').value.trim();
                    const desk = document.getElementById('ed_isi_desk').value.trim();
                    const harga = document.getElementById('ed_harga').value.trim();
                    if (harga && (+harga < 0 || !/^\d+$/.test(harga))) {
                        Swal.showValidationMessage('Harga tidak valid');
                        return false;
                    }
                    return {
                        nama,
                        desk,
                        harga,
                        hasFile: !!document.getElementById('ed_foto_file').files?.[0]
                    };
                }
            }).then(async (res) => {
                if (!res.isConfirmed) return;
                try {
                    const fd = new FormData();
                    fd.append('idadat', idadat);
                    if (res.value.nama) fd.append('nama_adat', res.value.nama);
                    if (res.value.desk) fd.append('isi_desk', res.value.desk);
                    if (res.value.harga) fd.append('harga', res.value.harga);
                    const f = document.getElementById('ed_foto_file').files?.[0];
                    if (f) fd.append('foto_file', f);

                    const r = await fetch('crupdate.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await r.json();
                    if (!r.ok || !data.success) throw new Error(data.message || 'Gagal menyimpan');

                    Swal.fire('Tersimpan', 'Perubahan katalog berhasil.', 'success').then(() => location.reload());
                } catch (e) {
                    Swal.fire('Gagal', e.message || 'Terjadi kesalahan server', 'error');
                }
            });
        };

        window.onHapusKatalog = function(idadat) {
            Swal.fire({
                icon: 'warning',
                title: `Hapus item #${idadat}?`,
                text: 'Item akan dihapus dari katalog.',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Hapus'
            }).then((res) => {
                if (!res.isConfirmed) return;
                // TODO: panggil endpoint hapus katalog
                const row = document.querySelector(`tr[data-idadat="${idadat}"]`);
                row?.remove();
                Swal.fire('Terhapus', 'Item katalog berhasil dihapus.', 'success');
            });
        };

        // ===================== Init =====================
        document.addEventListener('DOMContentLoaded', () => {
            // Isi tabel sewa saat admin page dibuka
            loadSewaAwal();

            // Optional: navbar active on scroll
            const links = document.querySelectorAll('header nav a');
            const sections = [...document.querySelectorAll('main section')];
            const onScroll = () => {
                const y = window.scrollY + 90;
                let activeId = sections[0]?.id || '';
                sections.forEach(sec => {
                    if (y >= sec.offsetTop) activeId = sec.id;
                });
                links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + activeId));
            };
            document.addEventListener('scroll', onScroll, {
                passive: true
            });
        });
    </script>

    <script>
        // Toggle dropdown avatar + klik di luar menutup
        (function() {
            const btn = document.getElementById('avatarBtn');
            const menu = document.getElementById('avatarMenu');
            const wrap = document.getElementById('userMenu');
            if (!btn || !menu || !wrap) return;

            const close = () => menu.classList.add('hidden');
            const open = () => menu.classList.remove('hidden');

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!wrap.contains(e.target)) close();
            });

            // Tutup saat Esc
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') close();
            });
        })();
    </script>


</body>

</html>