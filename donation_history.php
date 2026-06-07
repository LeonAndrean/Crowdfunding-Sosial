<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Summary query
$summaryStmt = $conn->prepare("
    SELECT status, SUM(amount) AS total_amount, COUNT(*) AS total_donations
    FROM donations WHERE donor_id = ? GROUP BY status
");
$summaryStmt->bind_param("i", $user_id);
$summaryStmt->execute();
$summaryResult = $summaryStmt->get_result();

$summary = [
    'pending'  => ['amount' => 0, 'count' => 0],
    'verified' => ['amount' => 0, 'count' => 0],
    'rejected' => ['amount' => 0, 'count' => 0],
];
while ($row = $summaryResult->fetch_assoc()) {
    $summary[$row['status']] = [
        'amount' => (float)$row['total_amount'],
        'count'  => (int)$row['total_donations']
    ];
}

$total_all = $summary['pending']['amount'] + $summary['verified']['amount'] + $summary['rejected']['amount'];
$total_count = $summary['pending']['count'] + $summary['verified']['count'] + $summary['rejected']['count'];

// Donations list
$stmt = $conn->prepare("
    SELECT d.*, c.title AS campaign_title
    FROM donations d JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.donor_id = ? ORDER BY d.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$donations = $stmt->get_result();
$all_donations = $donations->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Donasi</title>
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
            font-family: 'Lora', Georgia, serif;
            font-size: 1.2rem; font-weight: 600; color: #fff;
            letter-spacing: .01em;
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
        .navbar-links a.btn-nav-primary { background: #2563eb; color: #fff; margin-left: 4px; }
        .navbar-links a.btn-nav-primary:hover { background: #1d4ed8; }

        /* ── Container ── */
        .container {
            width: 92%; max-width: 1000px;
            margin: 0 auto;
            padding: 36px 0 60px;
        }

        /* ── Page heading ── */
        .page-head { margin-bottom: 28px; }
        .page-head h1 { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .page-head p { font-size: 0.85rem; color: #64748b; }

        /* ── Stats bar ── */
        .stats-bar { display: flex; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px 26px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            flex: 1; min-width: 150px;
            border-top: 3px solid #2563eb;
            transition: box-shadow .2s, transform .2s, background .2s;
            cursor: default;
        }
        .stat-card:hover { box-shadow: 0 8px 28px rgba(37,99,235,.18); transform: translateY(-3px); background: #eff6ff; }
        .stat-card.green { border-top-color: #16a34a; }
        .stat-card.green:hover { background: #f0fdf4; box-shadow: 0 8px 28px rgba(22,163,74,.18); }
        .stat-card.orange { border-top-color: #f59e0b; }
        .stat-card.orange:hover { background: #fffbeb; box-shadow: 0 8px 28px rgba(245,158,11,.18); }
        .stat-card.red { border-top-color: #dc2626; }
        .stat-card.red:hover { background: #fef2f2; box-shadow: 0 8px 28px rgba(220,38,38,.18); }
        .stat-label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
        .stat-value { font-size: 1.25rem; font-weight: 800; color: #1e293b; }
        .stat-sub { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }

        /* ── Section head ── */
        .section-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 18px; padding-bottom: 12px;
            border-bottom: 1.5px solid #e2e8f0;
        }
        .section-head h2 { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; }
        .section-head .s-count { font-size: 0.8rem; color: #94a3b8; }

        /* ── Donation card ── */
        .donation-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 14px;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform .25s cubic-bezier(.34,1.4,.64,1), box-shadow .25s ease, border-color .2s;
            border-left: 4px solid #e2e8f0;
        }
        .donation-card:hover {
            transform: translateY(-3px) scale(1.003);
            box-shadow: 0 12px 32px rgba(15,23,42,.1), 0 4px 12px rgba(37,99,235,.06);
            border-color: rgba(37,99,235,.2);
        }
        .donation-card.status-verified { border-left-color: #16a34a; }
        .donation-card.status-pending  { border-left-color: #f59e0b; }
        .donation-card.status-rejected { border-left-color: #dc2626; }

        .card-top { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; }
        .card-title { font-size: 1rem; font-weight: 700; color: #1e293b; }

        .status-badge {
            font-size: 0.68rem; font-weight: 700; padding: 3px 10px;
            border-radius: 99px; text-transform: uppercase; letter-spacing: .04em;
            white-space: nowrap;
        }
        .status-badge.verified { background: #dcfce7; color: #15803d; }
        .status-badge.pending  { background: #fef9c3; color: #92400e; }
        .status-badge.rejected { background: #fee2e2; color: #b91c1c; }

        .card-meta { display: flex; flex-wrap: wrap; gap: 4px 20px; }
        .card-meta span { font-size: 0.79rem; color: #64748b; }
        .card-meta span strong { color: #334155; font-weight: 700; }

        .card-amount {
            font-size: 1.15rem; font-weight: 800; color: #2563eb;
        }
        .card-message {
            font-size: 0.8rem; color: #64748b;
            background: #f8fafc; border-radius: 8px;
            padding: 8px 12px; border-left: 3px solid #cbd5e1;
            font-style: italic;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-block; padding: 8px 18px; border-radius: 9px;
            text-decoration: none; color: #fff; font-size: 0.82rem; font-weight: 700;
            border: none; cursor: pointer; font-family: inherit;
            transition: opacity .18s, transform .15s, box-shadow .18s;
            white-space: nowrap;
        }
        .btn:hover { opacity: .88; transform: translateY(-1px); }
        .btn-primary { background: #2563eb; }
        .btn-ghost {
            background: #f1f5f9; color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        .btn-ghost:hover { background: #e2e8f0; opacity: 1; }
        .btn-summary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
        }
        .btn-summary:hover { opacity: .88; }

        /* ── Top actions row ── */
        .actions-row {
            display: flex; gap: 10px; flex-wrap: wrap;
            margin-bottom: 28px;
        }

        /* ── Empty ── */
        .empty-state {
            text-align: center; padding: 72px 20px; color: #94a3b8;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
        }
        .empty-state p { font-size: 0.9rem; margin-bottom: 16px; }

        /* ── Footer ── */
        .dash-footer {
            margin-top: 48px; padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.78rem; color: #cbd5e1;
            text-align: center;
        }

        @media (max-width: 680px) {
            .navbar { padding: 0 16px; }
            .stats-bar { flex-direction: column; }
            .card-top { flex-direction: column; }
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
        <a href="index.php">Beranda</a>
        <a href="pengaturan_akun.php">Akun</a>
        <a href="logout.php" class="btn-nav-danger">Logout</a>
    </div>
</nav>

<div class="container">

    <div class="page-head">
        <h1>Riwayat Donasi Saya</h1>
        <p>Pantau semua donasi yang telah Anda lakukan</p>
    </div>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-label">Total Donasi</div>
            <div class="stat-value"><?= $total_count ?></div>
            <div class="stat-sub">transaksi</div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Terverifikasi</div>
            <div class="stat-value">Rp<?= number_format($summary['verified']['amount'], 0, ',', '.') ?></div>
            <div class="stat-sub"><?= $summary['verified']['count'] ?> donasi</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label">Menunggu</div>
            <div class="stat-value">Rp<?= number_format($summary['pending']['amount'], 0, ',', '.') ?></div>
            <div class="stat-sub"><?= $summary['pending']['count'] ?> donasi</div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Ditolak</div>
            <div class="stat-value">Rp<?= number_format($summary['rejected']['amount'], 0, ',', '.') ?></div>
            <div class="stat-sub"><?= $summary['rejected']['count'] ?> donasi</div>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="actions-row">
        <a href="summary.php" class="btn btn-summary">Ringkasan Donasi</a>
    </div>

    <!-- Section head -->
    <div class="section-head">
        <h2>Daftar Transaksi</h2>
        <span class="s-count"><?= $total_count ?> transaksi</span>
    </div>

    <?php if (empty($all_donations)): ?>
        <div class="empty-state">
            <p>Anda belum pernah melakukan donasi.</p>
            <a href="index.php" class="btn btn-primary">Lihat Campaign</a>
        </div>
    <?php else: ?>
        <?php foreach ($all_donations as $d): ?>
        <div class="donation-card status-<?= htmlspecialchars($d['status']) ?>">
            <div class="card-top">
                <div class="card-title"><?= htmlspecialchars($d['campaign_title']) ?></div>
                <span class="status-badge <?= htmlspecialchars($d['status']) ?>">
                    <?= $d['status'] === 'verified' ? 'Terverifikasi' : ($d['status'] === 'pending' ? 'Menunggu' : 'Ditolak') ?>
                </span>
            </div>
            <div class="card-amount">Rp<?= number_format($d['amount'], 0, ',', '.') ?></div>
            <div class="card-meta">
                <span><strong>Metode:</strong> <?= htmlspecialchars($d['payment_method']) ?></span>
                <span><strong>Tanggal:</strong> <?= date('d M Y, H:i', strtotime($d['created_at'])) ?></span>
            </div>
            <?php if (!empty($d['message'])): ?>
                <div class="card-message">"<?= htmlspecialchars($d['message']) ?>"</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="margin-top:28px;">
        <a href="javascript:history.back()" class="btn btn-ghost">Kembali</a>
    </div>

    <div class="dash-footer">Copyright &copy; 2026 Berbagi Donasi Social. All Rights Reserved</div>

</div>
</body>
</html>