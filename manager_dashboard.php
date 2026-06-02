<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: login.php");
    exit;
}

$manager_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.*, 
           COALESCE((SELECT SUM(amount) FROM donations WHERE campaign_id = c.id AND status = 'pending'), 0) AS pending_amount,
           COALESCE((SELECT SUM(amount) FROM donations WHERE campaign_id = c.id AND status = 'verified'), 0) AS verified_amount,
           (SELECT COUNT(*) FROM donations WHERE campaign_id = c.id) AS total_donatur
    FROM campaigns c 
    WHERE c.manager_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$campaigns = $stmt->get_result();
$all_campaigns = $campaigns->fetch_all(MYSQLI_ASSOC);
$total_campaigns = count($all_campaigns);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengelola</title>
    <link rel="stylesheet" href="assets/css/campaign.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; margin: 0; color: #1e293b; }

        .container { width: 92%; max-width: 1200px; margin: 0 auto; padding: 32px 0 60px; }

        /* Top bar */
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            background: #fff; padding: 18px 28px; border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 24px;
        }
        .topbar-title { margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b; }
        .topbar-subtitle { font-size: 0.82rem; color: #64748b; margin-top: 2px; }
        .topbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* Stats */
        
        .stats-bar { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 18px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05); flex: 1; min-width: 160px;
            border-left: 4px solid #2563eb;
        }
        .stat-card.green { border-left-color: #16a34a; }
        .stat-card.orange { border-left-color: #f59e0b; }
        .stat-label { font-size: 0.76rem; color: #64748b; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
        .stat-value { font-size: 1.3rem; font-weight: 700; color: #1e293b; }

        /* Section title */
        .section-title {
            font-size: 0.95rem; font-weight: 700; color: #64748b;
            margin-bottom: 16px; padding-bottom: 8px;
            border-bottom: 1.5px solid #e2e8f0;
            text-transform: uppercase; letter-spacing: .05em;
        }

        /* Campaign card */
        .campaign-card {
            background: #fff; border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            margin-bottom: 18px; overflow: hidden; display: flex;
            transition: box-shadow .2s;
        }
        .campaign-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.11); }
        .campaign-img-wrap { width: 210px; min-width: 210px; position: relative; overflow: hidden; }
        .campaign-img-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; min-height: 175px; }
        .campaign-img-wrap .no-image {
            width: 100%; height: 100%; min-height: 175px; background: #e2e8f0;
            display: flex; align-items: center; justify-content: center;
            color: #94a3b8; font-size: 0.82rem;
        }

        .campaign-body { flex: 1; padding: 20px 24px; display: flex; flex-direction: column; }
        .campaign-body h3 { margin: 0 0 8px; font-size: 1.08rem; color: #1e293b; }
        .campaign-meta {
            display: flex; flex-wrap: wrap; gap: 4px 18px;
            margin-bottom: 14px;
        }
        .campaign-meta span { font-size: 0.8rem; color: #64748b; }
        .campaign-meta span strong { color: #334155; }

        .progress-wrap { margin-bottom: 14px; }
        .progress-bar-bg { background: #e2e8f0; border-radius: 99px; height: 7px; overflow: hidden; }
        .progress-bar-fill {
            background: linear-gradient(90deg, #2563eb, #38bdf8);
            height: 100%; border-radius: 99px; transition: width .4s;
        }
        .progress-label {
            font-size: 0.76rem; color: #64748b; margin-top: 4px;
            display: flex; justify-content: space-between;
        }

        .money-grid { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .money-item { background: #f8fafc; border-radius: 8px; padding: 8px 14px; font-size: 0.8rem; }
        .money-item .mlabel { color: #94a3b8; font-size: 0.72rem; margin-bottom: 2px; }
        .money-item .mval { font-weight: 700; color: #1e293b; }
        .money-item.verified .mval { color: #16a34a; }
        .money-item.pending .mval { color: #f59e0b; }

        .campaign-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto; }

        /* Buttons */
        .btn {
            display: inline-block; padding: 8px 16px; border-radius: 8px;
            text-decoration: none; color: #fff; font-size: 0.82rem;
            font-weight: 600; transition: opacity .2s, transform .1s;
            border: none; cursor: pointer; white-space: nowrap;
        }
        .btn:hover { opacity: .88; transform: translateY(-1px); }
        .btn-primary { background: #2563eb; }
        .btn-warning { background: #f59e0b; color: #fff; }
        .btn-success { background: #16a34a; }
        .btn-danger { background: #dc2626; }
        .btn-outline {
            background: transparent; color: #475569;
            border: 1.5px solid #cbd5e1;
        }
        .btn-outline:hover { background: #f8fafc; opacity: 1; }

        /* Badge */
        .badge {
            display: inline-block; font-size: 0.7rem; padding: 2px 8px;
            border-radius: 99px; font-weight: 600; margin-left: 8px; vertical-align: middle;
        }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-expired { background: #fee2e2; color: #dc2626; }

        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state p { font-size: 1rem; margin-bottom: 16px; }

        /* Footer */
        .dashboard-footer {
            margin-top: 40px; padding-top: 20px;
            border-top: 1.5px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 10px;
        }
        .dashboard-footer .footer-links { display: flex; gap: 16px; flex-wrap: wrap; }
        .dashboard-footer a { font-size: 0.8rem; color: #94a3b8; text-decoration: none; }
        .dashboard-footer a:hover { color: #2563eb; }
        .dashboard-footer .copy { font-size: 0.78rem; color: #cbd5e1; }

        @media (max-width: 680px) {
            .campaign-card { flex-direction: column; }
            .campaign-img-wrap { width: 100%; min-width: unset; }
            .campaign-img-wrap img { min-height: 160px; max-height: 200px; }
            .stats-bar { flex-direction: column; }
            .topbar { flex-direction: column; gap: 12px; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Top bar -->
    <div class="topbar">
        <div>
            <div class="topbar-title">Dashboard Pengelola</div>
            <div class="topbar-subtitle">Kelola campaign dan pantau donasi Anda</div>
        </div>
        <div class="topbar-actions">
            <a href="index.php" class="btn btn-outline">Kembali</a>
            <a href="campaign_add.php" class="btn btn-primary">+ Tambah Campaign</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-label">Total Campaign</div>
            <div class="stat-value"><?= $total_campaigns ?></div>
        </div>
        <?php
        $total_target    = array_sum(array_column($all_campaigns, 'target_amount'));
        $total_collected = array_sum(array_column($all_campaigns, 'collected_amount'));
        $total_pending   = array_sum(array_column($all_campaigns, 'pending_amount'));
        ?>
        <div class="stat-card green">
            <div class="stat-label">Total Terkumpul</div>
            <div class="stat-value">Rp<?= number_format($total_collected, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label">Dana Pending</div>
            <div class="stat-value">Rp<?= number_format($total_pending, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="section-title">Daftar Campaign (<?= $total_campaigns ?>)</div>

    <?php if (empty($all_campaigns)): ?>
        <div class="empty-state">
            <p>Belum ada campaign. Mulai buat campaign pertama Anda!</p>
            <a href="campaign_add.php" class="btn btn-primary">+ Buat Campaign</a>
        </div>
    <?php else: ?>
        <?php foreach ($all_campaigns as $c): ?>
            <?php
            $pct = ($c['target_amount'] > 0) ? min(100, round(($c['collected_amount'] / $c['target_amount']) * 100)) : 0;
            $is_expired = strtotime($c['deadline']) < time();
            ?>
            <div class="campaign-card">
                <div class="campaign-img-wrap">
                    <?php
                    $imgFile   = 'uploads/' . $c['image'];
                    $imgExists = !empty($c['image']) && file_exists($imgFile);
                    ?>
                    <?php if ($imgExists): ?>
                        <img src="<?= htmlspecialchars($imgFile) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
                    <?php else: ?>
                        <div class="no-image">Tidak ada gambar</div>
                    <?php endif; ?>
                </div>
                <div class="campaign-body">
                    <h3>
                        <?= htmlspecialchars($c['title']) ?>
                        <span class="badge <?= $is_expired ? 'badge-expired' : 'badge-active' ?>">
                            <?= $is_expired ? 'Berakhir' : 'Aktif' ?>
                        </span>
                    </h3>
                    <div class="campaign-meta">
                        <span><strong><?= htmlspecialchars($c['category']) ?></strong></span>
                        <span><?= htmlspecialchars($c['location']) ?></span>
                        <span>Deadline: <?= date('d M Y', strtotime($c['deadline'])) ?></span>
                        <span><?= $c['total_donatur'] ?> Donatur</span>
                    </div>

                    <div class="progress-wrap">
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="progress-label">
                            <span><?= $pct ?>% tercapai</span>
                            <span>Target: Rp<?= number_format($c['target_amount'], 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="money-grid">
                        <div class="money-item verified">
                            <div class="mlabel">Terkumpul</div>
                            <div class="mval">Rp<?= number_format($c['collected_amount'], 0, ',', '.') ?></div>
                        </div>
                        <div class="money-item pending">
                            <div class="mlabel">Pending</div>
                            <div class="mval">Rp<?= number_format($c['pending_amount'], 0, ',', '.') ?></div>
                        </div>
                    </div>

                    <div class="campaign-actions">
                        <a href="campaign_edit.php?id=<?= $c['id'] ?>" class="btn btn-warning">Edit Campaign</a>
                        <a href="verify_donations.php?campaign_id=<?= $c['id'] ?>" class="btn btn-success">Lihat Donasi</a>
                        <a href="campaign_delete.php?id=<?= $c['id'] ?>" class="btn btn-danger"
                           onclick="return confirm('Yakin ingin menghapus campaign «<?= htmlspecialchars(addslashes($c['title'])) ?>»?')">Hapus</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Footer -->
    <div class="dashboard-footer">

        <div class="copy">Copyright &copy; 2026 Kitabisa. All Rights Reserved</div>
    </div>

</div>
</body>
</html>