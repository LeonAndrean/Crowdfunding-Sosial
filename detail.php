<?php
require 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT c.*, u.name AS manager_name
    FROM campaigns c
    JOIN users u ON c.manager_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();

if (!$campaign) {
    die("Kampanye tidak ditemukan.");
}

$progress = $campaign['target_amount'] > 0
    ? min(100, ($campaign['collected_amount'] / $campaign['target_amount']) * 100)
    : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kampanye</title>
    <link rel="stylesheet" href="assets/css/detail.css">
</head>

<body>
    <div class="container">
        <a href="index.php">← Kembali</a>
        <div class="detail-card">
            <img src="uploads/<?= htmlspecialchars($campaign['image']) ?>" alt="Campaign">
            <h2><?= htmlspecialchars($campaign['title']) ?></h2>
            <p><b>Pengelola:</b> <?= htmlspecialchars($campaign['manager_name']) ?></p>
            <p><b>Lokasi:</b> <?= htmlspecialchars($campaign['location']) ?></p>
            <p><b>Kategori:</b> <?= htmlspecialchars($campaign['category']) ?></p>
            <p><b>Deskripsi:</b> <?= htmlspecialchars($campaign['description']) ?></p>
            <p><b>Target Dana:</b> Rp<?= number_format($campaign['target_amount'], 0, ',', '.') ?></p>
            <p><b>Dana Terkumpul:</b> Rp<?= number_format($campaign['collected_amount'], 0, ',', '.') ?></p>
            <p><b>Batas Waktu:</b> <?= htmlspecialchars($campaign['deadline']) ?></p>
            <p><b>Rekening:</b> <?= htmlspecialchars($campaign['bank_info']) ?></p>

            <div class="progress-wrap">
                <div class="progress-bar" style="width: <?= $progress ?>%"><?= number_format($progress, 0) ?>%</div>
            </div>

            <a class="btn" href="donate.php?campaign_id=<?= $campaign['id'] ?>">Donasi Sekarang</a>
        </div>
    </div>
</body>

</html>