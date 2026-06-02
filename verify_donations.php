<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: login.php");
    exit;
}

$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$manager_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND manager_id = ?");
$stmt->bind_param("ii", $campaign_id, $manager_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();

if (!$campaign) {
    die("Kampanye tidak ditemukan.");
}

if (isset($_POST['verify']) || isset($_POST['reject'])) {
    $donation_id = (int)$_POST['donation_id'];

    $stmt = $conn->prepare("SELECT * FROM donations WHERE id = ? AND campaign_id = ?");
    $stmt->bind_param("ii", $donation_id, $campaign_id);
    $stmt->execute();
    $donation = $stmt->get_result()->fetch_assoc();

    if ($donation) {
        if (isset($_POST['verify']) && $donation['status'] === 'pending') {
            $conn->begin_transaction();

            try {
                $upd = $conn->prepare("UPDATE donations SET status = 'verified' WHERE id = ?");
                $upd->bind_param("i", $donation_id);
                $upd->execute();

                $upd2 = $conn->prepare("UPDATE campaigns SET collected_amount = collected_amount + ? WHERE id = ?");
                $upd2->bind_param("di", $donation['amount'], $campaign_id);
                $upd2->execute();

                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                die("Gagal verifikasi.");
            }
        }

        if (isset($_POST['reject']) && $donation['status'] === 'pending') {
            $upd = $conn->prepare("UPDATE donations SET status = 'rejected' WHERE id = ?");
            $upd->bind_param("i", $donation_id);
            $upd->execute();
        }
    }
}

$stmt = $conn->prepare("
    SELECT d.*, u.name AS donor_name, u.email AS donor_email
    FROM donations d
    JOIN users u ON d.donor_id = u.id
    WHERE d.campaign_id = ?
    ORDER BY d.created_at DESC
");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$donations = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Donasi</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
    <div class="container">
        <a href="manager_dashboard.php">← Kembali</a>
        <h2>Donasi untuk: <?= htmlspecialchars($campaign['title']) ?></h2>

        <?php while ($d = $donations->fetch_assoc()): ?>
            <div class="card">
                <p><b>Nama Donatur:</b> <?= htmlspecialchars($d['donor_name']) ?></p>
                <p><b>Email:</b> <?= htmlspecialchars($d['donor_email']) ?></p>
                <p><b>Nominal:</b> Rp<?= number_format($d['amount'], 0, ',', '.') ?></p>
                <p><b>Status:</b> <?= htmlspecialchars($d['status']) ?></p>
                <p><b>Bukti:</b> <?= htmlspecialchars($d['proof_file']) ?></p>

                <?php if ($d['status'] === 'pending'): ?>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="donation_id" value="<?= $d['id'] ?>">
                        <button type="submit" name="verify">Verifikasi</button>
                        <button type="submit" name="reject">Tolak</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>

</html>