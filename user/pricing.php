<?php ?>
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
                            <option value="" disabled <?= $idadatDipilih ? '' : 'selected' ?>>Pilih adat yang
                                diinginkan..
                            </option>
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