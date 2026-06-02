<?php
require 'config.php';

// Validasi Login: Pastikan hanya donor yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. QUERY UNTUK MENGHITUNG RINGKASAN DONASI (FITUR BONUS)
$summaryStmt = $conn->prepare("
    SELECT 
        status,
        SUM(amount) AS total_amount,
        COUNT(*) AS total_donations
    FROM donations
    WHERE donor_id = ?
    GROUP BY status
");
$summaryStmt->bind_param("i", $user_id);
$summaryStmt->execute();
$summaryResult = $summaryStmt->get_result();

// Set nilai default awal 0 jika belum ada donasi
$summary = [
    'pending' => ['amount' => 0, 'count' => 0],
    'verified' => ['amount' => 0, 'count' => 0],
    'rejected' => ['amount' => 0, 'count' => 0],
];

// Memasukkan data dari database ke dalam array ringkasan
while ($row = $summaryResult->fetch_assoc()) {
    $summary[$row['status']] = [
        'amount' => (float)$row['total_amount'],
        'count' => (int)$row['total_donations']
    ];
}

// 2. QUERY UNTUK MENAMPILKAN DAFTAR RIWAYAT DONASI
$stmt = $conn->prepare("
    SELECT d.*, c.title AS campaign_title
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.donor_id = ?
    ORDER BY d.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$donations = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Donasi</title>
    <link rel="stylesheet" href="assets/css/history.css">
    <style>
        /* Tambahan style cepat agar ringkasan terlihat rapi berjajar */
        .summary-container {
            margin-bottom: 25px;
        }

        .summary-title {
            margin-bottom: 10px;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Riwayat Donasi Saya</h2>
        <a href="index.php" style="display:inline-block; margin-bottom: 20px; text-decoration: none;">← Kembali ke Beranda</a>

        <div class="summary-container">
            <h3 class="summary-title">Ringkasan Donasi:</h3>
            <div class="summary verified">
                Verified : Rp<?= number_format($summary['verified']['amount'], 0, ',', '.') ?> (<?= $summary['verified']['count'] ?> donasi)
            </div>
            <div class="summary pending">
                Pending : Rp<?= number_format($summary['pending']['amount'], 0, ',', '.') ?> (<?= $summary['pending']['count'] ?> donasi)
            </div>
            <div class="summary rejected">
                Ditolak : Rp<?= number_format($summary['rejected']['amount'], 0, ',', '.') ?> (<?= $summary['rejected']['count'] ?> donasi)
            </div>
        </div>
        <h3 class="summary-title">Daftar Transaksi:</h3>
        <?php if ($donations->num_rows > 0): ?>
            <?php while ($d = $donations->fetch_assoc()): ?>
                <div class="card status-<?= htmlspecialchars($d['status']) ?>">
                    <h3><?= htmlspecialchars($d['campaign_title']) ?></h3>
                    <p><b>Nominal:</b> Rp<?= number_format($d['amount'], 0, ',', '.') ?></p>
                    <p><b>Metode:</b> <?= htmlspecialchars($d['payment_method']) ?></p>
                    <p><b>Tanggal:</b> <?= htmlspecialchars($d['created_at']) ?></p>
                    <p><b>Status:</b> <span style="text-transform: uppercase; font-weight: bold;"><?= htmlspecialchars($d['status']) ?></span></p>
                    <?php if (!empty($d['message'])): ?>
                        <p><i>"<?= htmlspecialchars($d['message']) ?>"</i></p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Anda belum pernah melakukan donasi.</p>
        <?php endif; ?>
    </div>
</body>

</html>