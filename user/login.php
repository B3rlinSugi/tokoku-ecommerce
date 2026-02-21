<?php ?>

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
                <form action="login_process.php" method="POST" class="card shadow-sm border-0 p-4">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="lni lni-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username"
                                   placeholder="Masukkan username" required>
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
                        <button type="submit" class="btn btn-primary py-2">Masuk</button>
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
    function registerPopup() {
        Swal.fire({
            title: 'Registrasi Akun',
            html: `
            <div class="text-start mb-2">
                <label class="small fw-bold">Nomor WhatsApp</label>
                <input type="tel" id="reg_phone" class="swal2-input m-0 w-100" placeholder="0812xxxx">
            </div>
            <div class="text-start mt-3">
                <label class="small fw-bold">Buat Password</label>
                <input type="password" id="reg_password" class="swal2-input m-0 w-100" placeholder="******">
            </div>
            <div class="text-start mt-3">
                <label class="small fw-bold">Ulangi Password</label>
                <input type="password" id="reg_confirm" class="swal2-input m-0 w-100" placeholder="******">
            </div>
        `,
            confirmButtonText: 'Daftar Sekarang',
            confirmButtonColor: '#4f46e5',
            showCancelButton: true,
            cancelButtonText: 'Batal',
            focusConfirm: false,
            preConfirm: () => {
                const phone = Swal.getPopup().querySelector('#reg_phone').value;
                const password = Swal.getPopup().querySelector('#reg_password').value;
                const confirm = Swal.getPopup().querySelector('#reg_confirm').value;

                // Validasi di sisi Client
                if (!phone || !password || !confirm) {
                    Swal.showValidationMessage(`Harap isi semua field!`);
                    return false;
                }
                if (password !== confirm) {
                    Swal.showValidationMessage(`Konfirmasi password tidak cocok!`);
                    return false;
                }
                if (password.length < 6) {
                    Swal.showValidationMessage(`Password minimal 6 karakter!`);
                    return false;
                }

                return { phone: phone, password: password };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('phone', result.value.phone);
                formData.append('password', result.value.password);

                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Akun Anda sudah aktif, silakan login.',
                                confirmButtonColor: '#4f46e5'
                            });
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                    });
            }
        });
    }
</script>