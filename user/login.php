<?php
include 'register.php';
?>

<section id="login-customer" class="pricing-area pricing-fourteen"
         style="background-color: #f9f9f9; padding-top: 150px; margin-top: -70px; padding-bottom: 80px;">
    <div class="section-title-five">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="content">
                        <h2 class="fw-bold">Login Customer</h2>
                        <p>Login untuk melakukan proses pemesanan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8 col-12">
                <form id="formLogin" class="card shadow-sm border-0 p-4">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Nomor WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="lni lni-phone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   placeholder="0812xxxx" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="lni lni-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Masukkan password" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" id="btnLogin" class="btn btn-primary py-2 text-white" style="background-color: #4f46e5; border: none;">Masuk</button>
                    </div>

                    <div class="text-center mt-4">
                        <p class="small text-muted mb-0">Belum punya akun Serenity?</p>
                        <a href="javascript:void(0)" onclick="registerPopup()"
                           class="btn btn-link btn-sm fw-bold text-decoration-none text-primary">
                            Daftar Sekarang
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    // Sekarang ID 'formLogin' sudah ada, jadi listener ini akan bekerja
    document.getElementById('formLogin')?.addEventListener('submit', function (e) {
        e.preventDefault();

        const btn = document.getElementById('btnLogin');
        const originalText = btn.textContent;

        btn.disabled = true;
        btn.textContent = 'Mengecek...';

        const formData = new FormData(this);

        fetch('user/login_process.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) throw new Error('Response server tidak ok');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Login!',
                        text: 'Selamat datang kembali!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Reload total ke index.php
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Gagal',
                        text: data.message,
                        confirmButtonColor: '#4f46e5'
                    });
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Gagal menghubungi server atau terjadi kesalahan sistem.', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            });
    });
</script>