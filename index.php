<?php
require 'config.php';

function progColor(int $pct): array {
    if ($pct <= 50)  return ['grad' => 'linear-gradient(90deg,#2563eb,#38bdf8)', 'fire' => false];
    if ($pct <= 75)  return ['grad' => 'linear-gradient(90deg,#d97706,#fbbf24)', 'fire' => false];
    return               ['grad' => 'linear-gradient(90deg,#dc2626,#f97316)', 'fire' => true];
}

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

// Recent donations for live notification ticker
$notifStmt = $conn->query("
    SELECT u.name AS donor_name, d.amount, c.title AS campaign_title
    FROM donations d
    JOIN users u ON d.donor_id = u.id
    JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.status IN ('pending','verified')
    ORDER BY d.created_at DESC
    LIMIT 15
");
$recentDonations = $notifStmt->fetch_all(MYSQLI_ASSOC);

// Mask name: show first char + *** + last char
function maskName(string $name): string {
    $parts = explode(' ', trim($name));
    $masked = array_map(function($w) {
        $len = mb_strlen($w);
        if ($len <= 2) return $w[0] . '*';
        return mb_substr($w,0,1) . str_repeat('*', max(2,$len-2)) . mb_substr($w,-1);
    }, $parts);
    return implode(' ', $masked);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crowdfunding Sosial – Bersama Kita Bisa</title>
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <img src="uploads/LogoKecil.png" alt="Logo">
        Berbagi Donasi Social
    </div>
    <div class="navbar-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="user-greet">Halo, <?= htmlspecialchars($_SESSION['user_name']) ?> |</span>
            <?php if ($_SESSION['user_role'] === 'donor'): ?>
                <a href="donation_history.php">Riwayat</a>
                <a href="pengaturan_akun.php">Akun</a>
            <?php else: ?>
                <a href="manager_dashboard.php" class="btn-nav-cta">Dashboard</a>
                <a href="pengaturan_akun.php">Akun</a>
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
        <h1>Bersama Berbagi Donasi<br><em>Wujudkan Impian Mereka</em></h1>
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
                            <?php $pc = progColor($pct); ?>
                            <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pc['grad'] ?>"></div>
                        </div>
                        <div class="progress-row">
                            <span class="pct"><?= $pct ?>% tercapai<?= $pc['fire'] ? ' 🔥' : '' ?></span>
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
    <?php if ($totalPages >= 1 && $totalRows > 0): ?>
    <div class="pagination-wrap">
        <div class="pagination-info">
            Menampilkan <?= min($offset + 1, $totalRows) ?>–<?= min($offset + $limit, $totalRows) ?> dari <?= $totalRows ?> campaign
        </div>
        <div class="pagination">
            <!-- Prev -->
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($keyword) ?>" class="pag-prev">&#8592; Sebelumnya</a>
            <?php else: ?>
                <span class="pag-prev disabled">&#8592; Sebelumnya</span>
            <?php endif; ?>

            <!-- Page numbers -->
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1): ?>
                <a href="?page=1&search=<?= urlencode($keyword) ?>">1</a>
                <?php if ($start > 2): ?><span class="pag-ellipsis">...</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a class="<?= $i == $page ? 'active' : '' ?>"
                   href="?page=<?= $i ?>&search=<?= urlencode($keyword) ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="pag-ellipsis">...</span><?php endif; ?>
                <a href="?page=<?= $totalPages ?>&search=<?= urlencode($keyword) ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <!-- Next -->
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($keyword) ?>" class="pag-next">Selanjutnya &#8594;</a>
            <?php else: ?>
                <span class="pag-next disabled">Selanjutnya &#8594;</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Tombol Lihat Detail berubah hijau saat diklik
document.querySelectorAll('.btn-detail').forEach(btn => {
    btn.addEventListener('click', function(e) {
        this.classList.add('clicked');
    });
});
</script>

<?php if (!empty($recentDonations)): ?>
<!-- Live donation toast notifications -->
<style>
.toast-wrap {
    position: fixed;
    bottom: 24px;
    left: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column-reverse;
    gap: 10px;
    pointer-events: none;
}
.d-toast {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    box-shadow: 0 8px 32px rgba(15,23,42,.14), 0 2px 8px rgba(15,23,42,.08);
    min-width: 280px;
    max-width: 340px;
    pointer-events: auto;
    opacity: 0;
    transform: translateX(-24px) scale(.97);
    transition: opacity .35s ease, transform .35s ease;
}
.d-toast.show {
    opacity: 1;
    transform: translateX(0) scale(1);
}
.d-toast.hide {
    opacity: 0;
    transform: translateX(-24px) scale(.97);
}
.toast-avatar {
    width: 36px; height: 36px;
    border-radius: 99px;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; font-weight: 800;
    flex-shrink: 0;
}
.toast-body { flex: 1; min-width: 0; }
.toast-name {
    font-size: 0.82rem; font-weight: 700;
    color: #1e293b; margin-bottom: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.toast-detail {
    font-size: 0.75rem; color: #64748b; line-height: 1.4;
}
.toast-detail .toast-amount {
    font-weight: 700; color: #16a34a;
}
.toast-detail .toast-campaign {
    color: #2563eb;
}
.toast-dot {
    width: 8px; height: 8px;
    border-radius: 99px;
    background: #16a34a;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(22,163,74,.2);
    animation: pulse 1.8s ease infinite;
}
@keyframes pulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(22,163,74,.2); }
    50%      { box-shadow: 0 0 0 6px rgba(22,163,74,.05); }
}
@media (max-width: 480px) {
    .toast-wrap { left: 12px; bottom: 12px; }
    .d-toast { min-width: 240px; max-width: calc(100vw - 24px); }
}
</style>

