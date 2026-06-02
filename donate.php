<?php
require 'config.php';

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

if (!$campaign) {
    die("Kampanye tidak ditemukan.");
}

$error = "";
$success = "";

if (isset($_POST['submit_donation'])) {
    $amount = (int)$_POST['amount'];
    $payment_method = trim($_POST['payment_method']);
    $message = trim($_POST['message']);

    if ($amount < 10000) {
        $error = "Nominal minimal donasi adalah Rp10.000.";
    } elseif (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Bukti transfer wajib diupload.";
    } else {
        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
        $fileName = $_FILES['proof_file']['name'];
        $fileTmp = $_FILES['proof_file']['tmp_name'];
        $fileSize = $_FILES['proof_file']['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            $error = "File harus JPG, JPEG, PNG, atau PDF.";
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $error = "Ukuran file maksimal 2MB.";
        } else {
            $newName = time() . "_" . rand(1000, 9999) . "." . $ext;
            $uploadPath = "uploads/" . $newName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $stmt = $conn->prepare("
                    INSERT INTO donations (campaign_id, donor_id, amount, payment_method, message, proof_file, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param(
                    "iiisss",
                    $campaign_id,
                    $_SESSION['user_id'],
                    $amount,
                    $payment_method,
                    $message,
                    $newName
                );

                if ($stmt->execute()) {
                    $success = "Donasi berhasil dikirim dan menunggu verifikasi.";
                } else {
                    $error = "Gagal menyimpan donasi.";
                }
            } else {
                $error = "Gagal upload file.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donasi</title>
    <link rel="stylesheet" href="assets/css/donate.css">
</head>

<body>
    <div class="container">
        <a href="detail.php?id=<?= $campaign['id'] ?>">← Kembali</a>

        <div class="donate-card">
            <h2>Donasi Sekarang</h2>
            <p><b>Kampanye:</b> <?= htmlspecialchars($campaign['title']) ?></p>
            <p><b>Target:</b> Rp<?= number_format($campaign['target_amount'], 0, ',', '.') ?></p>
            <p><b>Terkumpul:</b> Rp<?= number_format($campaign['collected_amount'], 0, ',', '.') ?></p>

            <p><b>Nama:</b> <?= htmlspecialchars($_SESSION['user_name']) ?></p>
            <p><b>Email:</b> <?= htmlspecialchars($_SESSION['user_email']) ?></p>

            <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
            <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>

            <form method="post" enctype="multipart/form-data" data-donate-form>
                <input type="number" name="amount" placeholder="Nominal Donasi" min="10000" required>
                <select name="payment_method" required>
                    <option value="">Pilih Metode Pembayaran</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="E-Wallet">E-Wallet</option>
                </select>
                <textarea name="message" placeholder="Pesan Dukungan (opsional)"></textarea>
                <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required>
                <button type="submit" name="submit_donation">Kirim Donasi</button>
            </form>
        </div>
    </div>
</body>
<script src="assets/js/script.js"></script>

</html>