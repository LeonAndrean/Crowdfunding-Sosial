<?php
require 'config.php';

function progColor(int $pct): array {
    if ($pct <= 50)  return ['grad' => 'linear-gradient(90deg,#2563eb,#38bdf8)', 'fire' => false];
    if ($pct <= 75)  return ['grad' => 'linear-gradient(90deg,#d97706,#fbbf24)', 'fire' => false];
    return               ['grad' => 'linear-gradient(90deg,#dc2626,#f97316)', 'fire' => true];
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT c.*, u.name AS manager_name
    FROM campaigns c
    JOIN users u ON c.manager_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
if (!$campaign) die("Kampanye tidak ditemukan.");

$pct = ($campaign['target_amount'] > 0)
    ? min(100, round(($campaign['collected_amount'] / $campaign['target_amount']) * 100))
    : 0;

$is_expired  = strtotime($campaign['deadline']) < time();
$days_left   = ceil((strtotime($campaign['deadline']) - time()) / 86400);
$imgPath     = 'uploads/' . $campaign['image'];
$imgExists   = !empty($campaign['image']) && file_exists($imgPath);

// Donatur count
$dStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM donations WHERE campaign_id = ?");
$dStmt->bind_param("i", $id);
$dStmt->execute();
$donatur_count = $dStmt->get_result()->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($campaign['title']) ?> – Crowdfunding Sosial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">
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
            padding: 0 40px;
            height: 60px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .navbar-brand {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.2rem; font-weight: 600; color: #fff;
            text-decoration: none; display: flex; align-items: center; gap: 10px;
        }
        .navbar-brand img { height: 34px; width: auto; object-fit: contain; }
        .navbar-links { display: flex; align-items: center; gap: 4px; font-size: 0.88rem; }
        .navbar-links a {
            color: #cbd5e1; text-decoration: none; padding: 6px 12px;
            border-radius: 8px; transition: background .2s, color .2s; font-weight: 500;
        }
        .navbar-links a:hover { background: rgba(255,255,255,.1); color: #fff; }
        .navbar-links a.nav-cta { background: #2563eb; color: #fff; margin-left: 4px; }
        .navbar-links a.nav-cta:hover { background: #1d4ed8; }
        .navbar-links a.danger { background: #dc2626; color: #fff; margin-left: 4px; }
        .navbar-links a.danger:hover { background: #b91c1c; }

        /* ── Page ── */
        .page {
            max-width: 860px;
            margin: 0 auto;
            padding: 36px 20px 60px;
        }

        /* ── Back button ── */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.82rem; font-weight: 600; color: #64748b;
            text-decoration: none; margin-bottom: 24px;
            padding: 8px 16px; border-radius: 9px;
            background: #fff; border: 1px solid #e2e8f0;
            transition: background .2s, color .2s, border-color .2s;
        }
        .back-link:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }

        /* ── Main card ── */
        .detail-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(15,23,42,.07);
        }

        /* Hero image */
        .hero-img {
            width: 100%; height: 380px;
            overflow: hidden; position: relative;
        }
        .hero-img img {
            width: 100%; height: 100%;
            object-fit: cover; display: block;
        }
        .hero-img .no-img {
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #1e293b, #334155);
            display: flex; align-items: center; justify-content: center;
            color: #64748b; font-size: 0.9rem;
        }
        /* Category & status overlay */
        .hero-badges {
            position: absolute; top: 16px; left: 16px;
            display: flex; gap: 8px;
        }
        .hbadge {
            font-size: 0.72rem; font-weight: 700;
            padding: 4px 12px; border-radius: 99px;
            backdrop-filter: blur(8px);
            text-transform: uppercase; letter-spacing: .04em;
        }
        .hbadge-cat { background: rgba(15,23,42,.7); color: #fff; }
        .hbadge-active  { background: rgba(22,163,74,.85); color: #fff; }
        .hbadge-expired { background: rgba(220,38,38,.85); color: #fff; }

        /* Content area */
        .detail-body { padding: 32px; }

        .detail-title {
            font-size: 1.6rem; font-weight: 800;
            color: #0f172a; margin-bottom: 16px; line-height: 1.3;
        }

        /* Meta pills row */
        .meta-row {
            display: flex; flex-wrap: wrap; gap: 8px;
            margin-bottom: 24px;
        }
        .meta-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: #f1f5f9; border: 1px solid #e2e8f0;
            border-radius: 99px; padding: 5px 12px;
            font-size: 0.78rem; color: #475569; font-weight: 600;
        }
        .meta-pill .pi { font-size: 0.75rem; }

        /* Stats row */
        .stats-row {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 12px; margin-bottom: 24px;
        }
        .stat-box {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 14px 16px; text-align: center;
        }
        .stat-box .sl { font-size: 0.68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
        .stat-box .sv { font-size: 1rem; font-weight: 800; color: #1e293b; }
        .stat-box.green .sv { color: #16a34a; }
        .stat-box.blue  .sv { color: #2563eb; }
        .stat-box.amber .sv { color: #d97706; }

        /* Progress */
        .prog-wrap { margin-bottom: 24px; }
        .prog-header {
            display: flex; justify-content: space-between; align-items: baseline;
            margin-bottom: 8px;
        }
        .prog-header .ph-label { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
        .prog-header .ph-pct   { font-size: 1rem; font-weight: 800; color: #2563eb; }
        .prog-bg {
            background: #e2e8f0; border-radius: 99px;
            height: 10px; overflow: hidden;
        }
        .prog-fill {
            height: 100%; border-radius: 99px;
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            transition: width .6s ease;
        }
        .prog-footer {
            display: flex; justify-content: space-between;
            font-size: 0.75rem; color: #94a3b8; margin-top: 6px;
        }

        /* Divider */
        .divider { border: none; border-top: 1px solid #f1f5f9; margin: 24px 0; }

        /* Deskripsi */
        .desc-label {
            font-size: 0.75rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px;
        }
        .desc-text {
            font-size: 0.92rem; color: #334155; line-height: 1.75;
        }

        /* Bank info */
        .bank-box {
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 12px; padding: 16px 20px;
            margin-top: 20px;
        }
        .bank-box .bl { font-size: 0.72rem; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
        .bank-box .bv { font-size: 0.92rem; font-weight: 700; color: #1e293b; }

        /* Deadline info */
        .deadline-box {
            background: <?= $is_expired ? '#fee2e2' : '#f0fdf4' ?>;
            border: 1px solid <?= $is_expired ? '#fecaca' : '#bbf7d0' ?>;
            border-radius: 12px; padding: 14px 18px;
            margin-top: 12px;
            display: flex; align-items: center; gap: 10px;
        }
        .deadline-box .db-icon { font-size: 1.1rem; }
        .deadline-box .db-text {
            font-size: 0.84rem; font-weight: 600;
            color: <?= $is_expired ? '#b91c1c' : '#15803d' ?>;
        }
        .deadline-box .db-sub { font-size: 0.75rem; color: <?= $is_expired ? '#dc2626' : '#16a34a' ?>; opacity: .8; }

        /* CTA button */
        .btn-donate {
            display: block; width: 100%;
            padding: 15px; margin-top: 28px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; border-radius: 12px;
            font-size: 1rem; font-weight: 800; font-family: inherit;
            text-align: center; text-decoration: none;
            cursor: pointer;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(37,99,235,.35);
            letter-spacing: .02em;
        }
        .btn-donate:hover {
            opacity: .92; transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(37,99,235,.45);
        }
        .btn-donate.disabled {
            background: #94a3b8; cursor: not-allowed;
            box-shadow: none; pointer-events: none;
        }

        @media (max-width: 600px) {
            .navbar { padding: 0 16px; }
            .hero-img { height: 220px; }
            .detail-body { padding: 20px; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .detail-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="index.php">
        <img src="uploads/LogoKecil.png" alt="Logo">
        Crowdfunding Sosial
    </a>
    <div class="navbar-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span style="color:#94a3b8;font-size:.85rem;">Halo, <?= htmlspecialchars($_SESSION['user_name']) ?> |</span>
            <?php if ($_SESSION['user_role'] === 'donor'): ?>
                <a href="donation_history.php">Riwayat</a>
            <?php else: ?>
                <a href="manager_dashboard.php" class="nav-cta">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" class="danger">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="nav-cta">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<div class="page">

    <a href="index.php" class="back-link">Kembali ke Halaman Utama</a>

    <div class="detail-card">

        <!-- Hero image -->
        <div class="hero-img">
            <?php if ($imgExists): ?>
                <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($campaign['title']) ?>">
            <?php else: ?>
                <div class="no-img">Tidak ada gambar</div>
            <?php endif; ?>
            <div class="hero-badges">
                <span class="hbadge hbadge-cat"><?= htmlspecialchars($campaign['category']) ?></span>
                <span class="hbadge <?= $is_expired ? 'hbadge-expired' : 'hbadge-active' ?>">
                    <?= $is_expired ? 'Berakhir' : 'Aktif' ?>
                </span>
            </div>
        </div>

        <div class="detail-body">

            <h1 class="detail-title"><?= htmlspecialchars($campaign['title']) ?></h1>

            <!-- Meta pills -->
            <div class="meta-row">
                <span class="meta-pill"><?= htmlspecialchars($campaign['manager_name']) ?></span>
                <span class="meta-pill"><?= htmlspecialchars($campaign['location']) ?></span>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box green">
                    <div class="sl">Terkumpul</div>
                    <div class="sv">Rp<?= number_format($campaign['collected_amount'], 0, ',', '.') ?></div>
                </div>
                <div class="stat-box blue">
                    <div class="sl">Target</div>
                    <div class="sv">Rp<?= number_format($campaign['target_amount'], 0, ',', '.') ?></div>
                </div>
                <div class="stat-box amber">
                    <div class="sl">Donatur</div>
                    <div class="sv"><?= $donatur_count ?> orang</div>
                </div>
            </div>

            <!-- Progress -->
            <?php $pc = progColor($pct); ?>
            <div class="prog-wrap">
                <div class="prog-header">
                    <span class="ph-label">Dana Terkumpul</span>
                    <span class="ph-pct" style="color:<?= $pct > 75 ? '#dc2626' : ($pct > 50 ? '#d97706' : '#2563eb') ?>">
                        <?= $pct ?>%<?= $pc['fire'] ? ' 🔥' : '' ?>
                    </span>
                </div>
                <div class="prog-bg">
                    <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $pc['grad'] ?>"></div>
                </div>
                <div class="prog-footer">
                    <span>Rp<?= number_format($campaign['collected_amount'], 0, ',', '.') ?> terkumpul</span>
                    <span>Target Rp<?= number_format($campaign['target_amount'], 0, ',', '.') ?></span>
                </div>
            </div>

            <hr class="divider">

            <!-- Deskripsi -->
            <div class="desc-label">Tentang Campaign</div>
            <div class="desc-text"><?= nl2br(htmlspecialchars($campaign['description'])) ?></div>

            <!-- Bank info -->
            <div class="bank-box">
                <div class="bl">Info Rekening Pembayaran</div>
                <div class="bv"><?= htmlspecialchars($campaign['bank_info']) ?></div>
            </div>

            <!-- Deadline -->
            <div class="deadline-box">
                <div class="db-icon"><?= $is_expired ? '&#128683;' : '&#128336;' ?></div>
                <div>
                    <div class="db-text">
                        <?= $is_expired
                            ? 'Campaign ini telah berakhir'
                            : ($days_left <= 7 ? 'Segera berakhir!' : 'Campaign masih berjalan') ?>
                    </div>
                    <div class="db-sub">
                        Deadline: <?= date('d M Y, H:i', strtotime($campaign['deadline'])) ?>
                        <?= !$is_expired ? " &middot; $days_left hari lagi" : '' ?>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <?php if (!$is_expired && isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'donor'): ?>
                <a class="btn-donate" href="donate.php?campaign_id=<?= $campaign['id'] ?>">Donasi Sekarang</a>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <a class="btn-donate" href="login.php">Login untuk Berdonasi</a>
            <?php elseif ($is_expired): ?>
                <div class="btn-donate disabled">Campaign Telah Berakhir</div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>