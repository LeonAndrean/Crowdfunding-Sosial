<?php
require 'config.php';

$error   = "";
$success = "";

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = "Akun berhasil dibuat. Silakan login.";
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $error = "email_not_found";
    } elseif (!password_verify($password, $user['password'])) {
        $error = "wrong_password";
    } else {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        if (isset($_SESSION['redirect_url'])) {
            $redirect = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']);
            header("Location: $redirect");
        } else {
            header("Location: index.php");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Crowdfunding Sosial</title>
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

        .login-wrap {
            width: 100%;
            max-width: 420px;
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
            width: 210px; height: 210px;
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
        .login-card {
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

        /* Error */
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

        /* Form */
        .form-group {
            margin-bottom: 16px;
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
        .input-wrap input {
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
        .input-wrap input::placeholder { color: #475569; }
        .input-wrap input:focus {
            border-color: #2563eb;
            background: rgba(37,99,235,.08);
            box-shadow: 0 0 0 3px rgba(37,99,235,.18);
        }
        /* Password field padding for eye button */
        .input-wrap input[type="password"],
        .input-wrap input[type="text"].pw-field {
            padding-right: 46px;
        }

        /* Eye toggle button */
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
        .btn-login {
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
            margin-top: 6px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(37,99,235,.4);
            letter-spacing: .02em;
        }
        .btn-login:hover {
            opacity: .9;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(37,99,235,.5);
        }
        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 28px 0 24px;
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

        .register-link {
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
        }
        .register-link a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 600;
            transition: color .2s;
        }
        .register-link a:hover { color: #93c5fd; }

        /* back link */
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

        /* ── Toast notif ── */
        #toast-notif {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 9999;
            padding: 15px 24px;
            font-size: 0.88rem;
            font-weight: 600;
            text-align: center;
            letter-spacing: .01em;
            animation: slideDown .35s ease;
        }
        #toast-notif.show { display: block; }
        #toast-notif.type-error {
            background: #dc2626;
            color: #fff;
            box-shadow: 0 4px 16px rgba(220,38,38,.4);
        }
        #toast-notif.type-success {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 4px 16px rgba(22,163,74,.4);
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .toast-inner {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            max-width: 640px; margin: 0 auto;
        }
        .toast-close {
            cursor: pointer; font-size: 1.1rem; opacity: .8; flex-shrink: 0;
        }
        .toast-close:hover { opacity: 1; }
    </style>
</head>
<body>
    <!-- Toast notif -->
    <div id="toast-notif">
        <div class="toast-inner">
            <span id="toast-msg"></span>
            <span class="toast-close" onclick="hideToast()">&#10005;</span>
        </div>
    </div>

    <div class="login-wrap">

        <div class="brand">
            <div class="brand-icon">
                <img src="uploads/LogoBerbagiDonasiSocial3.PNG" alt="Logo Crowdfunding Sosial">
            </div>
            <h1>
                <STRONG>
                LOGIN AKUN BERBAGI DONASI SOCIAL</STRONG>
            </h1>
        </div>

        <div class="login-card">
            <div class="card-title">Masuk ke Akun</div>
            <div class="card-sub">Selamat datang kembali</div>

            <form method="post" autocomplete="on">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrap">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="contoh@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="eye-btn" id="eyeBtn" title="Tampilkan/Sembunyikan password" aria-label="Toggle password visibility">
                            <!-- Eye icon (default: show) -->
                            <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <!-- Eye-off icon (hidden by default) -->
                            <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.526-4.129M6.343 6.343A9.96 9.96 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.357 2.835M6.343 6.343L3 3m3.343 3.343l11.314 11.314M9.88 9.88a3 3 0 104.24 4.24"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-login">Masuk</button>
            </form>

            <div class="divider"><span>atau</span></div>

            <div class="register-link">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </div>


    <script>
        // ── Toast ──
        function showToast(msg, type) {
            const el = document.getElementById('toast-notif');
            document.getElementById('toast-msg').textContent = msg;
            el.className = 'show type-' + type;
            clearTimeout(window._toastTimer);
            window._toastTimer = setTimeout(hideToast, 6000);
        }
        function hideToast() {
            document.getElementById('toast-notif').className = '';
        }

        <?php if ($error === 'wrong_password'): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showToast('Password yang anda ketik salah, mohon ulangi lagi.', 'error');
        });
        <?php elseif ($error === 'email_not_found'): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showToast('Email anda belum terdaftar sebagai Donatur atau Pengelola.', 'error');
        });
        <?php elseif ($success): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showToast('<?= addslashes($success) ?>', 'success');
        });
        <?php endif; ?>

        // ── Eye toggle ──
        const eyeBtn     = document.getElementById('eyeBtn');
        const pwInput    = document.getElementById('password');
        const iconEye    = document.getElementById('iconEye');
        const iconEyeOff = document.getElementById('iconEyeOff');

        eyeBtn.addEventListener('click', function () {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            iconEye.style.display    = isHidden ? 'none'  : 'block';
            iconEyeOff.style.display = isHidden ? 'block' : 'none';
            pwInput.focus();
        });
    </script>
</body>
</html>