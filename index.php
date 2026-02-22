<?php
session_start();
//var_dump($_SESSION); // debuging, check session

$isLoggedIn = !empty($_SESSION['user_id']);
/*if (!$isLoggedIn) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Silakan login terlebih dahulu'];
}*/



require_once __DIR__ . '/config/config.php'; // beracu pada percakapanmu sebelumnya
// Ambil data katalog
$sql = "SELECT idadat, nama_adat, foto_path, isi_desk, harga FROM tbkatalog ORDER BY idadat DESC";
$res = $conn->query($sql);

// Helper aman & format rupiah
function esc($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($n)
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

// --- Ambil semua paket untuk pilihan select ---
$katalogList = [];
$opt = $conn->query("SELECT idadat, nama_adat, harga FROM tbkatalog ORDER BY nama_adat ASC");
if ($opt) {
    while ($r = $opt->fetch_assoc()) {
        $katalogList[] = $r;
    }
}

// --- Jika datang dari katalog ?idadat=XX, set pilihan awal & harga ---
$idadatDipilih = isset($_GET['idadat']) ? (int)$_GET['idadat'] : 0;
$selectedRow = null;
foreach ($katalogList as $r) {
    if ((int)$r['idadat'] === $idadatDipilih) {
        $selectedRow = $r;
        break;
    }
}
$namaAdat = $selectedRow ? $selectedRow['nama_adat'] : '';
$hargaAsli = $selectedRow ? (float)$selectedRow['harga'] : 0;


?>

<!DOCTYPE html>
<html lang="en">


<head>
    <!--====== Required meta tags ======-->
    <meta charset="utf-8"/>
    <meta http-equiv="x-ua-compatible" content="ie=edge"/>
    <meta name="description" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


    <!-- Swiper -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@11/swiper-bundle.min.css">

    <!--====== Title ======-->
    <title>Serenity - Jasa Sewa make up</title>

    <!--====== Favicon Icon ======-->
    <link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/svg"/>

    <!--====== Bootstrap css ======-->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>

    <!--====== Line Icons css ======-->
    <link rel="stylesheet" href="assets/css/lineicons.css"/>

    <!--====== Tiny Slider css ======-->
    <link rel="stylesheet" href="assets/css/tiny-slider.css"/>

    <!--====== gLightBox css ======-->
    <link rel="stylesheet" href="assets/css/glightbox.min.css"/>

    <!-- style user-->
    <link rel="stylesheet" href="style.css"/>
    <link rel="stylesheet" href="assets/css/user/style.css">

</head>

<body data-bs-spy="scroll" data-bs-target="#navbarNine" data-bs-offset="120" tabindex="0">


<?php
//NAVBAR NINE PART START
include 'user/navbar.php';
include 'user/home.php';
include 'user/about.php';
include 'user/catalog.php';
include 'user/rules.php';
if ($isLoggedIn){
    include 'user/pricing.php';
}else{
    include 'user/login.php';
}

include 'user/footer.php';
?>

<script src="https://cdn.botpress.cloud/webchat/v3.3/inject.js"></script>
<script src="https://files.bpcontent.cloud/2025/10/25/06/20251025063605-3G0PLVYY.js" defer></script>


<a href="#" class="scroll-top btn-hover">
    <i class="lni lni-chevron-up"></i>
</a>

<!--====== js ======-->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/glightbox.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/tiny-slider.js"></script>

<script>
    //===== close navbar-collapse when a  clicked
    let navbarTogglerNine = document.querySelector(
        ".navbar-nine .navbar-toggler"
    );
    navbarTogglerNine.addEventListener("click", function () {
        navbarTogglerNine.classList.toggle("active");
    });

    // ==== left sidebar toggle
    let sidebarLeft = document.querySelector(".sidebar-left");
    let overlayLeft = document.querySelector(".overlay-left");
    let sidebarClose = document.querySelector(".sidebar-close .close");

    overlayLeft.addEventListener("click", function () {
        sidebarLeft.classList.toggle("open");
        overlayLeft.classList.toggle("open");
    });
    sidebarClose.addEventListener("click", function () {
        sidebarLeft.classList.remove("open");
        overlayLeft.classList.remove("open");
    });

    // ===== navbar nine sideMenu
    let sideMenuLeftNine = document.querySelector(".navbar-nine .menu-bar");

    sideMenuLeftNine.addEventListener("click", function () {
        sidebarLeft.classList.add("open");
        overlayLeft.classList.add("open");
    });

    //========= glightbox
    GLightbox({
        'href': 'https://www.youtube.com/watch?v=CGoxTMkNbmQ',
        'type': 'video',
        'source': 'youtube', //vimeo, youtube or local
        'width': 900,
        'autoplayVideos': true,
    });
</script>

<script src="https://unpkg.com/swiper@11/swiper-bundle.min.js"></script>
<script>
    new Swiper('.about-swiper', {
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false
        },
        navigation: {
            nextEl: '.about-image-five .swiper-button-next',
            prevEl: '.about-image-five .swiper-button-prev',
        },
        pagination: {
            el: '.about-image-five .swiper-pagination',
            clickable: true
        },
        keyboard: true,
        effect: 'slide', // bisa diganti 'fade','cube','coverflow'
        speed: 600
    });
</script>

<script>
    // Peta idadat -> harga (dibangun dari PHP)
    const hargaMap = <?php
        // hasil: {"3":1200000,"5":2000000,...}
        echo json_encode(array_column($katalogList, 'harga', 'idadat'), JSON_UNESCAPED_UNICODE);
        ?>;

    const selectPaket = document.getElementById('idadat');
    const hargaView = document.getElementById('harga_total_view');
    const hargaHidden = document.getElementById('harga_total');

    function formatRupiah(num) {
        if (!num) return '';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(num);
    }

    // Set harga awal jika sudah terpilih dari query ?idadat=XX
    (function initHargaAwal() {
        const idAwal = selectPaket.value;
        if (idAwal && hargaMap[idAwal]) {
            const h = Number(hargaMap[idAwal]);
            hargaHidden.value = h;
            hargaView.value = formatRupiah(h);
        }
    })();

    selectPaket.addEventListener('change', function () {
        const id = this.value;
        const h = Number(hargaMap[id] || 0);
        hargaHidden.value = h || '';
        hargaView.value = h ? formatRupiah(h) : '';
    });
</script>

<script>
    // Inisialisasi ulang (opsional kalau pakai data-attributes sudah cukup)
    new bootstrap.ScrollSpy(document.body, {
        target: '#navbarNine',
        offset: 120 // sesuaikan tinggi navbar/sticky header
    });
</script>

<script>
    document.getElementById('formSewa')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.currentTarget;

        const btn = form.querySelector('#btnKirim') || form.querySelector('[type=submit]');
        const old = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Mengirim...';

        try {
            const res = await fetch('sewa_process.php?ajax=1', {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json'
                }
            });

            // Jangan .json() langsung; aman-kan dulu:
            const text = await res.text();
            let out;
            try {
                out = JSON.parse(text);
            } catch {
                throw new Error('Server mengirim respons non-JSON: ' + text.slice(0, 200));
            }

            if (!res.ok || !out.ok) throw new Error(out.message || 'Gagal menyimpan pesanan.');

            await Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Pesanan tersimpan!',
                timer: 1600,
                showConfirmButton: false
            });
            form.reset();

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal menyimpan',
                text: String(err.message || err)
            });
        } finally {
            btn.disabled = false;
            btn.textContent = old;
        }
    });
</script>

</body>

</html>