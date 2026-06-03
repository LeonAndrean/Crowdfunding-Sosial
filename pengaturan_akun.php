<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Fetch current user data
$stmt = $conn->prepare("SELECT id, name, email, phone, address, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ── Handle avatar upload ── */
    $avatarFilename = $user['avatar'] ?? '';
    if (!empty($_FILES['avatar']['name'])) {
        $file     = $_FILES['avatar'];
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize  = 3 * 1024 * 1024; // 3 MB

        if (!in_array($file['type'], $allowed)) {
            $errors[] = "Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.";
        } elseif ($file['size'] > $maxSize) {
            $errors[] = "Ukuran foto maksimal 3 MB.";
        } else {
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName  = 'avatar_' . $user_id . '_' . time() . '.' . strtolower($ext);
            $dest     = 'uploads/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Delete old avatar if exists
                if (!empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])) {
                    @unlink('uploads/' . $user['avatar']);
                }
                $avatarFilename = $newName;
            } else {
                $errors[] = "Gagal mengunggah foto. Coba lagi.";
            }
        }
    }

    /* ── Validate & collect fields ── */
    $newName    = trim($_POST['name'] ?? '');
    $newPhone   = trim($_POST['phone'] ?? '');
    $newAddress = trim($_POST['address'] ?? '');
    $newPass    = $_POST['new_password'] ?? '';
    $confPass   = $_POST['confirm_password'] ?? '';

    if (empty($newName))  $errors[] = "Nama tidak boleh kosong.";

    if ($newPass !== '') {
        if (strlen($newPass) < 6)        $errors[] = "Password baru minimal 6 karakter.";
        elseif ($newPass !== $confPass)  $errors[] = "Konfirmasi password tidak cocok.";
    }

    /* ── Save if no errors ── */
    if (empty($errors)) {
        if ($newPass !== '') {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, avatar=?, password=? WHERE id=?");
            $upd->bind_param("sssssi", $newName, $newPhone, $newAddress, $avatarFilename, $hashed, $user_id);
        } else {
            $upd = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, avatar=? WHERE id=?");
            $upd->bind_param("ssssi", $newName, $newPhone, $newAddress, $avatarFilename, $user_id);
        }
        $upd->execute();

        // Refresh session name
        $_SESSION['user_name'] = $newName;

        // Reload user data
        $stmt2 = $conn->prepare("SELECT id, name, email, phone, address, avatar FROM users WHERE id = ?");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $user = $stmt2->get_result()->fetch_assoc();

        $success = "Pengaturan akun berhasil disimpan!";
    }
}

// Avatar display helper
function avatarUrl(array $user): string {
    if (!empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])) {
        return 'uploads/' . htmlspecialchars($user['avatar']);
    }
    return '';
}

