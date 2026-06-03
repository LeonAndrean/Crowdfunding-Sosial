<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT status, SUM(amount) AS total_amount, COUNT(*) AS total_donations
    FROM donations WHERE donor_id = ? GROUP BY status
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$summary = [
    'pending'  => ['amount' => 0, 'count' => 0],
    'verified' => ['amount' => 0, 'count' => 0],
    'rejected' => ['amount' => 0, 'count' => 0],
];
while ($row = $result->fetch_assoc()) {
    $summary[$row['status']] = [
        'amount' => (float)$row['total_amount'],
        'count'  => (int)$row['total_donations']
    ];
}

$total_amount = $summary['verified']['amount'] + $summary['pending']['amount'] + $summary['rejected']['amount'];
$total_count  = $summary['verified']['count'] + $summary['pending']['count'] + $summary['rejected']['count'];

// Pie chart percentages
function pct(float $part, float $total): float {
    return $total > 0 ? round(($part / $total) * 100, 1) : 0;
}

// Top campaigns
$topStmt = $conn->prepare("
    SELECT c.title, SUM(d.amount) AS total, COUNT(*) AS cnt
    FROM donations d JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.donor_id = ? AND d.status = 'verified'
    GROUP BY c.id ORDER BY total DESC LIMIT 5
");
$topStmt->bind_param("i", $user_id);
$topStmt->execute();
$topCampaigns = $topStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Donasi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@1,500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0f4f8; color: #1e293b; min-height: 100vh;
        }

        /* ── Navbar ── */
        .navbar {
            background: #0f172a; color: #fff; padding: 0 40px; height: 60px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .navbar-brand {
            font-family: 'Lora', Georgia, serif; font-size: 1.2rem; font-weight: 600;
            color: #fff; letter-spacing: .01em;
            display: flex; align-items: center; gap: 10px; text-decoration: none;
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
        .container { width: 92%; max-width: 1000px; margin: 0 auto; padding: 36px 0 60px; }

        .page-head { margin-bottom: 28px; }
        .page-head h1 { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .page-head p { font-size: 0.85rem; color: #64748b; }

        /* ── Total hero card ── */
        .total-card {
            background: linear-gradient(135deg, #1e40af, #2563eb, #0284c7);
            border-radius: 20px; padding: 32px 36px; margin-bottom: 24px;
            color: #fff; box-shadow: 0 12px 40px rgba(37,99,235,.3);
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px;
        }
        .total-card .tc-left .tc-label { font-size: 0.8rem; font-weight: 600; opacity: .7; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 6px; }
        .total-card .tc-left .tc-val { font-size: 2rem; font-weight: 800; line-height: 1; }
        .total-card .tc-left .tc-sub { font-size: 0.82rem; opacity: .65; margin-top: 4px; }
        .total-card .tc-right { font-size: 3.5rem; opacity: .15; font-weight: 900; }

        /* ── Stats grid ── */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-block {
            background: #fff; border-radius: 14px; padding: 22px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            border-top: 3px solid #e2e8f0;
            transition: transform .2s, box-shadow .2s;
            cursor: default;
        }
        .stat-block:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,.1); }
        .stat-block.green { border-top-color: #16a34a; }
        .stat-block.green:hover { background: #f0fdf4; }
        .stat-block.orange { border-top-color: #f59e0b; }
        .stat-block.orange:hover { background: #fffbeb; }
        .stat-block.red { border-top-color: #dc2626; }
        .stat-block.red:hover { background: #fef2f2; }

        .sb-label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
        .sb-val { font-size: 1.2rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .sb-count { font-size: 0.75rem; color: #94a3b8; }

        .sb-bar-bg { background: #e2e8f0; border-radius: 99px; height: 5px; margin-top: 10px; overflow: hidden; }
        .sb-bar-fill { height: 100%; border-radius: 99px; transition: width .6s ease; }
        .green .sb-bar-fill { background: #16a34a; }
        .orange .sb-bar-fill { background: #f59e0b; }
        .red .sb-bar-fill { background: #dc2626; }

        /* ── Section head ── */
        .section-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px; padding-bottom: 10px;
            border-bottom: 1.5px solid #e2e8f0;
        }
        .section-head h2 { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; }

        /* ── Top campaigns ── */
        .campaign-row {
            background: #fff; border-radius: 12px; padding: 14px 18px;
            margin-bottom: 10px; display: flex; align-items: center; gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            border: 1px solid #f1f5f9;
            transition: transform .2s, box-shadow .2s;
        }
        .campaign-row:hover { transform: translateX(4px); box-shadow: 0 6px 20px rgba(37,99,235,.1); }
        .cr-rank {
            width: 28px; height: 28px; border-radius: 99px;
            background: #eff6ff; color: #2563eb;
            font-size: 0.8rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .cr-rank.r1 { background: #fef9c3; color: #92400e; }
        .cr-rank.r2 { background: #f3f4f6; color: #6b7280; }
        .cr-rank.r3 { background: #fdf2f8; color: #a21caf; }
        .cr-name { flex: 1; font-size: 0.88rem; font-weight: 600; color: #1e293b; }
        .cr-cnt { font-size: 0.75rem; color: #94a3b8; margin-top: 1px; }
        .cr-amount { font-size: 0.95rem; font-weight: 800; color: #16a34a; white-space: nowrap; }

        /* ── Button ── */
        .btn {
            display: inline-block; padding: 9px 20px; border-radius: 9px;
            text-decoration: none; font-size: 0.82rem; font-weight: 700;
            border: none; cursor: pointer; font-family: inherit;
            transition: opacity .18s, transform .15s;
            white-space: nowrap;
        }
        .btn:hover { opacity: .88; transform: translateY(-1px); }
        .btn-ghost { background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0; }
        .btn-ghost:hover { background: #e2e8f0; opacity: 1; }

        .actions-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }

        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 0.88rem; }

        .dash-footer {
            margin-top: 48px; padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.78rem; color: #cbd5e1; text-align: center;
        }

        @media (max-width: 680px) {
            .navbar { padding: 0 16px; }
            .stats-grid { grid-template-columns: 1fr; }
            .total-card .tc-right { display: none; }
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
        <h1>Ringkasan Donasi</h1>
        <p>Statistik lengkap semua donasi Anda</p>
    </div>

    <!-- Total Hero -->
    <div class="total-card">
        <div class="tc-left">
            <div class="tc-label">Total Donasi Anda</div>
            <div class="tc-val">Rp<?= number_format($total_amount, 0, ',', '.') ?></div>
            <div class="tc-sub"><?= $total_count ?> transaksi dari seluruh campaign</div>
        </div>
        <div class="tc-right">💙</div>
    </div>

    <!-- Stats grid -->
    <div class="stats-grid">
        <div class="stat-block green">
            <div class="sb-label">Terverifikasi</div>
            <div class="sb-val">Rp<?= number_format($summary['verified']['amount'], 0, ',', '.') ?></div>
            <div class="sb-count"><?= $summary['verified']['count'] ?> donasi</div>
            <div class="sb-bar-bg">
                <div class="sb-bar-fill" style="width:<?= pct($summary['verified']['amount'], $total_amount) ?>%"></div>
            </div>
        </div>
        <div class="stat-block orange">
            <div class="sb-label">Menunggu</div>
            <div class="sb-val">Rp<?= number_format($summary['pending']['amount'], 0, ',', '.') ?></div>
            <div class="sb-count"><?= $summary['pending']['count'] ?> donasi</div>
            <div class="sb-bar-bg">
                <div class="sb-bar-fill" style="width:<?= pct($summary['pending']['amount'], $total_amount) ?>%"></div>
            </div>
        </div>
        <div class="stat-block red">
            <div class="sb-label">Ditolak</div>
            <div class="sb-val">Rp<?= number_format($summary['rejected']['amount'], 0, ',', '.') ?></div>
            <div class="sb-count"><?= $summary['rejected']['count'] ?> donasi</div>
            <div class="sb-bar-bg">
                <div class="sb-bar-fill" style="width:<?= pct($summary['rejected']['amount'], $total_amount) ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="actions-row">
        <a href="donation_history.php" class="btn btn-ghost">← Kembali ke Riwayat</a>
    </div>

    <!-- Top Campaigns -->
    <div class="section-head">
        <h2>Campaign Terdukung (Terverifikasi)</h2>
    </div>

    <?php if (empty($topCampaigns)): ?>
        <div class="empty-state">Belum ada donasi yang terverifikasi.</div>
    <?php else: ?>
        <?php foreach ($topCampaigns as $i => $tc): ?>
        <div class="campaign-row">
            <div class="cr-rank r<?= $i + 1 ?>"><?= $i + 1 ?></div>
            <div>
                <div class="cr-name"><?= htmlspecialchars($tc['title']) ?></div>
                <div class="cr-cnt"><?= $tc['cnt'] ?> kali donasi</div>
            </div>
            <div class="cr-amount">Rp<?= number_format($tc['total'], 0, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="dash-footer">Copyright &copy; 2026 Berbagi Donasi Social. All Rights Reserved</div>

</div>
</body>
</html>