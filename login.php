<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Serenity Admin — Login</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --panel: #111827;
            --muted: #94a3b8;
            --text: #e5e7eb;
            --brand: #2563eb;
            --brand-2: #3b82f6;
            --ring: rgba(59, 130, 246, .45);
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(1200px 600px at 10% 10%, rgba(37, 99, 235, .08), transparent),
                radial-gradient(1200px 600px at 90% 90%, rgba(16, 185, 129, .07), transparent),
                linear-gradient(180deg, #0b1220 0%, #0a0f1c 100%);
            display: grid;
            place-items: center;
            padding: 32px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
            border: 1px solid rgba(148, 163, 184, .15);
            border-radius: 20px;
            padding: 28px 26px;
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, .35), inset 0 1px 0 rgba(255, 255, 255, .04);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            margin-bottom: 14px;
            font-weight: 700
        }

        .brand .logo {
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

        h1 {
            font-size: 18px;
            text-align: center;
            margin: 0 0 20px 0;
            font-weight: 600;
            color: #e2e8f0
        }

        label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin: 12px 2px 6px
        }

        .field {
            position: relative
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: #0b1220;
            border: 1px solid rgba(148, 163, 184, .25);
            color: var(--text);
            border-radius: 12px;
            padding: 12px 14px;
            outline: none;
            transition: all .2s ease;
        }

        input::placeholder {
            color: #64748b
        }

        input:focus {
            border-color: var(--brand-2);
            box-shadow: 0 0 0 4px var(--ring)
        }

        .toggle-pass {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: 0;
            color: #94a3b8;
            cursor: pointer;
            font-size: 12px;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px
        }

        button[type="submit"] {
            width: 100%;
            border: 0;
            cursor: pointer;
            border-radius: 12px;
            padding: 12px 14px;
            font-weight: 600;
            background: linear-gradient(90deg, var(--brand), var(--brand-2));
            color: #fff;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .35);
            transition: transform .06s ease, box-shadow .2s ease, filter .2s ease;
        }

        .actions .btn {
            display: inline-block;
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            border: 0;
            cursor: pointer;
            transition: transform .06s ease, filter .2s ease;
        }

        .actions .btn-black {
            background: #000;
            /* tombol hitam */
            color: #fff;
            box-shadow: 0 6px 14px rgba(0, 0, 0, .35);
        }

        .actions .btn:hover {
            filter: brightness(1.05)
        }

        .actions .btn:active {
            transform: translateY(1px)
        }

        button[type="submit"]:hover {
            filter: brightness(1.05)
        }

        button[type="submit"]:active {
            transform: translateY(1px)
        }

        .back-link {
            display: inline-block;
            text-align: center;
            font-size: 12px;
            color: #93c5fd;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline
        }

        .foot {
            margin-top: 14px;
            text-align: center;
            font-size: 12px;
            color: var(--muted)
        }
    </style>
</head>

<body>
    <!-- Form POST ke ceklogin.php (redirect-based) -->
    <form class="card" method="POST" action="login_process.php" autocomplete="on" novalidate>
        <div class="brand">
            <div class="logo">SA</div>
            <div>Serenity <span style="opacity:.8">Admin</span></div>
        </div>
        <h1>Welcome back 👋</h1>

        <label for="username">USERNAME</label>
        <div class="field">
            <input id="username" name="username" type="text" placeholder="Masukkan Username" required autofocus />
        </div>

        <label for="password">PASSWORD</label>
        <div class="field">
            <input id="password" name="password" type="password" placeholder="••••••••" required />
            <button type="button" class="toggle-pass" aria-label="Show password" id="togglePass">Show</button>
        </div>

        <div class="actions">
            <button type="submit">Log in</button>
            <button type="button" class="btn btn-black" onclick="location.href='index.php'">
                Kembali ke Beranda
            </button>

        </div>

        <div class="foot"><strong>Serenity @2025</strong></div>
    </form>

    <script>
        // Toggle Show/Hide password
        const toggle = document.getElementById('togglePass');
        const pass = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const show = pass.getAttribute('type') === 'password';
            pass.setAttribute('type', show ? 'text' : 'password');
            toggle.textContent = show ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });

        // Fokus awal
        window.addEventListener('load', () => {
            document.getElementById('username').focus();
        });
    </script>

    <?php
    // SweetAlert flash dari ceklogin.php
    if (!empty($_SESSION['flash'])):
        $type = $_SESSION['flash']['type'];
        $msg  = $_SESSION['flash']['msg'];
        unset($_SESSION['flash']);
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: <?= json_encode($type === 'success' ? 'success' : 'error') ?>,
                    title: <?= json_encode($type === 'success' ? 'Berhasil' : 'Login Gagal!') ?>,
                    text: <?= json_encode($msg) ?>,
                    timer: <?= $type === 'success' ? 1300 : 'null' ?>,
                    showConfirmButton: <?= $type === 'success' ? 'false' : 'true' ?>
                }).then(() => {
                    <?php if ($type === 'success'): ?>
                        window.location.href = 'admin.php';
                    <?php endif; ?>
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>