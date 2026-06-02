<?php
require 'config.php';

$keyword = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$where = "WHERE c.deadline >= NOW()";
$params = [];
$types = "";

if ($keyword !== '') {
    $where .= " AND (c.title LIKE ? OR c.category LIKE ? OR c.location LIKE ? OR u.name LIKE ? OR DATE(c.deadline) LIKE ?)";
    $searchLike = "%$keyword%";
    $params = [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike];
    $types = "sssss";
}

// Count
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM campaigns c JOIN users u ON c.manager_id = u.id $where");
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalRows / $limit));

// Total stats for hero
$statsStmt = $conn->query("SELECT COUNT(*) AS tc, COALESCE(SUM(collected_amount),0) AS tca FROM campaigns WHERE deadline >= NOW()");
$stats = $statsStmt->fetch_assoc();

// Campaigns
$sql = "SELECT c.*, u.name AS manager_name
        FROM campaigns c JOIN users u ON c.manager_id = u.id
        $where
        ORDER BY c.deadline ASC, c.collected_amount ASC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types . "ii", ...[...$params, $limit, $offset]);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crowdfunding Sosial – Bersama Kita</title>
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <img src="uploads/LogoKecil.png" alt="Logo">
        Crowdfunding Sosial
    </div>
    <div class="navbar-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="user-greet">Halo, <?= htmlspecialchars($_SESSION['user_name']) ?> |</span>
            <?php if ($_SESSION['user_role'] === 'donor'): ?>
                <a href="donation_history.php">Riwayat</a>
                <a href="summary.php">Summary</a>
            <?php else: ?>
                <a href="manager_dashboard.php" class="btn-nav-cta">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-nav-cta">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="hero-inner">
        <h1>Bersama Kita<br><em>Wujudkan Perubahan</em></h1>
        <p>Platform penggalangan dana sosial untuk membantu sesama. Setiap donasi, sekecil apapun, berarti besar bagi mereka yang membutuhkan.</p>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="val"><?= $stats['tc'] ?>+</span>
                <span class="lbl">Campaign Aktif</span>
            </div>
            <div class="hero-stat">
                <span class="val">Rp<?= number_format($stats['tca'] / 1000000, 1, ',', '.') ?>Jt</span>
                <span class="lbl">Dana Terkumpul</span>
            </div>
            <div class="hero-stat">
                <span class="val">100%</span>
                <span class="lbl">Transparan</span>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Search -->
    <div class="search-wrap">
        <div class="search-label">Cari Campaign</div>
        <form class="search-box" method="get">
            <input type="text" name="search" placeholder="Cari judul, kategori, lokasi, pengelola..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit">Cari</button>
        </form>
    </div>

    <!-- Section heading -->
    <div class="section-head">
        <h2><?= $keyword ? 'Hasil Pencarian' : 'Campaign Aktif' ?></h2>
        <span class="count"><?= $totalRows ?> campaign ditemukan</span>
    </div>

    <!-- Grid -->
    <div class="grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $pct = ($row['target_amount'] > 0)
                    ? min(100, round(($row['collected_amount'] / $row['target_amount']) * 100))
                    : 0;
                $daysLeft = ceil((strtotime($row['deadline']) - time()) / 86400);
                $imgPath  = 'uploads/' . $row['image'];
                $imgExists = !empty($row['image']) && file_exists($imgPath);
            ?>
            <div class="card">
                <div class="card-img-wrap">
                    <?php if ($imgExists): ?>
                        <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                    <?php else: ?>
                        <div class="no-img">Tidak ada gambar</div>
                    <?php endif; ?>
                    <span class="card-category-badge"><?= htmlspecialchars($row['category']) ?></span>
                </div>
                <div class="card-body">
                    <span class="deadline-badge <?= $daysLeft <= 14 ? 'soon' : 'normal' ?>">
                        <?= $daysLeft > 0 ? $daysLeft . ' hari lagi' : 'Berakhir hari ini' ?>
                    </span>
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <div class="card-meta">
                        <span><?= htmlspecialchars($row['manager_name']) ?></span>
                        <span><?= htmlspecialchars($row['location'] ?? '-') ?></span>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="progress-row">
                            <span class="pct"><?= $pct ?>% tercapai</span>
                            <span class="target">Target: Rp<?= number_format($row['target_amount'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="card-amounts">
                        <div class="amount-box collected">
                            <div class="alabel">Terkumpul</div>
                            <div class="aval">Rp<?= number_format($row['collected_amount'], 0, ',', '.') ?></div>
                        </div>
                        <div class="amount-box">
                            <div class="alabel">Target</div>
                            <div class="aval">Rp<?= number_format($row['target_amount'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <a class="btn-detail" href="detail.php?id=<?= $row['id'] ?>">Lihat Detail Campaign</a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>Tidak ada campaign yang ditemukan<?= $keyword ? ' untuk "' . htmlspecialchars($keyword) . '"' : '' ?>.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="<?= $i == $page ? 'active' : '' ?>"
               href="?page=<?= $i ?>&search=<?= urlencode($keyword) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Tombol Lihat Detail berubah hijau saat diklik
document.querySelectorAll('.btn-detail').forEach(btn => {
    btn.addEventListener('click', function(e) {
        // Mark as clicked (green) briefly before navigating
        this.classList.add('clicked');
    });
});
</script>

<!-- Footer -->
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-links">
            <a href="#">Tentang Crowdfunding Social</a>
            <span class="footer-sep">|</span>
            <a href="#">Syarat &amp; Ketentuan</a>
            <span class="footer-sep">|</span>
            <a href="#">Pusat Bantuan</a>
        </div>
        <div class="footer-socials">
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="Facebook">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="Twitter/X">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.7 5.4 4.4 9 4.4C11.5 4.7 17-1 22 4z"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="Instagram">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="YouTube">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42C1 8.14 1 12 1 12s0 3.86.46 5.58a2.78 2.78 0 0 0 1.95 1.95C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95C23 15.86 23 12 23 12s0-3.86-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="TikTok">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="LinkedIn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
        </div>
        <div class="footer-copy">Copyright &copy; 2026 Crowdfunding Social. All Rights Reserved</div>
    </div>
</footer>

</body>
</html>