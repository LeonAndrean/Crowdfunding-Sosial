<?php
require 'config.php';

function progColor(int $pct): array {
    if ($pct <= 50)  return ['grad' => 'linear-gradient(90deg,#2563eb,#38bdf8)', 'fire' => false];
    if ($pct <= 75)  return ['grad' => 'linear-gradient(90deg,#d97706,#fbbf24)', 'fire' => false];
    return               ['grad' => 'linear-gradient(90deg,#dc2626,#f97316)', 'fire' => true];
}

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

        /* ── Navbar (sama persis index) ── */
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
            font-size: 1.2rem;
            font-weight: 600;
            color: #fff;
            letter-spacing: .01em;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .navbar-links a.btn-nav-danger {
            background: #dc2626; color: #fff; margin-left: 4px;
        }
        .navbar-links a.btn-nav-danger:hover { background: #b91c1c; }
        .navbar-links a.btn-nav-primary {
            background: #2563eb; color: #fff; margin-left: 4px;
        }
        .navbar-links a.btn-nav-primary:hover { background: #1d4ed8; }

        /* ── Container ── */
        .container {
            width: 92%; max-width: 1200px;
            margin: 0 auto;
            padding: 36px 0 60px;
        }

        /* ── Page heading ── */
        .page-head {
            margin-bottom: 28px;
        }
        .page-head h1 {
            font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 4px;
        }
        .page-head p { font-size: 0.85rem; color: #64748b; }

        /* ── Stats ── */
        .stats-bar { display: flex; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px 26px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            flex: 1; min-width: 160px;
            border-top: 3px solid #2563eb;
            cursor: default;
            transition: background .2s, box-shadow .2s, transform .2s;
        }
        .stat-card:hover {
            box-shadow: 0 8px 28px rgba(37,99,235,.18);
            transform: translateY(-3px);
            background: #eff6ff;
        }
        .stat-card.green { border-top-color: #16a34a; }
        .stat-card.green:hover {
            background: #f0fdf4;
            box-shadow: 0 8px 28px rgba(22,163,74,.18);
        }
        .stat-card.orange { border-top-color: #f59e0b; }
        .stat-card.orange:hover {
            background: #fffbeb;
            box-shadow: 0 8px 28px rgba(245,158,11,.18);
        }
        .stat-label {
            font-size: 0.72rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;
        }
        .stat-value { font-size: 1.35rem; font-weight: 800; color: #1e293b; }

        /* ── Section heading ── */
        .section-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 18px; padding-bottom: 12px;
            border-bottom: 1.5px solid #e2e8f0;
        }
        .section-head h2 {
            font-size: 0.85rem; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .section-head .s-count { font-size: 0.8rem; color: #94a3b8; }

        /* ── Campaign card ── */
        .campaign-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 18px;
            overflow: hidden;
            display: flex;
            transition: transform .25s cubic-bezier(.34,1.4,.64,1), box-shadow .25s ease, border-color .2s;
        }
        .campaign-card:hover {
            transform: translateY(-4px) scale(1.005);
            box-shadow: 0 16px 40px rgba(15,23,42,.12), 0 4px 16px rgba(37,99,235,.08);
            border-color: rgba(37,99,235,.15);
        }

        .campaign-img-wrap {
            width: 220px; min-width: 220px;
            overflow: hidden; position: relative;
        }
        .campaign-img-wrap img {
            width: 100%; height: 100%; object-fit: cover; display: block;
            min-height: 180px;
            transition: transform .4s ease;
        }
        .campaign-card:hover .campaign-img-wrap img { transform: scale(1.05); }
        .campaign-img-wrap .no-image {
            width: 100%; height: 100%; min-height: 180px;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex; align-items: center; justify-content: center;
            color: #94a3b8; font-size: 0.82rem;
        }

        .campaign-body { flex: 1; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px; }

        .title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .title-row h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; }

        .badge {
            font-size: 0.68rem; font-weight: 700; padding: 2px 9px;
            border-radius: 99px; text-transform: uppercase; letter-spacing: .04em;
        }
        .badge-active  { background: #dcfce7; color: #15803d; }
        .badge-expired { background: #fee2e2; color: #b91c1c; }

        .campaign-meta { display: flex; flex-wrap: wrap; gap: 4px 18px; }
        .campaign-meta span { font-size: 0.79rem; color: #64748b; }
        .campaign-meta span strong { color: #334155; font-weight: 600; }

        .progress-wrap {}
        .progress-bar-bg { background: #e2e8f0; border-radius: 99px; height: 7px; overflow: hidden; }
        .progress-bar-fill {
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            height: 100%; border-radius: 99px; transition: width .4s;
        }
        .progress-label {
            display: flex; justify-content: space-between;
            font-size: 0.73rem; color: #64748b; margin-top: 4px;
        }
        .progress-label .pct { color: #2563eb; font-weight: 700; }

        .money-grid { display: flex; gap: 10px; flex-wrap: wrap; }
        .money-item { background: #f8fafc; border-radius: 8px; padding: 8px 14px; }
        .money-item .mlabel { font-size: 0.68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
        .money-item .mval { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
        .money-item.verified .mval { color: #16a34a; }
        .money-item.pending  .mval { color: #d97706; }

        .campaign-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto; }

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
        .btn-warning { background: #d97706; }
        .btn-success { background: #16a34a; }
        .btn-danger  { background: #dc2626; }
        .btn-ghost {
            background: #f1f5f9; color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        .btn-ghost:hover { background: #e2e8f0; opacity: 1; }

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
            .campaign-card { flex-direction: column; }
            .campaign-img-wrap { width: 100%; min-width: unset; height: 190px; }
            .stats-bar { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="navbar-brand" href="index.php">
        <img src="uploads/LogoKecil.png" alt="Logo">
        Crowdfunding Sosial
    </a>
    <div class="navbar-links">
        <span class="user-greet">Halo, <?= htmlspecialchars($_SESSION['user_name']) ?> |</span>
        <a href="index.php">Beranda</a>
        <a href="campaign_add.php" class="btn-nav-primary">+ Tambah Campaign</a>
        <a href="logout.php" class="btn-nav-danger">Logout</a>
    </div>
</nav>

<div class="container">

    <div class="page-head">
        <h1>Dashboard Pengelola</h1>
        <p>Kelola campaign dan pantau donasi Anda</p>
    </div>

    <!-- Stats -->
    <div class="stats-bar">
        <?php
        $total_target    = array_sum(array_column($all_campaigns, 'target_amount'));
        $total_collected = array_sum(array_column($all_campaigns, 'collected_amount'));
        $total_pending   = array_sum(array_column($all_campaigns, 'pending_amount'));
        ?>
        <div class="stat-card">
            <div class="stat-label">Total Campaign</div>
            <div class="stat-value"><?= $total_campaigns ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Total Terkumpul</div>
            <div class="stat-value">Rp<?= number_format($total_collected, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label">Dana Pending</div>
            <div class="stat-value">Rp<?= number_format($total_pending, 0, ',', '.') ?></div>
        </div>
    </div>

    <!-- Section head -->
    <div class="section-head">
        <h2>Daftar Campaign</h2>
        <span class="s-count"><?= $total_campaigns ?> campaign</span>
    </div>

    <?php if (empty($all_campaigns)): ?>
        <div class="empty-state">
            <p>Belum ada campaign. Mulai buat campaign pertama Anda!</p>
            <a href="campaign_add.php" class="btn btn-primary">Buat Campaign</a>
        </div>
    <?php else: ?>
        <?php foreach ($all_campaigns as $c):
            $pct = ($c['target_amount'] > 0) ? min(100, round(($c['collected_amount'] / $c['target_amount']) * 100)) : 0;
            $is_expired = strtotime($c['deadline']) < time();
            $imgFile    = 'uploads/' . $c['image'];
            $imgExists  = !empty($c['image']) && file_exists($imgFile);
        ?>
        <div class="campaign-card">
            <div class="campaign-img-wrap">
                <?php if ($imgExists): ?>
                    <img src="<?= htmlspecialchars($imgFile) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
                <?php else: ?>
                    <div class="no-image">Tidak ada gambar</div>
                <?php endif; ?>
            </div>
            <div class="campaign-body">
                <div class="title-row">
                    <h3><?= htmlspecialchars($c['title']) ?></h3>
                    <span class="badge <?= $is_expired ? 'badge-expired' : 'badge-active' ?>">
                        <?= $is_expired ? 'Berakhir' : 'Aktif' ?>
                    </span>
                </div>
                <div class="campaign-meta">
                    <span><strong><?= htmlspecialchars($c['category']) ?></strong></span>
                    <span><?= htmlspecialchars($c['location']) ?></span>
                    <span>Deadline: <?= date('d M Y', strtotime($c['deadline'])) ?></span>
                    <span><?= $c['total_donatur'] ?> donatur</span>
                </div>
                <div class="progress-wrap">
                    <?php $pc = progColor($pct); ?>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $pc['grad'] ?>"></div>
                    </div>
                    <div class="progress-label">
                        <span class="pct"><?= $pct ?>% tercapai<?= $pc['fire'] ? ' 🔥' : '' ?></span>
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
                       onclick="return confirm('Yakin ingin menghapus campaign ini?')">Hapus</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="dash-footer">Copyright &copy; 2026 Crowdfunding Sosial. All Rights Reserved</div>

</div>
</body>
</html>