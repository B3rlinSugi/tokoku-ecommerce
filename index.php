<?php

require_once __DIR__ . '/config/config.php'; // beracu pada percakapanmu sebelumnya
// Ambil data katalog
$sql  = "SELECT idadat, nama_adat, foto_path, isi_desk, harga FROM tbkatalog ORDER BY idadat DESC";
$res  = $conn->query($sql);

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
$selectedRow   = null;
foreach ($katalogList as $r) {
  if ((int)$r['idadat'] === $idadatDipilih) {
    $selectedRow = $r;
    break;
  }
}
$namaAdat  = $selectedRow ? $selectedRow['nama_adat'] : '';
$hargaAsli = $selectedRow ? (float)$selectedRow['harga'] : 0;
?>

<!DOCTYPE html>
<html lang="en">

<style>
  /* Agar gambar proporsional di dalam ratio */
  .object-fit-cover {
    object-fit: cover;
    width: 100%;
    height: 100%;
  }

  /* ===== Rules (Aturan Sewa) Cards ===== */
  .rules-area .rule-card {
    position: relative;
    background: #fff;
    border-radius: 18px;
    padding: 22px 22px 20px;
    border: 1px solid rgba(0, 0, 0, .06);
    box-shadow: 0 6px 20px rgba(0, 0, 0, .05);
    transition: transform .35s ease, box-shadow .35s ease, border-color .35s ease;
    overflow: hidden;
  }

  /* subtle highlight sweep */
  .rules-area .rule-card::after {
    content: "";
    position: absolute;
    inset: -40%;
    background: radial-gradient(80% 60% at 20% 0%,
        rgba(99, 102, 241, .08), transparent 60%);
    transform: translateX(-20%);
    transition: transform .6s ease;
    pointer-events: none;
  }

  .rules-area .rule-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 14px 40px rgba(0, 0, 0, .10);
    border-color: rgba(99, 102, 241, .25);
    /* ungu lembut */
  }

  .rules-area .rule-card:hover::after {
    transform: translateX(10%);
  }

  .rules-area .rule-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(99, 102, 241, .15), rgba(59, 130, 246, .15));
    color: #4f46e5;
    /* fallback icon color */
    margin-bottom: 14px;
    font-size: 22px;
  }

  .rules-area .rule-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 8px;
  }


  body .scroll-top {
    left: 16px !important;
    right: auto !important;
  }

  .rules-area p {
    color: #444;
  }
</style>

<head>
  <!--====== Required meta tags ======-->
  <meta charset="utf-8" />
  <meta http-equiv="x-ua-compatible" content="ie=edge" />
  <meta name="description" content="" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


  <!-- Swiper -->
  <link rel="stylesheet" href="https://unpkg.com/swiper@11/swiper-bundle.min.css">

  <!--====== Title ======-->
  <title>Serenity - Jasa Sewa make up</title>

  <!--====== Favicon Icon ======-->
  <link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/svg" />

  <!--====== Bootstrap css ======-->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" />

  <!--====== Line Icons css ======-->
  <link rel="stylesheet" href="assets/css/lineicons.css" />

  <!--====== Tiny Slider css ======-->
  <link rel="stylesheet" href="assets/css/tiny-slider.css" />

  <!--====== gLightBox css ======-->
  <link rel="stylesheet" href="assets/css/glightbox.min.css" />

  <link rel="stylesheet" href="style.css" />
</head>

