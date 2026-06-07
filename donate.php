<?php
require 'config.php';

function progColor(int $pct): array {
    if ($pct <= 50)  return ['grad' => 'linear-gradient(90deg,#2563eb,#38bdf8)', 'fire' => false];
    if ($pct <= 75)  return ['grad' => 'linear-gradient(90deg,#d97706,#fbbf24)', 'fire' => false];
    return               ['grad' => 'linear-gradient(90deg,#dc2626,#f97316)', 'fire' => true];
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;

$stmt = $conn->prepare("
    SELECT c.*, u.name AS manager_name
    FROM campaigns c
    JOIN users u ON c.manager_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
if (!$campaign) die("Kampanye tidak ditemukan.");

$error = "";
$success = "";

if (isset($_POST['submit_donation'])) {
    $amount         = (int)$_POST['amount'];
    $payment_method = trim($_POST['payment_method']);
    $message        = trim($_POST['message']);

    if ($amount < 10000) {
        $error = "Nominal minimal donasi adalah Rp10.000.";
    } elseif (empty($payment_method)) {
        $error = "Anda belum memilih metode pembayaran, segera pilih metode pembayaran Anda.";
    } elseif (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Anda belum memberikan bukti pembayaran, silahkan upload bukti pembayaran Anda.";
    } else {
        $allowedExt = ['jpg','jpeg','png','pdf'];
        $ext = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            $error = "File harus JPG, JPEG, PNG, atau PDF.";
        } elseif ($_FILES['proof_file']['size'] > 2 * 1024 * 1024) {
            $error = "Ukuran file maksimal 2MB.";
        } else {
            $newName = time() . "_" . rand(1000,9999) . "." . $ext;
            if (move_uploaded_file($_FILES['proof_file']['tmp_name'], "uploads/" . $newName)) {
                $stmt2 = $conn->prepare("
                    INSERT INTO donations (campaign_id, donor_id, amount, payment_method, message, proof_file, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt2->bind_param("iiisss", $campaign_id, $_SESSION['user_id'], $amount, $payment_method, $message, $newName);
                if ($stmt2->execute()) {
                    $success = "Donasi berhasil dikirim dan menunggu verifikasi dari pengelola.";
                } else {
                    $error = "Gagal menyimpan donasi.";
                }
            } else {
                $error = "Gagal upload file.";
            }
        }
    }
}

$pct = ($campaign['target_amount'] > 0)
    ? min(100, round(($campaign['collected_amount'] / $campaign['target_amount']) * 100))
    : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donasi – <?= htmlspecialchars($campaign['title']) ?></title>
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
        .navbar-links a.danger { background: #dc2626; color: #fff; margin-left: 4px; }
        .navbar-links a.danger:hover { background: #b91c1c; }

        /* ── Page ── */
        .page {
            max-width: 680px;
            margin: 0 auto;
            padding: 36px 20px 60px;
        }

        /* ── Back button ── */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.82rem; font-weight: 600; color: #64748b;
            text-decoration: none; margin-bottom: 24px;
            padding: 8px 16px; border-radius: 9px;
            background: #fff; border: 1px solid #e2e8f0;
            transition: background .2s, color .2s, border-color .2s;
        }
        .back-link:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }

        /* ── Card ── */
        .donate-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(15,23,42,.07);
        }

        .card-header {
            background: linear-gradient(135deg, #0f172a, #1e3a5f);
            padding: 28px 32px;
            position: relative;
            overflow: hidden;
        }
        .card-header::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 70% 80% at 10% 50%, rgba(37,99,235,.2) 0%, transparent 70%);
        }
        .card-header h2 {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.3rem; font-weight: 600;
            color: #fff; margin-bottom: 4px;
            position: relative;
        }
        .card-header p {
            font-size: 0.82rem; color: #94a3b8;
            position: relative;
        }

        .card-body { padding: 28px 32px; }

        /* ── Campaign info strip ── */
        .campaign-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex; flex-wrap: wrap; gap: 12px 28px;
        }
        .ci-item .ci-label {
            font-size: 0.68rem; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px;
        }
        .ci-item .ci-value { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
        .ci-item .ci-value.green { color: #16a34a; }

        /* progress */
        .prog-bg { background: #e2e8f0; border-radius: 99px; height: 6px; overflow: hidden; margin-top: 14px; }
        .prog-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #2563eb, #06b6d4); }
        .prog-label {
            display: flex; justify-content: space-between;
            font-size: 0.72rem; color: #64748b; margin-top: 4px;
        }
        .prog-label .pct { color: #2563eb; font-weight: 700; }

        /* ── Donor info ── */
        .donor-info {
            display: flex; align-items: center; gap: 12px;
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 10px; padding: 12px 16px;
            margin-bottom: 24px;
        }
        .donor-avatar {
            width: 38px; height: 38px; border-radius: 99px;
            background: #2563eb; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; font-weight: 800; flex-shrink: 0;
        }
        .donor-info .d-name { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
        .donor-info .d-email { font-size: 0.75rem; color: #64748b; }

        /* ── Alert ── */
        .alert {
            padding: 12px 16px; border-radius: 10px;
            font-size: 0.84rem; font-weight: 600;
            margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px;
            line-height: 1.5;
        }
        .alert-icon { flex-shrink: 0; margin-top: 1px; font-size: 1rem; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

        /* ── Form ── */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block; font-size: 0.76rem; font-weight: 700;
            color: #475569; text-transform: uppercase; letter-spacing: .05em;
            margin-bottom: 7px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 12px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 0.9rem; color: #1e293b; background: #f8fafc;
            font-family: inherit; outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.10);
            background: #fff;
        }
        .form-group textarea { min-height: 90px; resize: vertical; }

        /* file upload */
        .file-upload-wrap {
            border: 1.5px dashed #cbd5e1;
            border-radius: 10px; padding: 20px;
            text-align: center; background: #f8fafc;
            cursor: pointer; transition: border-color .2s, background .2s;
            position: relative;
        }
        .file-upload-wrap:hover { border-color: #2563eb; background: #eff6ff; }
        .file-upload-wrap.has-file { border-color: #16a34a; background: #f0fdf4; }
        .file-upload-wrap input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer;
            width: 100%; height: 100%; border: none; padding: 0;
        }
        .file-upload-wrap input[type="file"]:focus { box-shadow: none; border: none; }
        .fu-icon { font-size: 1.4rem; margin-bottom: 6px; }
        .fu-text { font-size: 0.82rem; color: #64748b; }
        .fu-text strong { color: #2563eb; }
        .fu-hint { font-size: 0.72rem; color: #94a3b8; margin-top: 4px; }
        .fu-filename { font-size: 0.82rem; font-weight: 700; color: #15803d; margin-top: 4px; }

        /* inline validation hints */
        .field-hint {
            font-size: 0.74rem; margin-top: 5px; padding: 6px 10px;
            border-radius: 7px; font-weight: 600; display: none;
        }
        .field-hint.show { display: block; }
        .field-hint.warn { background: #fef3c7; color: #92400e; }

        /* submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; border-radius: 11px;
            font-size: 0.93rem; font-weight: 700; font-family: inherit;
            cursor: pointer; margin-top: 4px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(37,99,235,.35);
        }
        .btn-submit:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(37,99,235,.45); }
        .btn-submit:active { transform: translateY(0); }

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
            max-width: 700px; margin: 0 auto;
        }
        .toast-close { cursor: pointer; font-size: 1.1rem; opacity: .8; flex-shrink: 0; }
        .toast-close:hover { opacity: 1; }

        @media (max-width: 600px) {
            .navbar { padding: 0 16px; }
            .card-body { padding: 20px; }
            .card-header { padding: 22px 20px; }
        }
    </style>
</head>
<body>

<div id="toast-notif">
    <div class="toast-inner">
        <span id="toast-msg"></span>
        <span class="toast-close" onclick="hideToast()">&#10005;</span>
    </div>
</div>

<nav class="navbar">
    <a class="navbar-brand" href="index.php">
        <img src="uploads/LogoKecil.png" alt="Logo">
        Crowdfunding Sosial
    </a>
    <div class="navbar-links">
        <a href="index.php">Beranda</a>
        <a href="logout.php" class="danger">Logout</a>
    </div>
</nav>

<div class="page">

    <a href="detail.php?id=<?= $campaign['id'] ?>" class="back-link">Kembali ke Detail Campaign</a>

    <div class="donate-card">
        <div class="card-header">
            <h2>Donasi Sekarang</h2>
            <p><?= htmlspecialchars($campaign['title']) ?></p>
        </div>
        <div class="card-body">

            <!-- Campaign info -->
            <div class="campaign-info">
                <div class="ci-item">
                    <div class="ci-label">Target</div>
                    <div class="ci-value">Rp<?= number_format($campaign['target_amount'], 0, ',', '.') ?></div>
                </div>
                <div class="ci-item">
                    <div class="ci-label">Terkumpul</div>
                    <div class="ci-value green">Rp<?= number_format($campaign['collected_amount'], 0, ',', '.') ?></div>
                </div>
                <div class="ci-item">
                    <div class="ci-label">Pengelola</div>
                    <div class="ci-value"><?= htmlspecialchars($campaign['manager_name']) ?></div>
                </div>
                <div style="flex-basis:100%">
                    <?php $pc = progColor($pct); ?>
                    <div class="prog-bg">
                        <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $pc['grad'] ?>"></div>
                    </div>
                    <div class="prog-label">
                        <span class="pct"><?= $pct ?>% tercapai<?= $pc['fire'] ? ' 🔥' : '' ?></span>
                        <span>Deadline: <?= date('d M Y', strtotime($campaign['deadline'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Donor info -->
            <div class="donor-info">
                <div class="donor-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                <div>
                    <div class="d-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="d-email"><?= htmlspecialchars($_SESSION['user_email']) ?></div>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" id="donateForm" novalidate>

                <div class="form-group">
                    <label>Nominal Donasi (min. Rp10.000)</label>
                    <input type="number" name="amount" placeholder="Contoh: 50000"
                           value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="payment_method" id="paymentMethod">
                        <option value="">Pilih Metode Pembayaran</option>
                        <option value="Transfer Bank" <?= ($_POST['payment_method'] ?? '') === 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank</option>
                        <option value="E-Wallet" <?= ($_POST['payment_method'] ?? '') === 'E-Wallet' ? 'selected' : '' ?>>E-Wallet</option>
                    </select>
                    <div class="field-hint warn" id="hintPayment">
                        Anda belum memilih metode pembayaran, segera pilih metode pembayaran Anda.
                    </div>
                </div>

                <div class="form-group">
                    <label>Pesan Dukungan <span style="color:#94a3b8;font-weight:400;text-transform:none">(opsional)</span></label>
                    <textarea name="message" placeholder="Tulis pesan semangat untuk campaign ini..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Bukti Pembayaran</label>
                    <div class="file-upload-wrap" id="fileWrap">
                        <input type="file" name="proof_file" id="proofFile" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="fu-icon">&#128196;</div>
                        <div class="fu-text"><strong>Klik untuk upload</strong> atau seret file ke sini</div>
                        <div class="fu-hint">JPG, JPEG, PNG, PDF &middot; Maks. 2MB</div>
                        <div class="fu-filename" id="fuFilename"></div>
                    </div>
                    <div class="field-hint warn" id="hintProof">
                        Anda belum memberikan bukti pembayaran, silahkan upload bukti pembayaran Anda.
                    </div>
                </div>

                <button type="submit" name="submit_donation" class="btn-submit">Kirim Donasi</button>
            </form>

        </div>
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

    <?php if ($error): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('<?= addslashes(htmlspecialchars_decode($error)) ?>', 'error');
    });
    <?php elseif ($success): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('<?= addslashes(htmlspecialchars_decode($success)) ?>', 'success');
    });
    <?php endif; ?>

    // ── File upload preview ──
    const proofFile  = document.getElementById('proofFile');
    const fileWrap   = document.getElementById('fileWrap');
    const fuFilename = document.getElementById('fuFilename');

    proofFile.addEventListener('change', function () {
        if (this.files[0]) {
            fuFilename.textContent = this.files[0].name;
            fileWrap.classList.add('has-file');
        } else {
            fuFilename.textContent = '';
            fileWrap.classList.remove('has-file');
        }
    });

    // ── Client-side validation ──
    document.getElementById('donateForm').addEventListener('submit', function (e) {
        const amount  = parseInt(document.querySelector('[name="amount"]').value) || 0;
        const payment = document.getElementById('paymentMethod').value;
        const hasFile = proofFile.files.length > 0;

        if (!amount && !payment && !hasFile) {
            e.preventDefault();
            showToast('Lengkapi semua field donasi sebelum mengirim — nominal, metode pembayaran, dan bukti transfer wajib diisi.', 'error');
            return;
        }
        if (!amount || amount < 10000) {
            e.preventDefault();
            showToast('Nominal donasi minimal Rp10.000. Mohon masukkan jumlah yang sesuai.', 'error');
            return;
        }
        if (!payment) {
            e.preventDefault();
            showToast('Kamu belum memilih metode pembayaran. Silakan pilih terlebih dahulu.', 'error');
            return;
        }
        if (!hasFile) {
            e.preventDefault();
            showToast('Kamu belum mengupload bukti pembayaran. Silakan lampirkan bukti transfer kamu.', 'error');
            return;
        }
    });
</script>
</body>
</html>