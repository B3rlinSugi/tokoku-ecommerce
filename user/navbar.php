<?php ?>
<section class="navbar-area navbar-nine">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <nav class="navbar navbar-expand-lg">
                    <a class="navbar-brand" href="index.php">
                        <img src="assets/images/sere-logo.png" width="200px"/>
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
                        <div class="navbar-btn d-none d-lg-inline-block">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <ul class="navbar-nav">
                                    <li class="nav-item">
                                        <a class="nav-link text-danger bg-white rounded-pill px-4 shadow-sm border border-danger"
                                           href="javascript:void(0)"
                                           onclick="handleLogout()"
                                           style="font-weight: 400; transition: all 0.3s ease;">
                                            <i class="lni lni-exit"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</section>

<script>
    function handleLogout() {
        Swal.fire({
            title: 'Mau keluar?',
            text: "Anda harus login kembali untuk memesan layanan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Warna merah untuk konfirmasi
            cancelButtonColor: '#6c757d', // Warna abu untuk batal
            confirmButtonText: 'Ya, Keluar!',
            cancelButtonText: 'Batal',
            reverseButtons: true, // Tombol batal di kiri, keluar di kanan
            backdrop: `rgba(0, 0, 0, 0.4)` // Overlay gelap transparan
        }).then((result) => {
            if (result.isConfirmed) {
                // Berikan efek loading sebelum pindah halaman
                Swal.fire({
                    title: 'Logging out...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Arahkan ke file logout kamu
                window.location.href = 'user/logout.php';
            }
        })
    }
</script>