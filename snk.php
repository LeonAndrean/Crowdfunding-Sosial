<?php
require 'config.php';

// Mode: 'register' (from register flow with pending data) or 'read' (just reading)
$mode = $_GET['mode'] ?? 'read';

// If coming from register flow, retrieve pending data from session
$pendingData = [];
if ($mode === 'register' && isset($_SESSION['pending_register'])) {
    $pendingData = $_SESSION['pending_register'];
}

// If user submitted the checkbox (agreed) and came from register flow
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agree_snk'])) {
    if (!isset($_POST['snk_checked'])) {
        $error = 'snk_not_checked';
    } elseif (!empty($pendingData)) {
        // Process the registration
        $name     = $pendingData['name'];
        $email    = $pendingData['email'];
        $phone    = $pendingData['phone'];
        $address  = $pendingData['address'];
        $hashed   = $pendingData['hashed'];
        $role     = $pendingData['role'];

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            unset($_SESSION['pending_register']);
            header("Location: register.php?err=duplicate");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $address, $hashed, $role);

        if ($stmt->execute()) {
            unset($_SESSION['pending_register']);
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = 'db_error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat &amp; Ketentuan – Berbagi Donasi Social</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@1,500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0f4f8;
            color: #1e293b;
            min-height: 100vh;
        }

        /* ── Navbar ── */
        .navbar {
            background: #0f172a;
            padding: 0 40px;
            height: 60px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .navbar-brand {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.2rem; font-weight: 600; color: #fff;
            text-decoration: none; display: flex; align-items: center; gap: 10px;
        }
        .navbar-brand img { height: 34px; width: auto; object-fit: contain; }
        .navbar-links { display: flex; align-items: center; gap: 4px; font-size: 0.88rem; }
        .navbar-links a {
            color: #cbd5e1; text-decoration: none; padding: 6px 12px;
            border-radius: 8px; transition: background .2s, color .2s; font-weight: 500;
        }
        .navbar-links a:hover { background: rgba(255,255,255,.1); color: #fff; }

        /* ── Toast notif ── */
        #toast-notif {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 9999;
            background: #dc2626;
            color: #fff;
            padding: 14px 24px;
            font-size: 0.92rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: .01em;
            box-shadow: 0 4px 16px rgba(220,38,38,.35);
            animation: slideDown .35s ease;
        }
        #toast-notif.show { display: block; }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        #toast-notif .toast-close {
            cursor: pointer;
            margin-left: 16px;
            font-size: 1.1rem;
            opacity: .8;
        }
        #toast-notif .toast-close:hover { opacity: 1; }

        /* ── Page ── */
        .page {
            max-width: 860px;
            margin: 0 auto;
            padding: 40px 20px 80px;
        }

        /* ── Header card ── */
        .snk-header {
            background: linear-gradient(135deg, #0f172a, #1e3a5f);
            border-radius: 18px;
            padding: 36px 40px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }
        .snk-header::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 60% 70% at 5% 50%, rgba(37,99,235,.25) 0%, transparent 70%);
        }
        .snk-header h1 {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.7rem; font-weight: 600; color: #fff;
            position: relative; margin-bottom: 8px;
        }
        .snk-header p {
            font-size: 0.83rem; color: #94a3b8; position: relative;
        }
        .snk-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(37,99,235,.25); border: 1px solid rgba(37,99,235,.4);
            color: #93c5fd; border-radius: 99px;
            padding: 5px 14px; font-size: 0.75rem; font-weight: 700;
            margin-bottom: 14px; position: relative;
        }

        /* ── Content card ── */
        .snk-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(15,23,42,.06);
            margin-bottom: 24px;
        }
        .snk-card-inner {
            padding: 32px 40px;
            max-height: 520px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .snk-card-inner::-webkit-scrollbar { width: 6px; }
        .snk-card-inner::-webkit-scrollbar-track { background: #f1f5f9; }
        .snk-card-inner::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        .snk-section { margin-bottom: 28px; }
        .snk-section:last-child { margin-bottom: 0; }

        .snk-section h2 {
            font-size: 1rem; font-weight: 800; color: #1e293b;
            margin-bottom: 10px; padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
            display: flex; align-items: center; gap: 8px;
        }
        .snk-section h2 .num {
            width: 26px; height: 26px;
            background: #2563eb; color: #fff;
            border-radius: 99px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 800; flex-shrink: 0;
        }
        .snk-section p {
            font-size: 0.88rem; color: #475569; line-height: 1.75;
            margin-bottom: 8px;
        }
        .snk-section ul {
            padding-left: 20px; margin-top: 6px;
        }
        .snk-section ul li {
            font-size: 0.88rem; color: #475569; line-height: 1.75; margin-bottom: 4px;
        }
        .snk-section ul li::marker { color: #2563eb; }

        .snk-updated {
            font-size: 0.76rem; color: #94a3b8;
            text-align: right; padding: 12px 40px;
            border-top: 1px solid #f1f5f9;
            font-style: italic;
        }

        /* ── Agree section (register mode only) ── */
        .agree-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 28px 36px;
            box-shadow: 0 4px 24px rgba(15,23,42,.06);
        }
        .agree-section h3 {
            font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 16px;
        }
        .agree-row {
            display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px;
        }
        .agree-row input[type="checkbox"] {
            width: 20px; height: 20px;
            accent-color: #2563eb;
            flex-shrink: 0; margin-top: 2px; cursor: pointer;
        }
        .agree-row label {
            font-size: 0.88rem; color: #475569; line-height: 1.6; cursor: pointer;
        }
        .agree-row label strong { color: #1e293b; }
        .btn-agree {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; border-radius: 11px;
            font-size: 0.93rem; font-weight: 700; font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(37,99,235,.35);
            transition: opacity .2s, transform .15s;
        }
        .btn-agree:hover { opacity: .9; transform: translateY(-1px); }
        .btn-agree:active { transform: translateY(0); }

        /* read-only mode back btn */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.82rem; font-weight: 600; color: #64748b;
            text-decoration: none; margin-bottom: 24px;
            padding: 8px 16px; border-radius: 9px;
            background: #fff; border: 1px solid #e2e8f0;
            transition: background .2s, color .2s;
        }
        .back-link:hover { background: #f1f5f9; color: #1e293b; }

        @media (max-width: 600px) {
            .navbar { padding: 0 16px; }
            .snk-header { padding: 24px 20px; }
            .snk-card-inner { padding: 20px; }
            .agree-section { padding: 20px; }
            .snk-updated { padding: 12px 20px; }
        }
    </style>
</head>
<body>

<!-- Toast notif (JS-controlled) -->
<div id="toast-notif">
    <span id="toast-msg"></span>
    <span class="toast-close" onclick="hideToast()">&#10005;</span>
</div>

<!-- Navbar -->
<nav class="navbar">
    <a class="navbar-brand" href="index.php">
        <img src="uploads/LogoKecil.png" alt="Logo">
        Berbagi Donasi Social
    </a>
    <div class="navbar-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php">Beranda</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="index.php">Beranda</a>
            <a href="login.php">Login</a>
            <a href="register.php">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<div class="page">

    <?php if ($mode === 'register'): ?>
        <!-- register mode: no top back link -->
    <?php endif; ?>

    <!-- Header -->
    <div class="snk-header">
        <h1>Syarat &amp; Ketentuan</h1>
        <p>Harap baca seluruh syarat dan ketentuan berikut sebelum menggunakan layanan Berbagi Donasi Social.</p>
    </div>

    <!-- Content -->
    <div class="snk-card">
        <div class="snk-card-inner" id="snkContent">

            <div class="snk-section">
                <h2><span class="num">1</span> Penerimaan Syarat</h2>
                <p>Dengan mengakses dan menggunakan platform <strong>Berbagi Donasi Social</strong>, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan yang tercantum dalam dokumen ini. Jika Anda tidak menyetujui salah satu ketentuan, Anda tidak diperkenankan menggunakan layanan kami.</p>
                <p>Kami berhak mengubah syarat dan ketentuan ini sewaktu-waktu. Perubahan akan diinformasikan melalui platform dan berlaku sejak tanggal publikasi.</p>
            </div>

            <div class="snk-section">
                <h2><span class="num">2</span> Pendaftaran Akun</h2>
                <p>Untuk menggunakan fitur penuh platform ini, Anda diwajibkan untuk mendaftarkan akun dengan informasi yang benar, akurat, dan terkini. Setiap pengguna hanya diperbolehkan memiliki satu akun terdaftar.</p>
                <ul>
                    <li>Anda bertanggung jawab penuh atas kerahasiaan kata sandi akun Anda.</li>
                    <li>Anda tidak diperkenankan membagikan akses akun kepada pihak lain.</li>
                    <li>Segala aktivitas yang terjadi di bawah akun Anda adalah tanggung jawab Anda sepenuhnya.</li>
                    <li>Kami berhak menangguhkan atau menghapus akun yang terbukti melanggar ketentuan.</li>
                </ul>
            </div>

            <div class="snk-section">
                <h2><span class="num">3</span> Ketentuan Donasi</h2>
                <p>Seluruh donasi yang dilakukan melalui platform ini bersifat sukarela dan tidak dapat dikembalikan kecuali donasi ditolak oleh pengelola kampanye.</p>
                <ul>
                    <li>Nominal minimum donasi adalah Rp10.000 per transaksi.</li>
                    <li>Setiap donasi wajib disertai bukti transfer yang valid (format JPG/JPEG).</li>
                    <li>Donasi akan masuk ke status <em>Pending</em> dan memerlukan verifikasi dari pengelola kampanye.</li>
                    <li>Dana yang telah diverifikasi akan langsung ditambahkan ke total dana kampanye.</li>
                    <li>Kami tidak bertanggung jawab atas kerugian yang timbul akibat informasi donasi yang tidak benar.</li>
                </ul>
            </div>

            <div class="snk-section">
                <h2><span class="num">4</span> Ketentuan Kampanye</h2>
                <p>Pengelola kampanye wajib memastikan informasi kampanye yang dibuat adalah benar, jelas, dan tidak menyesatkan. Kampanye yang terbukti bersifat penipuan akan segera dihapus dan akun pengelola akan ditangguhkan.</p>
                <ul>
                    <li>Setiap kampanye harus memiliki target dana, deadline, dan deskripsi yang jelas.</li>
                    <li>Gambar kampanye harus relevan dan tidak mengandung konten yang melanggar hukum.</li>
                    <li>Kampanye yang telah memiliki dana terkumpul ≥ Rp10.000 tidak dapat dihapus.</li>
                    <li>Pengelola bertanggung jawab memverifikasi bukti transfer dari para donatur.</li>
                </ul>
            </div>

            <div class="snk-section">
                <h2><span class="num">5</span> Privasi dan Data Pengguna</h2>
                <p>Kami menghargai privasi Anda. Data pribadi yang Anda berikan hanya digunakan untuk keperluan operasional platform, termasuk verifikasi akun, notifikasi donasi, dan keperluan komunikasi resmi.</p>
                <ul>
                    <li>Data Anda tidak akan dijual atau dibagikan kepada pihak ketiga tanpa persetujuan Anda.</li>
                    <li>Kami menggunakan enkripsi kata sandi untuk menjaga keamanan akun Anda.</li>
                    <li>Anda berhak meminta penghapusan data akun Anda dengan menghubungi tim kami.</li>
                </ul>
            </div>

            <div class="snk-section">
                <h2><span class="num">6</span> Larangan Penggunaan</h2>
                <p>Pengguna dilarang keras menggunakan platform ini untuk:</p>
                <ul>
                    <li>Membuat kampanye penggalangan dana fiktif atau penipuan.</li>
                    <li>Mengunggah konten yang bersifat SARA, pornografi, atau melanggar hukum Indonesia.</li>
                    <li>Menggunakan data pengguna lain tanpa izin.</li>
                    <li>Melakukan manipulasi atau pemalsuan bukti transfer donasi.</li>
                    <li>Melakukan upaya peretasan atau gangguan terhadap sistem platform.</li>
                </ul>
                <p>Pelanggaran atas larangan di atas dapat berakibat pada pemblokiran akun permanen dan tindakan hukum sesuai peraturan yang berlaku.</p>
            </div>

            <div class="snk-section">
                <h2><span class="num">7</span> Penyelesaian Sengketa</h2>
                <p>Segala sengketa yang timbul dari penggunaan platform ini akan diselesaikan secara musyawarah mufakat. Apabila tidak tercapai kesepakatan, sengketa akan diselesaikan melalui jalur hukum yang berlaku di wilayah Republik Indonesia.</p>
            </div>

            <div class="snk-section">
                <h2><span class="num">8</span> Kontak</h2>
                <p>Jika Anda memiliki pertanyaan atau keluhan terkait syarat dan ketentuan ini, silakan hubungi kami melalui:</p>
                <ul>
                    <li>Instagram: <strong>@crowdfunding_sociall</strong></li>
                    <li>Email: <strong>support@berbagdonasisocial.id</strong></li>
                </ul>
            </div>

        </div>
        <div class="snk-updated">Terakhir diperbarui: Juni 2026</div>
    </div>

    <?php if ($mode === 'register' && !empty($pendingData)): ?>
    <!-- Agreement section (register flow) -->
    <div class="agree-section">
        <h3>Konfirmasi Persetujuan</h3>
        <form method="post" id="agreeForm">
            <div class="agree-row">
                <input type="checkbox" name="snk_checked" id="snkCheck" value="1">
                <label for="snkCheck">
                    Saya telah membaca dan memahami seluruh <strong>Syarat &amp; Ketentuan</strong> Berbagi Donasi Social, dan saya menyetujui untuk mematuhi semua ketentuan yang berlaku.
                </label>
            </div>
            <button type="submit" name="agree_snk" class="btn-agree">Setuju &amp; Buat Akun</button>
        </form>
    </div>
    <?php elseif ($mode === 'register' && empty($pendingData)): ?>
    <!-- No pending data, redirect -->
    <script>window.location.href = 'register.php';</script>
    <?php endif; ?>

    <?php if ($mode !== 'register'): ?>
    <div style="margin-top: 24px;">
        <a href="javascript:history.back()" class="back-link">Kembali</a>
    </div>
    <?php endif; ?>

</div>

<script>
function showToast(msg) {
    const toast = document.getElementById('toast-notif');
    document.getElementById('toast-msg').textContent = msg;
    toast.classList.add('show');
    // auto-hide after 5s
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(hideToast, 5000);
}
function hideToast() {
    document.getElementById('toast-notif').classList.remove('show');
}

<?php if ($error === 'snk_not_checked'): ?>
// Show toast on load because checkbox wasn't checked
window.addEventListener('DOMContentLoaded', function() {
    showToast('Anda belum mencentang Syarat dan Ketentuan. Mohon baca dan centang terlebih dahulu sebelum melanjutkan.');
});
<?php elseif ($error === 'db_error'): ?>
window.addEventListener('DOMContentLoaded', function() {
    showToast('Terjadi kesalahan saat membuat akun. Silakan coba lagi.');
});
<?php endif; ?>

// Client-side: intercept form submit and show toast if unchecked
<?php if ($mode === 'register' && !empty($pendingData)): ?>
document.getElementById('agreeForm').addEventListener('submit', function(e) {
    const cb = document.getElementById('snkCheck');
    if (!cb.checked) {
        e.preventDefault();
        showToast('Anda belum mencentang Syarat dan Ketentuan. Mohon baca dan centang terlebih dahulu sebelum melanjutkan.');
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }
});
<?php endif; ?>
</script>
</body>
</html>