$backLink = 'index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun – Berbagi Donasi Social</title>
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
            color: #fff;
            padding: 0 40px;
            height: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .navbar-brand {
            font-family: 'Lora', serif;
            font-size: 1.2rem; font-weight: 600;
            color: #fff; letter-spacing: .01em;
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .navbar-brand img { height: 34px; width: auto; object-fit: contain; }
        .navbar-links { display: flex; align-items: center; gap: 4px; font-size: 0.88rem; }
        .navbar-links .user-greet { color: #94a3b8; margin-right: 6px; font-size: 0.85rem; }
        .navbar-links a {
            color: #cbd5e1; text-decoration: none; padding: 6px 12px;
            border-radius: 8px; transition: background .2s, color .2s; font-weight: 500;
        }
        .navbar-links a:hover { background: rgba(255,255,255,.1); color: #fff; }
        .navbar-links a.btn-nav-danger { background: #dc2626; color: #fff; margin-left: 4px; }
        .navbar-links a.btn-nav-danger:hover { background: #b91c1c; }

        /* ── Container ── */
        .container {
            width: 92%; max-width: 680px;
            margin: 0 auto;
            padding: 40px 0 72px;
        }

        /* ── Page head ── */
        .page-head { margin-bottom: 28px; }
        .page-head h1 { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .page-head p { font-size: 0.85rem; color: #64748b; }

        /* ── Alert ── */
        .alert {
            border-radius: 12px; padding: 14px 18px; margin-bottom: 22px;
            font-size: 0.85rem; font-weight: 600;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert ul { margin: 6px 0 0 18px; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
            overflow: hidden;
        }

        /* ── Avatar section ── */
        .avatar-section {
            background: linear-gradient(135deg, #1e40af, #2563eb, #0284c7);
            padding: 36px 32px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }
        .avatar-wrap {
            position: relative;
            width: 100px; height: 100px;
        }
        .avatar-img {
            width: 100px; height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,.4);
            display: block;
        }
        .avatar-placeholder {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            border: 3px solid rgba(255,255,255,.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; font-weight: 900; color: #fff;
            letter-spacing: -.02em;
        }
        .avatar-overlay {
            position: absolute; inset: 0;
            border-radius: 50%;
            background: rgba(0,0,0,.4);
            display: flex; align-items: center; justify-content: center;
            opacity: 0;
            transition: opacity .2s;
            cursor: pointer;
        }
        .avatar-wrap:hover .avatar-overlay { opacity: 1; }
        .avatar-overlay svg { width: 24px; height: 24px; color: #fff; }
        .avatar-name { font-size: 1.1rem; font-weight: 800; color: #fff; }
        .avatar-role {
            font-size: 0.73rem; font-weight: 700;
            padding: 3px 12px; border-radius: 99px;
            background: rgba(255,255,255,.18);
            color: rgba(255,255,255,.85);
            text-transform: uppercase; letter-spacing: .08em;
        }
        .avatar-hint { font-size: 0.72rem; color: rgba(255,255,255,.55); }

        /* ── Form section ── */
        .form-section { padding: 32px; }

        .section-divider {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .1em;
            color: #94a3b8;
            margin: 28px 0 18px;
            display: flex; align-items: center; gap: 10px;
        }
        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 0.78rem; font-weight: 700; color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .field input[type="text"],
        .field input[type="tel"],
        .field input[type="password"],
        .field textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1e293b;
            background: #f8fafc;
            transition: border-color .2s, background .2s, box-shadow .2s;
            outline: none;
        }
        .field input:focus,
        .field textarea:focus {
            border-color: #2563eb;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }
        .field input[readonly] {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }
        .field textarea { resize: vertical; min-height: 80px; }
        .field .hint {
            font-size: 0.73rem; color: #94a3b8; margin-top: 4px;
        }

        /* ── Hidden file input ── */
        #avatarInput { display: none; }

        /* ── Buttons ── */
        .btn-row {
            display: flex; gap: 10px; flex-wrap: wrap;
            margin-top: 28px; padding-top: 22px;
            border-top: 1.5px solid #f1f5f9;
        }
        .btn {
            display: inline-block; padding: 10px 22px;
            border-radius: 10px; font-size: 0.85rem; font-weight: 700;
            border: none; cursor: pointer; font-family: inherit;
            text-decoration: none;
            transition: opacity .18s, transform .15s, box-shadow .18s;
            white-space: nowrap;
        }
        .btn:hover { opacity: .88; transform: translateY(-1px); }
        .btn-primary {
            background: #2563eb; color: #fff;
            box-shadow: 0 4px 14px rgba(37,99,235,.3);
        }
        .btn-ghost {
            background: #f1f5f9; color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        .btn-ghost:hover { background: #e2e8f0; opacity: 1; }

        /* ── Footer ── */
        .dash-footer {
            margin-top: 48px; padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.78rem; color: #cbd5e1;
            text-align: center;
        }

        @media (max-width: 600px) {
            .navbar { padding: 0 16px; }
            .form-section { padding: 22px 18px; }
            .avatar-section { padding: 28px 16px 22px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="navbar-brand" href="index.php">
        <img src="uploads/LogoKecil.png" alt="Logo">
        Berbagi Donasi Social
    </a>
    <div class="navbar-links">
        <span class="user-greet">Halo, <?= htmlspecialchars($_SESSION['user_name']) ?> |</span>
        <a href="<?= $backLink ?>">Beranda</a>
        <a href="logout.php" class="btn-nav-danger">Logout</a>
    </div>
</nav>

<div class="container">

    <div class="page-head">
        <h1>Pengaturan Akun</h1>
        <p>Kelola informasi profil dan keamanan akun Anda</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Terdapat kesalahan:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="card">

            <!-- Avatar section -->
            <div class="avatar-section">
                <div class="avatar-wrap" onclick="document.getElementById('avatarInput').click()" title="Klik untuk ganti foto">
                    <?php $av = avatarUrl($user); ?>
                    <?php if ($av): ?>
                        <img class="avatar-img" id="avatarPreview" src="<?= $av ?>" alt="Avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="avatarPreview">
                            <?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="avatar-overlay">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </div>
                </div>
                <div class="avatar-name"><?= htmlspecialchars($user['name']) ?></div>
                <span class="avatar-role"><?= $user_role === 'manager' ? 'Pengelola Campaign' : 'Donatur' ?></span>
                <span class="avatar-hint">Klik foto untuk mengganti</span>
                <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
            </div>

            <!-- Form fields -->
            <div class="form-section">

                <div class="section-divider">Informasi Pribadi</div>

                <div class="field">
                    <label>Nama Lengkap <span style="color:#ef4444">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required placeholder="Nama lengkap Anda">
                </div>

                <div class="field">
                    <label>Email</label>
                    <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    <div class="hint">Email tidak dapat diubah.</div>
                </div>

                <div class="field">
                    <label>Nomor Telepon</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="cth. 0812xxxxxxxx">
                </div>

                <div class="field">
                    <label>Alamat</label>
                    <textarea name="address" placeholder="Alamat lengkap Anda"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>

                <div class="section-divider">Ubah Password</div>

                <div class="field">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" placeholder="Kosongkan jika tidak ingin ubah password" autocomplete="new-password">
                    <div class="hint">Minimal 6 karakter.</div>
                </div>

                <div class="field">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" placeholder="Ulangi password baru" autocomplete="new-password">
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="<?= $backLink ?>" class="btn btn-ghost">Kembali</a>
                </div>

            </div>
        </div>
    </form>

    <div class="dash-footer">Copyright &copy; 2026 Berbagi Donasi Social. All Rights Reserved</div>

</div>

<script>
// Live avatar preview before upload
document.getElementById('avatarInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    if (!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)) {
        alert('Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.');
        return;
    }
    if (file.size > 3 * 1024 * 1024) {
        alert('Ukuran file terlalu besar. Maksimal 3 MB.');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const wrap = document.querySelector('.avatar-wrap');
        // Replace whatever is inside with an <img>
        const existing = document.getElementById('avatarPreview');
        if (existing.tagName === 'DIV') {
            // Was placeholder, replace with img
            const img = document.createElement('img');
            img.className = 'avatar-img';
            img.id = 'avatarPreview';
            img.src = e.target.result;
            img.alt = 'Avatar Preview';
            wrap.replaceChild(img, existing);
        } else {
            existing.src = e.target.result;
        }
    };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>