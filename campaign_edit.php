<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: login.php");
    exit;
}

$manager_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND manager_id = ?");
$stmt->bind_param("ii", $id, $manager_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();

if (!$campaign) {
    die("Campaign tidak ditemukan atau Anda tidak memiliki akses.");
}

$error = "";
$success = "";

if (isset($_POST['update_campaign'])) {
    $title       = trim($_POST['title']);
    $category    = trim($_POST['category']);
    $location    = trim($_POST['location']);
    $description = trim($_POST['description']);
    $target_amount = (float)$_POST['target_amount'];
    $deadline    = $_POST['deadline'];
    $bank_info   = trim($_POST['bank_info']);

    $newImage = $campaign['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = "Gambar harus berformat JPG, JPEG, atau PNG.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $error = "Ukuran gambar maksimal 5MB.";
        } else {
            $fileName = time() . "_" . rand(1000, 9999) . "." . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $fileName)) {
                // optionally delete old image
                $newImage = $fileName;
            } else {
                $error = "Gagal upload gambar baru. Pastikan folder uploads/ bisa ditulis.";
            }
        }
    }

    if ($error === "") {
        $stmt2 = $conn->prepare("
            UPDATE campaigns
            SET title=?, category=?, location=?, description=?, target_amount=?, deadline=?, image=?, bank_info=?
            WHERE id=? AND manager_id=?
        ");
        $stmt2->bind_param("ssssdsssii", $title, $category, $location, $description, $target_amount, $deadline, $newImage, $bank_info, $id, $manager_id);

        if ($stmt2->execute()) {
            header("Location: manager_dashboard.php?updated=1");
            exit;
        } else {
            $error = "Gagal menyimpan perubahan. Silakan coba lagi.";
        }
    }
}

// Reload campaign data (in case of error, show latest posted values)
$imgPath = 'uploads/' . $campaign['image'];
$imgExists = !empty($campaign['image']) && file_exists($imgPath);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign – <?= htmlspecialchars($campaign['title']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; min-height: 100vh; }

        .topbar {
            background: #fff; padding: 14px 32px; display: flex;
            justify-content: space-between; align-items: center;
            box-shadow: 0 1px 6px rgba(0,0,0,.08);
        }
        .topbar h1 { font-size: 1.2rem; color: #1e293b; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; background: #f1f5f9; border-radius: 8px;
            text-decoration: none; color: #475569; font-size: 0.88rem;
            font-weight: 600; transition: background .2s;
        }
        .btn-back:hover { background: #e2e8f0; }

        .page-wrap {
            max-width: 860px; margin: 36px auto; padding: 0 16px 60px;
        }

        .edit-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden;
        }
        .edit-card-header {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            padding: 24px 32px; color: #fff;
        }
        .edit-card-header h2 { font-size: 1.3rem; margin-bottom: 4px; }
        .edit-card-header p { font-size: 0.85rem; opacity: .8; }
        .edit-card-body { padding: 32px; }

        .alert {
            padding: 12px 16px; border-radius: 10px; margin-bottom: 24px;
            font-size: 0.88rem; font-weight: 600;
        }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
        .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #86efac; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-full { grid-column: 1 / -1; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label {
            font-size: 0.82rem; font-weight: 700; color: #475569;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 0.9rem; color: #1e293b; background: #f8fafc;
            transition: border-color .2s, box-shadow .2s;
            outline: none; font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12);
            background: #fff;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }

        /* Image section */
        .image-section {
            background: #f8fafc; border: 1.5px dashed #cbd5e1;
            border-radius: 12px; padding: 24px; text-align: center;
        }
        .image-preview-wrap {
            margin-bottom: 16px;
        }
        .image-preview-wrap img {
            max-width: 100%; max-height: 260px; border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,.12); object-fit: cover;
        }
        .no-preview {
            width: 100%; height: 160px; background: #e2e8f0; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #94a3b8; font-size: 0.9rem;
        }
        .image-label-text {
            font-size: 0.82rem; color: #64748b; margin-bottom: 12px;
        }
        .image-label-text strong { color: #1e293b; }

        .file-input-label {
            display: inline-block; padding: 10px 22px;
            background: #f1f5f9; border: 1.5px solid #cbd5e1;
            border-radius: 8px; cursor: pointer; font-size: 0.85rem;
            color: #475569; font-weight: 600; transition: background .2s;
        }
        .file-input-label:hover { background: #e2e8f0; }
        #imageInput { display: none; }

        .new-preview-label {
            font-size: 0.78rem; color: #64748b; margin-top: 8px;
        }
        #newPreviewImg {
            max-width: 100%; max-height: 200px; border-radius: 10px;
            margin-top: 10px; display: none;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }

        .form-divider {
            border: none; border-top: 1.5px solid #e2e8f0;
            margin: 28px 0;
        }
        .section-heading {
            font-size: 0.9rem; font-weight: 700; color: #2563eb;
            margin-bottom: 16px; text-transform: uppercase; letter-spacing: .05em;
        }

        .action-bar {
            display: flex; justify-content: flex-end; gap: 12px;
            margin-top: 32px; padding-top: 24px; border-top: 1.5px solid #e2e8f0;
        }
        .btn {
            display: inline-block; padding: 11px 26px; border-radius: 10px;
            font-size: 0.9rem; font-weight: 700; cursor: pointer;
            text-decoration: none; border: none; transition: opacity .2s, transform .1s;
        }
        .btn:hover { opacity: .88; transform: translateY(-1px); }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #475569; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .edit-card-body { padding: 20px; }
        }
    </style>
