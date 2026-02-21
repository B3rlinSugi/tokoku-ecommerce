<?php ?>
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
                            Kami menyediakan berbagai pilihan pakaian adat yang dapat Anda pilih menyesuaikan dengan
                            suku.
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
                                    <img src="<?= $img ?>" alt="<?= esc($row['nama_adat']) ?>"
                                         class="card-img-top object-fit-cover">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1"><?= esc($row['nama_adat']) ?></h5>
                                    <p class="card-text small text-muted flex-grow-1"><?= nl2br(esc($row['isi_desk'])) ?></p>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge bg-dark fs-6"><?= formatRupiah($row['harga']) ?></span>
                                        <!-- urutan benar: query dulu, lalu hash -->
                                        <a href="index.php?idadat=<?= (int)$row['idadat'] ?>#pricing"
                                           class="btn btn-primary btn-sm">
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