<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: login.php");
    exit;
}

$error = "";

if (isset($_POST['save_campaign'])) {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $target_amount = (float)$_POST['target_amount'];
    $deadline = $_POST['deadline'];
    $bank_info = trim($_POST['bank_info']);

    if ($title == "" || $category == "" || $location == "" || $description == "" || $target_amount <= 0 || $deadline == "" || $bank_info == "") {
        $error = "Semua field wajib diisi.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Gambar campaign wajib diupload.";
    } else {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = "Gambar harus JPG, JPEG, atau PNG.";
        } else {
            $fileName = time() . "_" . rand(1000, 9999) . "." . $ext;
            $uploadPath = "uploads/" . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $stmt = $conn->prepare("
                    INSERT INTO campaigns 
                    (manager_id, title, category, location, description, target_amount, collected_amount, deadline, image, bank_info)
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
                ");
                $stmt->bind_param("issssisss", $manager_id, $title, $category, $location, $description, $target_amount, $deadline, $fileName, $bank_info);

                $manager_id = $_SESSION['user_id'];

                if ($stmt->execute()) {
                    header("Location: manager_dashboard.php");
                    exit;
                } else {
                    $error = "Gagal menyimpan campaign.";
                }
            } else {
                $error = "Gagal upload gambar.";
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
    <title>Tambah Campaign</title>
    <link rel="stylesheet" href="assets/css/campaign.css">
</head>

<body>
    <div class="container">
        <h2>Tambah Campaign</h2>
        <a href="manager_dashboard.php">← Kembali</a>

        <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Judul Campaign" required>
            <input type="text" name="category" placeholder="Kategori" required>
            <input type="text" name="location" placeholder="Lokasi" required>
            <textarea name="description" placeholder="Deskripsi" required></textarea>
            <input type="number" name="target_amount" placeholder="Target Dana" required>
            <label>Batas Waktu Kampanye (Tanggal & Jam):</label>
            <input type="datetime-local" name="deadline" required>
            <input type="text" name="bank_info" placeholder="Informasi Rekening" required>
            <input type="file" name="image" accept=".jpg,.jpeg,.png" required>
            <button type="submit" name="save_campaign" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</body>

</html>