<body data-bs-spy="scroll" data-bs-target="#navbarNine" data-bs-offset="120" tabindex="0">

  <!--====== NAVBAR NINE PART START ======-->

  <section class="navbar-area navbar-nine">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <nav class="navbar navbar-expand-lg">
            <a class="navbar-brand" href="index.php">
              <img src="assets/images/sere-logo.png" width="200px" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNine"
              aria-controls="navbarNine" aria-expanded="false" aria-label="Toggle navigation">
              <span class="toggler-icon"></span>
              <span class="toggler-icon"></span>
              <span class="toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse sub-menu-bar" id="navbarNine">
              <ul class="navbar-nav me-auto">
                <li class="nav-item">
                  <a class="page-scroll" href="#hero-area">Home</a>
                </li>
                <li class="nav-item">
                  <a class="page-scroll" href="#katalog">Katalog</a>
                </li>

                <li class="nav-item">
                  <a class="page-scroll" href="#pricing">Pesan</a>
                </li>
                <li class="nav-item">
                  <a class="page-scroll" href="#contact">Kontak</a>
                </li>
              </ul>
            </div>
          </nav>
          <!-- navbar -->
        </div>
      </div>
      <!-- row -->
    </div>
    <!-- container -->
  </section>

  <!--====== NAVBAR NINE PART ENDS ======-->

  <!-- Start header Area -->
  <section id="hero-area" class="header-area header-eight">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 col-md-12 col-12">
          <div class="header-content">
            <h1>Wujudkan pernikahanmu bersama Serenity.</h1>
            <p>
              Serenity hadir untuk menemani momen terindah Anda dengan sentuhan riasan yang lembut, anggun, dan memancarkan ketenangan.
              Kami percaya setiap pasangan memiliki pesonanya masing-masing. Karena itu, Serenity menghadirkan make up pengantin pria dan wanita yang serasi, harmonis, dan tetap menonjolkan karakter alami keduanya.
            </p>
            <div class="button">
              <a href="#pricing" class="btn primary-btn">Mulai Daftar</a>
              <a href="https://www.youtube.com/watch?v=CGoxTMkNbmQ"
                class="glightbox video-button">
                <span class="btn icon-btn rounded-full">
                  <i class="lni lni-play"></i>
                </span>
                <span class="text">Lihat Intro</span>
              </a>
            </div>
          </div>
        </div>
        <div class="col-lg-6 col-md-12 col-12">
          <div class="header-image">
            <img src="assets/images/banner-header.jpg" alt="#" />
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- End header Area -->

  <!--====== ABOUT FIVE PART START ======-->

  <section class="about-area about-five">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 col-12">
          <div class="about-image-five">
            <svg class="shape" width="106" height="134" viewBox="0 0 106 134" fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <circle cx="1.66654" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="1.66654" cy="132" r="1.66667" fill="#DADADA" />
              <circle cx="16.3333" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="16.3333" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="16.3333" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="16.3333" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="16.333" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="16.333" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="16.333" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="16.333" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="16.333" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="16.333" cy="132" r="1.66667" fill="#DADADA" />
              <circle cx="30.9998" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="74.6665" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="30.9998" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="74.6665" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="30.9998" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="74.6665" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="30.9998" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="74.6665" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="31" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="74.6668" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="31" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="74.6668" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="31" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="74.6668" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="31" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="74.6668" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="31" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="74.6668" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="31" cy="132" r="1.66667" fill="#DADADA" />
              <circle cx="74.6668" cy="132" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="45.6665" cy="132" r="1.66667" fill="#DADADA" />
              <circle cx="89.3333" cy="132" r="1.66667" fill="#DADADA" />
              <circle cx="60.3333" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="1.66679" r="1.66667" fill="#DADADA" />
              <circle cx="60.3333" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="16.3335" r="1.66667" fill="#DADADA" />
              <circle cx="60.3333" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="31.0001" r="1.66667" fill="#DADADA" />
              <circle cx="60.3333" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="45.6668" r="1.66667" fill="#DADADA" />
              <circle cx="60.333" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="60.3335" r="1.66667" fill="#DADADA" />
              <circle cx="60.333" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="88.6668" r="1.66667" fill="#DADADA" />
              <circle cx="60.333" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="117.667" r="1.66667" fill="#DADADA" />
              <circle cx="60.333" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="74.6668" r="1.66667" fill="#DADADA" />
              <circle cx="60.333" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="103" r="1.66667" fill="#DADADA" />
              <circle cx="60.333" cy="132" r="1.66667" fill="#DADADA" />
              <circle cx="104" cy="132" r="1.66667" fill="#DADADA" />
            </svg>
            <!-- SLIDER -->
            <div class="swiper about-swiper">
              <div class="swiper-wrapper">
                <!-- Tambahkan/duplikasi slide sesuai jumlah foto -->
                <div class="swiper-slide">
                  <img src="assets/images/about-foto.jpg" alt="about" />
                </div>
                <div class="swiper-slide">
                  <img src="assets/images/minang-foto.jpg" alt="about">
                </div>
                <div class="swiper-slide">
                  <img src="assets/images/batak-foto.jpg" alt="about">
                </div>
                <div class="swiper-slide">
                  <img src="assets/images/batak-foto2.jpg" alt="about">
                </div>
              </div>

              <!-- Navigasi & pagination -->
              <div class="swiper-button-prev"></div>
              <div class="swiper-button-next"></div>
              <div class="swiper-pagination"></div>
            </div>

          </div>
        </div>
        <div class="col-lg-6 col-12">
          <div class="about-five-content">
            <h6 class="small-title text-lg">Serenity, Beauty in Harmony </h6>
            <h2 class="main-title fw-bold">Tentang Kami:</h2>
            <div class="about-five-tab">
              <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                  <button class="nav-link active" id="nav-who-tab" data-bs-toggle="tab" data-bs-target="#nav-who"
                    type="button" role="tab" aria-controls="nav-who" aria-selected="true">Apa itu Serenity?</button>
                  <button class="nav-link" id="nav-vision-tab" data-bs-toggle="tab" data-bs-target="#nav-vision"
                    type="button" role="tab" aria-controls="nav-vision" aria-selected="false">Visi</button>
                  <button class="nav-link" id="nav-history-tab" data-bs-toggle="tab" data-bs-target="#nav-history"
                    type="button" role="tab" aria-controls="nav-history" aria-selected="false">Misi</button>
                </div>
              </nav>
              <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-who" role="tabpanel" aria-labelledby="nav-who-tab">
                  <p>Serenity adalah jasa rias pengantin yang berfokus pada penyediaan layanan make up untuk pasangan pengantin pria dan wanita. Usaha ini berdiri dengan tujuan menghadirkan tampilan riasan yang serasi, rapi, dan sesuai dengan karakter masing-masing individu.
                    Serenity beroperasi dengan mengutamakan ketelitian, kebersihan peralatan, serta penggunaan produk kosmetik yang aman dan berkualitas.</p>
                  <p>Dengan tenaga perias yang berpengalaman di bidang tata rias wajah dan penataan penampilan, Serenity berkomitmen memberikan hasil kerja yang profesional serta menjaga kenyamanan setiap klien selama proses pengerjaan.</p>
                </div>
                <div class="tab-pane fade" id="nav-vision" role="tabpanel" aria-labelledby="nav-vision-tab">
                  <p>Menjadi jasa rias pengantin profesional yang menghadirkan ketenangan, keserasian, dan keindahan alami bagi setiap pasangan melalui sentuhan make up yang elegan, rapi, dan harmonis.</p>

                </div>
                <div class="tab-pane fade" id="nav-history" role="tabpanel" aria-labelledby="nav-history-tab">
                  <p>1. Memberikan layanan rias pengantin pria dan wanita dengan hasil yang serasi, nyaman, serta sesuai karakter masing-masing individu.</p>
                  <p>2. Menggunakan produk kosmetik berkualitas dan aman untuk menjaga kesehatan kulit serta hasil riasan yang tahan lama.</p>
                  <p>3. Menjaga profesionalisme melalui ketepatan waktu, kebersihan alat, dan sikap kerja yang ramah terhadap setiap klien.</p>
                  <p>4. Mengembangkan keterampilan dan kreativitas dalam seni tata rias agar selalu mengikuti tren tanpa meninggalkan nilai keanggunan.</p>
                  <p>5. Membangun citra Serenity sebagai penyedia jasa make up yang mengedepankan ketenangan, keindahan, dan kepercayaan klien.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- container -->
  </section>

  <!--====== ABOUT FIVE PART ENDS ======-->

  <section id="katalog" class="services-area services-eight">
    <!-- judul/teks pengantar -->
    <div class="section-title-five">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="content">
              <h6>Katalog</h6>
              <h2 class="fw-bold">-Model pakaian beragam</h2>
              <p>
                Kami menyediakan berbagai pilihan pakaian adat yang dapat Anda pilih menyesuaikan dengan suku.
                Berikut ini merupakan pilihan rias yang dapat anda pilih:
              </p>
            </div>
          </div>
        </div>
        <!-- row -->
      </div>
    </div>


    <!-- bagian daftar kartu katalog -->
    <section class="py-5" id="katalog-list"> <!-- ganti/hapus id -->
      <div class="container">
        <div class="row g-4">
          <?php if ($res && $res->num_rows): ?>
            <?php while ($row = $res->fetch_assoc()):
              $img = !empty($row['foto_path']) ? esc($row['foto_path']) : 'assets/img/placeholder.jpg';
            ?>
              <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow-sm border-0">
                  <div class="ratio ratio-4x3">
                    <img src="<?= $img ?>" alt="<?= esc($row['nama_adat']) ?>" class="card-img-top object-fit-cover">
                  </div>
                  <div class="card-body d-flex flex-column">
                    <h5 class="card-title mb-1"><?= esc($row['nama_adat']) ?></h5>
                    <p class="card-text small text-muted flex-grow-1"><?= nl2br(esc($row['isi_desk'])) ?></p>
                    <div class="d-flex align-items-center justify-content-between">
                      <span class="badge bg-dark fs-6"><?= formatRupiah($row['harga']) ?></span>
                      <!-- urutan benar: query dulu, lalu hash -->
                      <a href="index.php?idadat=<?= (int)$row['idadat'] ?>#pricing" class="btn btn-primary btn-sm">
                        Sewa sekarang
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="alert alert-warning mb-0">Belum ada data katalog.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </section>

  <!-- Start Aturan Sewa Area -->
  <div id="aturan-sewa" class="rules-area section">
    <!--======  Start Section Title Five ======-->
    <div class="section-title-five">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="content text-center">
              <h6>Aturan Sewa</h6>
              <h2 class="fw-bold">Mohon dibaca sebelum memesan</h2>
              <p>
                Ringkasan kebijakan penyewaan Serenity. Dengan melakukan pemesanan,
                Anda setuju dengan poin-poin berikut.
              </p>
            </div>
          </div>
        </div>
        <!-- row -->
      </div>
      <!-- container -->
    </div>
    <!--======  End Section Title Five ======-->

    <div class="container">
      <div class="row g-4">
        <!-- Card 1 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-clipboard-check"></i></div>
            <h4 class="rule-title">DP & Pelunasan</h4>
            <p class="mb-0">
              DP minimal 50% untuk mengunci jadwal. Sisa pembayaran wajib
              <strong>lunas H-1</strong> sebelum acara. DP tidak dapat dikembalikan
              (non-refundable).
            </p>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-calendar-check"></i></div>
            <h4 class="rule-title">Perubahan Jadwal</h4>
            <p class="mb-0">
              Reschedule maksimal 1 kali dan mengikuti <em>ketersediaan</em>.
              Beritahu minimal <strong>7 hari</strong> sebelumnya.
            </p>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-clock-history"></i></div>
            <h4 class="rule-title">Waktu Kedatangan</h4>
            <p class="mb-0">
              Tim datang sesuai jam yang disepakati (toleransi 15 menit).
              Keterlambatan dari pihak penyewa mengurangi durasi pengerjaan.
            </p>
          </div>
        </div>

        <!-- Card 4 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-geo-alt"></i></div>
            <h4 class="rule-title">Lokasi & Transport</h4>
            <p class="mb-0">
              Biaya transport/akomodasi di luar area layanan ditanggung penyewa.
              Pastikan lokasi mudah diakses & aman untuk peralatan.
            </p>
          </div>
        </div>

        <!-- Card 5 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-box2-heart"></i></div>
            <h4 class="rule-title">Perlengkapan & Kebersihan</h4>
            <p class="mb-0">
              Seluruh alat dibersihkan sebelum/selesai digunakan. Mohon sediakan
              meja & kursi dengan pencahayaan memadai.
            </p>
          </div>
        </div>

        <!-- Card 6 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-shield-check"></i></div>
            <h4 class="rule-title">Pembatalan</h4>
            <p class="mb-0">
              Pembatalan oleh penyewa: DP hangus. Pembatalan oleh Serenity:
              refund penuh DP atau reschedule sesuai kesepakatan.
            </p>
          </div>
        </div>

        <!-- Card 7 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-people"></i></div>
            <h4 class="rule-title">Tambahan Personil</h4>
            <p class="mb-0">
              Permintaan MUA tambahan, touch-up crew, atau styling khusus akan
              dikenakan biaya tambahan sesuai paket.
            </p>
          </div>
        </div>

        <!-- Card 8 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-camera"></i></div>
            <h4 class="rule-title">Dokumentasi</h4>
            <p class="mb-0">
              Kami berhak mendokumentasikan hasil karya untuk portofolio kecuali
              penyewa meminta <em>opt-out</em> saat pemesanan.
            </p>
          </div>
        </div>

        <!-- Card 9 -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="rule-card h-100">
            <div class="rule-icon"><i class="bi bi-chat-dots"></i></div>
            <h4 class="rule-title">Konsultasi</h4>
            <p class="mb-0">
              Konsultasi gaya rias & uji alergi (bila perlu) dilakukan sebelum
              hari H. Sampaikan preferensi & kondisi kulit sejak awal.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End Aturan Sewa Area -->

  <!-- Start Pricing  Area -->
  <section id="pricing" class="pricing-area pricing-fourteen">
    <div class="section-title-five">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="content">
              <h6>Pesan</h6>
              <h2 class="fw-bold">Pengisian Data Sewa</h2>
              <p>Silakan isi formulir di bawah ini untuk melakukan pemesanan sewa make up pengantin:</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10 col-12">
          <form id="formSewa" action="sewa_process.php" method="POST" class="card shadow-sm border-0 p-4">

            <!-- PILIH PAKET (idadat) -->
            <div class="mb-3">
              <label for="idadat" class="form-label">Pilih Paket Adat</label>
              <select class="form-select form-control-lg" id="idadat" name="idadat" required>
                <option value="" disabled <?= $idadatDipilih ? '' : 'selected' ?>>Pilih adat yang diinginkan..</option>
                <?php foreach ($katalogList as $r): ?>
                  <option
                    value="<?= (int)$r['idadat'] ?>"
                    <?= ($idadatDipilih === (int)$r['idadat']) ? 'selected' : '' ?>>
                    <?= esc($r['nama_adat']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Silakan pilih salah satu paket.</div>
            </div>

            <!-- HARGA TOTAL AUTO (readonly view + hidden numeric) -->
            <div class="mb-4">
              <label for="harga_total_view" class="form-label">Harga Total</label>
              <input
                type="text"
                class="form-control form-control-lg"
                id="harga_total_view"
                value="<?= $hargaAsli ? esc(formatRupiah($hargaAsli)) : '' ?>"
                placeholder="Rp -"
                readonly>
              <!-- nilai numerik yang dikirim ke server -->
              <input type="hidden" id="harga_total" name="harga_total" value="<?= $hargaAsli ?: '' ?>">
              <div class="form-text">Harga yang ditampilkan belum termasuk biaya perjalanan.</div>
            </div>


            <!-- NAMA PENYEWA -->
            <div class="mb-3">
              <label for="nama_cust" class="form-label">Nama Penyewa</label>
              <input type="text" class="form-control" id="nama_cust" name="nama_cust" required>
            </div>

            <!-- EMAIL -->
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <!-- NOMOR TELP -->
            <div class="mb-3">
              <label for="notelp" class="form-label">Nomor Telepon</label>
              <input type="tel" class="form-control" id="notelp" name="notelp" required>
            </div>

            <!-- KOTA -->
            <div class="mb-3">
              <label for="kota" class="form-label">Kota</label>
              <input type="text" class="form-control" id="kota" name="kota" required>
            </div>

            <!-- TANGGAL SEWA -->
            <div class="mb-3">
              <label for="tgl_sewa" class="form-label">Tanggal Sewa</label>
              <input type="date" class="form-control" id="tgl_sewa" name="tgl_sewa" required>
            </div>

            <!-- JENIS PEMBAYARAN -->
            <div class="mb-4">
              <label for="jenisbayar" class="form-label">Jenis Pembayaran</label>
              <select class="form-select" id="jenisbayar" name="jenisbayar" required>
                <option value="" selected disabled>Pilih jenis pembayaran</option>
                <option value="Full">Full</option>
                <option value="DP">DP</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" id="btnKirim">Kirim Pemesanan</button>
              <a href="#katalog" class="btn btn-outline-secondary">Kembali ke Katalog</a>
            </div>

          </form>
        </div>
      </div>
    </div>
  </section>
  <!--/ End Pricing  Area -->



  <!-- Start Cta Area -->
  <section id="call-action" class="call-action">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-xxl-6 col-xl-7 col-lg-8 col-md-9">
          <div class="inner-content">
            <h2>Wujudkan pernikahanmu bersama Serenity.</h2>
            <p>
              Serenity adalah solusi lengkap untuk hari spesialmu—rias pengantin profesional + busana adat lengkap (Betawi, Sunda, Jawa, Batak, Minangkabau) dengan hasil yang tahan lama, fotogenik, 
              dan tetap nyaman seharian. Tim MUA kami datang on-site, menata aksesori sesuai pakem, dan siap retouch di momen penting. 
              Prosesnya praktis: pilih paket di website, isi formulir, terima email konfirmasi & invoice, lalu tunggu admin menghubungi via WA. 
              Siap tampil anggun dan percaya diri? Booking sekarang di Serenity!
            </p>
            <div class="light-rounded-buttons">
              <a href="index.php" class="btn primary-btn-outline">Get Started</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- End Cta Area -->

  <!-- ========================= contact-section start ========================= -->
  <section id="contact" class="contact-section">
    <div class="container">
      <div class="row">
        <div class="col-xl-4">
          <div class="contact-item-wrapper">
            <div class="row">
              <div class="col-12 col-md-6 col-xl-12">
                <div class="contact-item">
                  <div class="contact-icon">
                    <i class="lni lni-phone"></i>
                  </div>
                  <div class="contact-content">
                    <h4>Kontak</h4>
                    <p>0823-7251-6822</p>
                    <p>siapafikri045@gmail.com</p>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6 col-xl-12">
                <div class="contact-item">
                  <div class="contact-icon">
                    <i class="lni lni-alarm-clock"></i>
                  </div>
                  <div class="contact-content">
                    <h4>Jadwal Layanan</h4>
                    <p>06.00-20.00 WIB</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-8">
          <div class="contact-form-wrapper">
            <div class="row">
              <div class="col-xl-10 col-lg-8 mx-auto">
                <div class="section-title text-center">
                  <span> Get in Touch </span>
                  <h2>
                    Ready to Get Started
                  </h2>
                  <p>
                    At vero eos et accusamus et iusto odio dignissimos ducimus
                    quiblanditiis praesentium
                  </p>
                </div>
              </div>
            </div>
            <form action="#" class="contact-form">
              <div class="row">
                <div class="col-md-6">
                  <input type="text" name="name" id="name" placeholder="Name" required />
                </div>
                <div class="col-md-6">
                  <input type="email" name="email" id="email" placeholder="Email" required />
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <input type="text" name="phone" id="phone" placeholder="Phone" required />
                </div>
                <div class="col-md-6">
                  <input type="text" name="subject" id="email" placeholder="Subject" required />
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <textarea name="message" id="message" placeholder="Type Message" rows="5"></textarea>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="button text-center rounded-buttons">
                    <button type="submit" class="btn primary-btn rounded-full">
                      Send Message
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- ========================= contact-section end ========================= -->

  <!-- ========================= map-section end ========================= -->
  <section class="map-section map-style-9">
    <div class="map-container">
      <iframe
        style="border:0; height:500px; width:100%;"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps?q=Jakarta%2C%20Indonesia&z=11&output=embed">
      </iframe>
      <!-- fallback link (opsional) -->
      <p style="margin-top:8px; text-align:center;">
        <a href="https://www.google.com/maps/place/Jakarta,+Indonesia" target="_blank" rel="noopener">
          Buka peta di Google Maps
        </a>
      </p>
    </div>
  </section>

  <!-- ========================= map-section end ========================= -->

  <!-- Start Footer Area -->
  <footer class="footer-area footer-eleven">
    <!-- Start Footer Top -->
    <div class="footer-top">
      <div class="container">
        <div class="inner-content">
          <div class="row">
            <div class="col-lg-4 col-md-6 col-12">
              <!-- Single Widget -->
              <div class="footer-widget f-about">
                <div class="logo">
                  <a href="index.php">
                    <img src="assets/images/sere-logo.png" alt="#" class="img-fluid" width="140px" />
                  </a>
                </div>
                <p>
                  Making the world a better place through constructing elegant
                  hierarchies.
                </p>
                <p class="copyright-text">
                  <span>© 2025 Serenity Co.</span>
                </p>
              </div>
              <!-- End Single Widget -->
            </div>
            <div class="col-lg-2 col-md-6 col-12">
              <!-- Single Widget -->
              <div class="footer-widget f-link">
                <h5>Solutions</h5>
                <ul>
                  <li><a href="javascript:void(0)">Marketing</a></li>
                  <li><a href="javascript:void(0)">Analytics</a></li>
                  <li><a href="javascript:void(0)">Commerce</a></li>
                  <li><a href="javascript:void(0)">Insights</a></li>
                </ul>
              </div>
              <!-- End Single Widget -->
            </div>
            <div class="col-lg-2 col-md-6 col-12">
              <!-- Single Widget -->
              <div class="footer-widget f-link">
                <h5>Support</h5>
                <ul>
                  <li><a href="javascript:void(0)">Pricing</a></li>
                  <li><a href="javascript:void(0)">Documentation</a></li>
                  <li><a href="javascript:void(0)">Guides</a></li>
                  <li><a href="javascript:void(0)">API Status</a></li>
                </ul>
              </div>
              <!-- End Single Widget -->
            </div>
            <div class="col-lg-4 col-md-6 col-12">
              <!-- Single Widget -->
              <div class="footer-widget newsletter">
                <h5>Subscribe</h5>
                <p>Subscribe to our newsletter for the latest updates</p>
                <form action="#" method="get" target="_blank" class="newsletter-form">
                  <input name="EMAIL" placeholder="Email address" required="required" type="email" />
                  <div class="button">
                    <button class="sub-btn">
                      <i class="lni lni-envelope"></i>
                    </button>
                  </div>
                </form>
              </div>
              <!-- End Single Widget -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--/ End Footer Top -->
  </footer>
  <!--/ End Footer Area -->


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
    navbarTogglerNine.addEventListener("click", function() {
      navbarTogglerNine.classList.toggle("active");
    });

    // ==== left sidebar toggle
    let sidebarLeft = document.querySelector(".sidebar-left");
    let overlayLeft = document.querySelector(".overlay-left");
    let sidebarClose = document.querySelector(".sidebar-close .close");

    overlayLeft.addEventListener("click", function() {
      sidebarLeft.classList.toggle("open");
      overlayLeft.classList.toggle("open");
    });
    sidebarClose.addEventListener("click", function() {
      sidebarLeft.classList.remove("open");
      overlayLeft.classList.remove("open");
    });

    // ===== navbar nine sideMenu
    let sideMenuLeftNine = document.querySelector(".navbar-nine .menu-bar");

    sideMenuLeftNine.addEventListener("click", function() {
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

    selectPaket.addEventListener('change', function() {
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