<div class="toast-wrap" id="toastWrap"></div>

<script>
const donations = <?= json_encode(array_map(function($d) {
    return [
        'name'     => maskName($d['donor_name']),
        'initial'  => mb_strtoupper(mb_substr($d['donor_name'], 0, 1)),
        'amount'   => 'Rp' . number_format($d['amount'], 0, ',', '.'),
        'campaign' => $d['campaign_title'],
    ];
}, $recentDonations), JSON_UNESCAPED_UNICODE) ?>;

const wrap   = document.getElementById('toastWrap');
let idx      = 0;

function showNext() {
    if (!donations.length) return;
    const d = donations[idx % donations.length];
    idx++;

    const t = document.createElement('div');
    t.className = 'd-toast';
    t.innerHTML = `
        <div class="toast-avatar">${d.initial}</div>
        <div class="toast-body">
            <div class="toast-name">${d.name} baru saja berdonasi</div>
            <div class="toast-detail">
                <span class="toast-amount">${d.amount}</span>
                untuk <span class="toast-campaign">${d.campaign}</span>
            </div>
        </div>
        <div class="toast-dot"></div>
    `;
    wrap.appendChild(t);

    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));

    setTimeout(() => {
        t.classList.remove('show');
        t.classList.add('hide');
        setTimeout(() => t.remove(), 400);
    }, 4200);
}

setTimeout(() => {
    showNext();
    setInterval(showNext, 5500);
}, 1500);
</script>
<?php endif; ?>

<!-- Footer -->
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-links">
            <a href="#">Tentang Berbagi Donasi Social</a>
            <span>|</span>
            <a href="snk.php">Syarat &amp; Ketentuan</a>
            <span>|</span>
            <a href="#">Pusat Bantuan</a>
        </div>
        <div class="footer-socials">
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="Facebook">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="X">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="Instagram">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
            <a href="https://youtu.be/dQw4w9WgXcQ?si=qAxvmFhMQ8qPHjA4" target="_blank" rel="noopener" aria-label="YouTube">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.95C5.12 20 12 20 12 20s6.88 0 8.59-.47a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="#fff"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="TikTok">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V9.05a8.16 8.16 0 0 0 4.78 1.52V7.12a4.85 4.85 0 0 1-1.01-.43z"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="LinkedIn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
            <a href="https://www.instagram.com/crowdfunding_sociall/" target="_blank" rel="noopener" aria-label="Website">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            </a>
        </div>
        <div class="footer-copy">Copyright &copy; 2026 Berbagi Donasi Social. All Rights Reserved</div>
    </div>
</footer>

<style>
.site-footer {
    background: #fff;
    border-top: 1px solid #e2e8f0;
    padding: 32px 40px 28px;
    margin-top: 24px;
}
.footer-inner {
    max-width: 1240px; margin: 0 auto;
    display: flex; flex-direction: column; align-items: center; gap: 18px;
    text-align: center;
}
.footer-links {
    display: flex; flex-wrap: wrap; align-items: center;
    justify-content: center; gap: 8px 4px;
    font-size: 0.85rem;
}
.footer-links a {
    color: #475569; text-decoration: none; padding: 2px 6px;
    transition: color .2s;
}
.footer-links a:hover { color: #2563eb; }
.footer-links span { color: #cbd5e1; }

.footer-socials {
    display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;
}
.footer-socials a {
    width: 40px; height: 40px;
    border-radius: 99px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #475569;
    text-decoration: none;
    transition: background .2s, color .2s, border-color .2s, transform .15s;
}
.footer-socials a:hover {
    background: #2563eb; color: #fff;
    border-color: #2563eb;
    transform: translateY(-2px);
}
.footer-copy {
    font-size: 0.78rem; color: #94a3b8;
}
</style>
</body>
</html>