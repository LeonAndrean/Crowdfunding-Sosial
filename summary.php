<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        status,
        SUM(amount) AS total_amount,
        COUNT(*) AS total_donations
    FROM donations
    WHERE donor_id = ?
    GROUP BY status
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$summary = [
    'pending' => ['amount' => 0, 'count' => 0],
    'verified' => ['amount' => 0, 'count' => 0],
    'rejected' => ['amount' => 0, 'count' => 0],
];

while ($row = $result->fetch_assoc()) {
    $summary[$row['status']] = [
        'amount' => (float)$row['total_amount'],
        'count' => (int)$row['total_donations']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary Donasi</title>
    <link rel="stylesheet" href="assets/css/history.css">
</head>

<body>
    <div class="container">
        <h2>Ringkasan Donasi</h2>
        <a href="donation_history.php">← Riwayat Donasi</a>

        <div class="summary verified">
            Verified : Rp<?= number_format($summary['verified']['amount'], 0, ',', '.') ?>
            (<?= $summary['verified']['count'] ?> donasi)
        </div>

        <div class="summary pending">
            Pending : Rp<?= number_format($summary['pending']['amount'], 0, ',', '.') ?>
            (<?= $summary['pending']['count'] ?> donasi)
        </div>

        <div class="summary rejected">
            Ditolak : Rp<?= number_format($summary['rejected']['amount'], 0, ',', '.') ?>
            (<?= $summary['rejected']['count'] ?> donasi)
        </div>
    </div>
</body>

</html>