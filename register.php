<?php
require 'config.php';

$error = "";

// Show error if redirected back from snk.php due to duplicate email
if (isset($_GET['err']) && $_GET['err'] === 'duplicate') {
    $error = "Email sudah terdaftar.";
}

if (isset($_POST['register'])) {
    $name             = trim($_POST['name']);
    $email            = trim($_POST['email']);
    $phone            = trim($_POST['phone']);
    $address          = trim($_POST['address']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role             = $_POST['role'];

    if ($name == "" || $email == "" || $phone == "" || $password == "" || $confirm_password == "") {
        $error = "Semua field wajib diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak sama.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            // Save pending data to session, redirect to S&K page
            $_SESSION['pending_register'] = [
                'name'    => $name,
                'email'   => $email,
                'phone'   => $phone,
                'address' => $address,
                'hashed'  => password_hash($password, PASSWORD_DEFAULT),
                'role'    => $role,
            ];
            header("Location: snk.php?mode=register");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun – Berbagi Donasi Social</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:ital,wght@1,500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-y: auto;
            padding: 40px 16px;
        }

        /* Background decoration */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 15% 40%, rgba(37,99,235,.22) 0%, transparent 65%),
                radial-gradient(ellipse 50% 50% at 85% 70%, rgba(245,158,11,.10) 0%, transparent 65%);
            pointer-events: none;
        }

        .register-wrap {
            width: 100%;
            max-width: 480px;
            padding: 16px;
            position: relative;
            z-index: 1;
        }

        /* Brand */
        .brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-icon {
            width: 110px; height: 110px;
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .brand-icon img {
            width: 100%; height: 100%;
            object-fit: contain;
        }
        .brand h1 {
            font-family: 'Lora', Georgia, serif;
            font-style: italic;
            font-size: 1.1rem;
            color: #94a3b8;
            font-weight: 500;
            letter-spacing: .01em;
        }
        .brand h1 strong {
            font-style: normal;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            font-size: 1.3rem;
            display: block;
            margin-bottom: 2px;
            font-weight: 700;
        }

        /* Card */
        .register-card {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 20px;
            padding: 32px 32px 28px;
            backdrop-filter: blur(20px);
            box-shadow: 0 24px 60px rgba(0,0,0,.4);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .card-sub {
            font-size: 0.83rem;
            color: #64748b;
            margin-bottom: 24px;
        }

        /* Alert */
        .alert-error {
            background: rgba(220,38,38,.15);
            border: 1px solid rgba(220,38,38,.3);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.83rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Two-column grid for some fields */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Form */
        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 7px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap input,
        .input-wrap textarea,
        .input-wrap select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,.06);
            border: 1.5px solid rgba(255,255,255,.10);
            border-radius: 11px;
            color: #fff;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .input-wrap input::placeholder,
        .input-wrap textarea::placeholder { color: #475569; }
        .input-wrap input:focus,
        .input-wrap textarea:focus,
        .input-wrap select:focus {
            border-color: #2563eb;
            background: rgba(37,99,235,.08);
            box-shadow: 0 0 0 3px rgba(37,99,235,.18);
        }
        .input-wrap textarea {
            resize: vertical;
            min-height: 72px;
            line-height: 1.5;
        }
        /* Select dark styling */
        .input-wrap select {
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 38px;
        }
        .input-wrap select option {
            background: #1e293b;
            color: #fff;
        }
        /* Password eye toggle */
        .input-wrap input[type="password"],
        .input-wrap input[type="text"].pw-field {
            padding-right: 46px;
        }
        .eye-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color .2s;
        }
        .eye-btn:hover { color: #94a3b8; }
        .eye-btn svg { width: 18px; height: 18px; display: block; }

        /* Submit button */
        .btn-register {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 11px;
            font-size: 0.93rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(37,99,235,.4);
            letter-spacing: .02em;
        }
        .btn-register:hover {
            opacity: .9;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(37,99,235,.5);
        }
        .btn-register:active { transform: translateY(0); }

        .divider {
            text-align: center;
            margin: 24px 0 20px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.1);
        }
        .divider span {
            font-size: 0.75rem;
            color: #475569;
            white-space: nowrap;
            padding: 0 4px;
        }

        .login-link {
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
        }
        .login-link a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 600;
            transition: color .2s;
        }
        .login-link a:hover { color: #93c5fd; }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        .back-home a {
            font-size: 0.8rem;
            color: #475569;
            text-decoration: none;
            transition: color .2s;
        }
        .back-home a:hover { color: #94a3b8; }

        /* Role card selector */
        .role-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 2px;
        }
        .role-option { display: none; }
        .role-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 10px;
            border-radius: 11px;
            border: 1.5px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.04);
            cursor: pointer;
            transition: border-color .2s, background .2s;
            text-align: center;
            min-height: 96px;
        }
        .role-label .role-icon {
            width: 36px; height: 36px;
            margin: 0 auto;
            border-radius: 9px;
            background: rgba(255,255,255,.07);
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .role-label .role-icon svg {
            width: 18px; height: 18px;
            stroke: #64748b;
            transition: stroke .2s;
        }
        .role-label .role-text { display: flex; flex-direction: column; align-items: center; gap: 3px; }
        .role-label .role-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: #94a3b8;
            transition: color .2s;
        }
        .role-label .role-desc {
            font-size: 0.69rem;
            color: #475569;
            line-height: 1.4;
        }
        .role-option:checked + .role-label {
            border-color: #2563eb;
            background: rgba(37,99,235,.12);
        }
        .role-option:checked + .role-label .role-icon {
            background: rgba(37,99,235,.2);
        }
        .role-option:checked + .role-label .role-icon svg { stroke: #60a5fa; }
        .role-option:checked + .role-label .role-name { color: #60a5fa; }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
            .register-card { padding: 24px 20px 20px; }
        }
    </style>
</head>
<body>
    <div class="register-wrap">

        <div class="brand">
            <div class="brand-icon">
                <img src="uploads/LogoBerbagiDonasiSocial3.PNG" alt="Logo Berbagi Donasi Social">
            </div>
            <h1>
                <strong>DAFTAR AKUN BERBAGI DONASI SOCIAL</strong>
            </h1>
        </div>

        <div class="register-card">
            <div class="card-title">Buat Akun Baru</div>
            <div class="card-sub">Isi informasi di bawah untuk mendaftar</div>

            <?php if ($error): ?>
                <div class="alert-error">&#9888; <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="on" id="registerForm">

                <!-- Nama & No Telepon side by side -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <div class="input-wrap">
                            <input type="text" id="name" name="name"
                                placeholder="Nama kamu"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                required autocomplete="name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="phone">No. Telepon</label>
                        <div class="input-wrap">
                            <input type="text" id="phone" name="phone"
                                placeholder="08xxxxxxxxxx"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                required autocomplete="tel">
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrap">
                        <input type="email" id="email" name="email"
                            placeholder="contoh@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required autocomplete="email">
                    </div>
                </div>

                <!-- Alamat -->
                <div class="form-group">
                    <label for="address">Alamat <span style="color:#475569;font-weight:400;text-transform:none">(opsional)</span></label>
                    <div class="input-wrap">
                        <textarea id="address" name="address"
                            placeholder="Alamat lengkap kamu..."><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Password & Konfirmasi side by side -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <input type="password" id="password" name="password"
                                placeholder="Min. 6 karakter"
                                minlength="6"
                                required autocomplete="new-password">
                            <button type="button" class="eye-btn" data-target="password" title="Tampilkan/Sembunyikan">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.526-4.129M6.343 6.343A9.96 9.96 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.357 2.835M6.343 6.343L3 3m3.343 3.343l11.314 11.314M9.88 9.88a3 3 0 104.24 4.24"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password</label>
                        <div class="input-wrap">
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Ulangi password"
                                required autocomplete="new-password">
                            <button type="button" class="eye-btn" data-target="confirm_password" title="Tampilkan/Sembunyikan">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.526-4.129M6.343 6.343A9.96 9.96 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.357 2.835M6.343 6.343L3 3m3.343 3.343l11.314 11.314M9.88 9.88a3 3 0 104.24 4.24"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Role selector -->
                <div class="form-group" style="margin-bottom:20px">
                    <label>Daftar Sebagai</label>
                    <div class="role-grid">
                        <div>
                            <input class="role-option" type="radio" name="role" id="role_donor"
                                value="donor" <?= (($_POST['role'] ?? 'donor') === 'donor') ? 'checked' : '' ?>>
                            <label class="role-label" for="role_donor">
                                <span class="role-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </span>
                                <span class="role-text">
                                    <span class="role-name">Donatur</span>
                                    <span class="role-desc">Berdonasi untuk kampanye yang ada</span>
                                </span>
                            </label>
                        </div>
                        <div>
                            <input class="role-option" type="radio" name="role" id="role_manager"
                                value="manager" <?= (($_POST['role'] ?? '') === 'manager') ? 'checked' : '' ?>>
                            <label class="role-label" for="role_manager">
                                <span class="role-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </span>
                                <span class="role-text">
                                    <span class="role-name">Pengelola</span>
                                    <span class="role-desc">Buat dan kelola kampanye donasi</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-register">
                    Lanjut
                </button>
            </form>

            <div class="divider"><span>atau</span></div>

            <div class="login-link">
                Sudah punya akun? <a href="login.php">Masuk sekarang</a>
            </div>
        </div>

        <div class="back-home">
            <a href="index.php">&#8592; Kembali ke Beranda</a>
        </div>

    </div>

    <script>
        // Eye toggle — handles both password fields
        document.querySelectorAll('.eye-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const input    = document.getElementById(targetId);
                const eyeOn    = this.querySelector('.icon-eye');
                const eyeOff   = this.querySelector('.icon-eye-off');
                const isHidden = input.type === 'password';
                input.type        = isHidden ? 'text' : 'password';
                eyeOn.style.display  = isHidden ? 'none'  : 'block';
                eyeOff.style.display = isHidden ? 'block' : 'none';
                input.focus();
            });
        });
    </script>
</body>
</html>