</head>

<body>
    <div class="topbar">
        <h1>Edit Campaign</h1>
        <a href="manager_dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
    </div>

    <div class="page-wrap">
        <div class="edit-card">
            <div class="edit-card-header">
                <h2>Edit: <?= htmlspecialchars($campaign['title']) ?></h2>
                <p>Ubah informasi campaign, termasuk gambar, nama, dan detail lainnya.</p>
            </div>
            <div class="edit-card-body">

                <?php if ($error): ?>
                    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">

                    <!-- Gambar Campaign -->
                    <div class="section-heading">Gambar Campaign</div>
                    <div class="image-section">
                        <div class="image-preview-wrap">
                            <?php if ($imgExists): ?>
                                <img id="currentImg" src="<?= htmlspecialchars($imgPath) ?>" alt="Gambar saat ini">
                            <?php else: ?>
                                <div class="no-preview">Tidak ada gambar tersedia</div>
                            <?php endif; ?>
                        </div>
                        <div class="image-label-text">
                            File saat ini: <strong><?= htmlspecialchars($campaign['image'] ?: '(tidak ada)') ?></strong>
                        </div>
                        <label class="file-input-label" for="imageInput">
                            Pilih Gambar Baru
                        </label>
                        <input type="file" id="imageInput" name="image" accept=".jpg,.jpeg,.png">
                        <div class="new-preview-label" id="newPreviewLabel" style="display:none">Preview gambar baru:</div>
                        <img id="newPreviewImg" src="" alt="Preview gambar baru">
                        <div style="font-size:0.75rem;color:#94a3b8;margin-top:8px;">Format: JPG, JPEG, PNG · Maks. 5MB</div>
                    </div>

                    <hr class="form-divider">

                    <!-- Info Dasar -->
                    <div class="section-heading">Informasi Campaign</div>
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label>Judul Campaign</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $campaign['title']) ?>" required placeholder="Masukkan judul campaign">
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <input type="text" name="category" value="<?= htmlspecialchars($_POST['category'] ?? $campaign['category']) ?>" required placeholder="Contoh: Bencana, Pendidikan...">
                        </div>
                        <div class="form-group">
                            <label>Lokasi</label>
                            <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? $campaign['location']) ?>" required placeholder="Contoh: Yogyakarta">
                        </div>
                        <div class="form-group form-full">
                            <label>Deskripsi</label>
                            <textarea name="description" required placeholder="Ceritakan tujuan campaign ini..."><?= htmlspecialchars($_POST['description'] ?? $campaign['description']) ?></textarea>
                        </div>
                    </div>

                    <hr class="form-divider">

                    <!-- Target & Waktu -->
                    <div class="section-heading">Target & Waktu</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Target Dana (Rp)</label>
                            <input type="number" name="target_amount" value="<?= htmlspecialchars($_POST['target_amount'] ?? $campaign['target_amount']) ?>" required min="1" placeholder="Contoh: 10000000">
                        </div>
                        <div class="form-group">
                            <label>Batas Waktu</label>
                            <input type="datetime-local" name="deadline"
                                value="<?= date('Y-m-d\TH:i', strtotime($_POST['deadline'] ?? $campaign['deadline'])) ?>"
                                required>
                        </div>
                        <div class="form-group form-full">
                            <label>Info Rekening / Pembayaran</label>
                            <input type="text" name="bank_info" value="<?= htmlspecialchars($_POST['bank_info'] ?? $campaign['bank_info']) ?>" required placeholder="Contoh: BCA 12345678 a.n. Nama">
                        </div>
                    </div>

                    <div class="action-bar">
                        <a href="manager_dashboard.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" name="update_campaign" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const imageInput = document.getElementById('imageInput');
        const newPreviewImg = document.getElementById('newPreviewImg');
        const newPreviewLabel = document.getElementById('newPreviewLabel');
        const currentImg = document.getElementById('currentImg');

        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                newPreviewImg.src = e.target.result;
                newPreviewImg.style.display = 'block';
                newPreviewLabel.style.display = 'block';
                // Dim the old image
                if (currentImg) currentImg.style.opacity = '0.4';
            };
            reader.readAsDataURL(file);
        });
    </script>
</body>

</html>
