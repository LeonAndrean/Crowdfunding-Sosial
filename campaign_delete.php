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
    die("Campaign tidak ditemukan.");
}

if ($campaign['collected_amount'] >= 10000) {
    die("Campaign tidak bisa dihapus karena sudah memiliki dana terkumpul lebih dari Rp.10.000.");
}

$stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ? AND manager_id = ?");
$stmt->bind_param("ii", $id, $manager_id);
$stmt->execute();

header("Location: manager_dashboard.php");
exit;
