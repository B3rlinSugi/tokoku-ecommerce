<?php
include 'register_process.php';
?>

<script>
    function registerPopup() {
        Swal.fire({
            title: 'Registrasi Akun Customer',
            html: `
            <div class="text-start mb-2">
                <label class="small fw-bold">Nama Lengkap</label>
                <input type="text" id="reg_nama" class="swal2-input m-0 w-100" placeholder="Nama Anda">
            </div>
            <div class="text-start mt-3">
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
                const nama = Swal.getPopup().querySelector('#reg_nama').value;
                const phone = Swal.getPopup().querySelector('#reg_phone').value;
                const password = Swal.getPopup().querySelector('#reg_password').value;
                const confirm = Swal.getPopup().querySelector('#reg_confirm').value;

                if (!nama || !phone || !password || !confirm) {
                    Swal.showValidationMessage(`Harap isi semua field!`);
                    return false;
                }
                if (password !== confirm) {
                    Swal.showValidationMessage(`Konfirmasi password tidak cocok!`);
                    return false;
                }
                return {nama: nama, phone: phone, password: password};
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('nama', result.value.nama);
                formData.append('phone', result.value.phone);
                formData.append('password', result.value.password);

                fetch('user/register_process.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Akun berhasil dibuat. Selamat datang!',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                // HALAMAN AKAN RELOAD DISINI
                                // window.location.reload();
                                window.location.href = 'index.php';
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