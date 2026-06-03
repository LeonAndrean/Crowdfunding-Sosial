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

$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$manager_id  = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND manager_id = ?");
$stmt->bind_param("ii", $campaign_id, $manager_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
if (!$campaign) die("Kampanye tidak ditemukan.");

$flash = "";
if (isset($_POST['verify']) || isset($_POST['reject'])) {
    $donation_id = (int)$_POST['donation_id'];
    $stmt = $conn->prepare("SELECT * FROM donations WHERE id = ? AND campaign_id = ?");
    $stmt->bind_param("ii", $donation_id, $campaign_id);
    $stmt->execute();
    $donation = $stmt->get_result()->fetch_assoc();

    if ($donation && $donation['status'] === 'pending') {
        if (isset($_POST['verify'])) {
            $conn->begin_transaction();
            try {
                $u1 = $conn->prepare("UPDATE donations SET status='verified' WHERE id=?");
                $u1->bind_param("i", $donation_id);
                $u1->execute();
                $u2 = $conn->prepare("UPDATE campaigns SET collected_amount = collected_amount + ? WHERE id=?");
                $u2->bind_param("di", $donation['amount'], $campaign_id);
                $u2->execute();
                $conn->commit();
                $flash = "success|Donasi berhasil diverifikasi.";
            } catch (Throwable $e) {
                $conn->rollback();
                $flash = "error|Gagal verifikasi.";
            }
        } elseif (isset($_POST['reject'])) {
            $u = $conn->prepare("UPDATE donations SET status='rejected' WHERE id=?");
            $u->bind_param("i", $donation_id);
            $u->execute();
            $flash = "warning|Donasi ditolak.";
        }
    }
    header("Location: verify_donations.php?campaign_id=$campaign_id&flash=" . urlencode($flash));
    exit;
}

$flash = $_GET['flash'] ?? "";

$stmt = $conn->prepare("
    SELECT d.*, u.name AS donor_name, u.email AS donor_email
    FROM donations d
    JOIN users u ON d.donor_id = u.id
    WHERE d.campaign_id = ?
    ORDER BY d.created_at DESC
");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$donations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_verified = array_sum(array_map(fn($d) => $d['status']==='verified' ? $d['amount'] : 0, $donations));
$total_pending  = array_sum(array_map(fn($d) => $d['status']==='pending'  ? $d['amount'] : 0, $donations));
$count_pending  = count(array_filter($donations, fn($d) => $d['status']==='pending'));

$pct = ($campaign['target_amount'] > 0)
    ? min(100, round(($campaign['collected_amount'] / $campaign['target_amount']) * 100))
    : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donasi – <?= htmlspecialchars($campaign['title']) ?></title>
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
        .navbar-links a.danger { background: #dc2626; color: #fff; margin-left: 4px; }
        .navbar-links a.danger:hover { background: #b91c1c; }

        /* ── Page ── */
        .page { max-width: 1000px; margin: 0 auto; padding: 36px 20px 60px; }

        /* ── Back link ── */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.82rem; font-weight: 600; color: #64748b;
            text-decoration: none; margin-bottom: 20px;
            padding: 7px 14px; border-radius: 8px;
            background: #fff; border: 1px solid #e2e8f0;
            transition: background .2s, color .2s;
        }
        .back-link:hover { background: #f1f5f9; color: #1e293b; }

        /* ── Campaign summary card ── */
        .summary-card {
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 16px; padding: 24px 28px;
            margin-bottom: 20px;
            display: flex; gap: 24px; flex-wrap: wrap; align-items: center;
        }
        .summary-card .s-title {
            font-size: 1.15rem; font-weight: 800; color: #1e293b; margin-bottom: 4px;
        }
        .summary-card .s-meta {
            font-size: 0.8rem; color: #64748b;
        }
        .summary-right { display: flex; gap: 14px; flex-wrap: wrap; margin-left: auto; }
        .s-pill {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 10px; padding: 10px 18px; text-align: center;
        }
        .s-pill .pl { font-size: 0.68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px; }
        .s-pill .pv { font-size: 1rem; font-weight: 800; }
        .s-pill.blue .pv  { color: #2563eb; }
        .s-pill.green .pv { color: #16a34a; }
        .s-pill.amber .pv { color: #d97706; }

        /* progress */
        .prog-wrap { flex: 1; min-width: 220px; }
        .prog-bg { background: #e2e8f0; border-radius: 99px; height: 8px; overflow: hidden; margin-top: 10px; }
        .prog-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #2563eb, #06b6d4); }
        .prog-label { display: flex; justify-content: space-between; font-size: 0.73rem; color: #64748b; margin-top: 4px; }
        .prog-label .pct { color: #2563eb; font-weight: 700; }

        /* ── Flash ── */
        .flash {
            padding: 12px 16px; border-radius: 10px; margin-bottom: 18px;
            font-size: 0.85rem; font-weight: 600;
        }
        .flash.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .flash.error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .flash.warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* ── Section heading ── */
        .section-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1.5px solid #e2e8f0;
        }
        .section-head h2 {
            font-size: 0.82rem; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .section-head .pending-badge {
            background: #fef3c7; color: #92400e;
            font-size: 0.72rem; font-weight: 700;
            padding: 3px 10px; border-radius: 99px;
        }

        /* ── Table ── */
        .table-wrap {
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 16px; overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; }
        thead th {
            padding: 13px 16px; text-align: left;
            font-size: 0.72rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .06em;
            white-space: nowrap;
        }
        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .15s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }
        td {
            padding: 14px 16px; font-size: 0.85rem; color: #334155;
            vertical-align: middle;
        }

        /* donor info cell */
        .donor-cell .d-name { font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .donor-cell .d-email { font-size: 0.75rem; color: #94a3b8; }

        /* amount */
        .amount-cell { font-weight: 700; color: #1e293b; font-size: 0.9rem; }

        /* status badge */
        .status-badge {
            display: inline-block; padding: 3px 10px; border-radius: 99px;
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        }
        .status-verified { background: #dcfce7; color: #15803d; }
        .status-pending  { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }

        /* date */
        .date-cell { font-size: 0.78rem; color: #94a3b8; white-space: nowrap; }

        /* proof */
        .proof-link {
            display: inline-block; font-size: 0.75rem; font-weight: 600;
            color: #2563eb; text-decoration: none;
            padding: 3px 10px; border-radius: 6px; border: 1px solid #bfdbfe;
            background: #eff6ff; transition: background .15s;
        }
        .proof-link:hover { background: #dbeafe; }
        .no-proof { font-size: 0.75rem; color: #cbd5e1; }

        /* action buttons */
        .action-cell { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-verify, .btn-reject {
            padding: 6px 14px; border-radius: 7px;
            font-size: 0.75rem; font-weight: 700; border: none; cursor: pointer;
            font-family: inherit; transition: opacity .15s, transform .1s;
        }
        .btn-verify { background: #16a34a; color: #fff; }
        .btn-reject { background: #f1f5f9; color: #dc2626; border: 1px solid #fecaca; }
        .btn-verify:hover, .btn-reject:hover { opacity: .85; transform: translateY(-1px); }

        /* empty */
        .empty-row td {
            text-align: center; padding: 56px 20px;
            color: #94a3b8; font-size: 0.88rem;
        }

        /* footer */
        .page-footer { margin-top: 40px; text-align: center; font-size: 0.78rem; color: #cbd5e1; }

        @media (max-width: 660px) {
            .navbar { padding: 0 16px; }
            table { font-size: 0.8rem; }
            td, th { padding: 10px 10px; }
            .summary-card { flex-direction: column; }
            .summary-right { margin-left: 0; }
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
        <a href="manager_dashboard.php">Dashboard</a>
        <a href="logout.php" class="danger">Logout</a>
    </div>
</nav>

<div class="page">

    <a href="manager_dashboard.php" class="back-link">← Kembali ke Dashboard</a>

    <!-- Campaign summary -->
    <div class="summary-card">
        <div>
            <div class="s-title"><?= htmlspecialchars($campaign['title']) ?></div>
            <div class="s-meta"><?= htmlspecialchars($campaign['category']) ?> &middot; <?= htmlspecialchars($campaign['location']) ?></div>
            <div class="prog-wrap">
                    <?php $pc = progColor($pct); ?>
                <div class="prog-bg">
                    <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $pc['grad'] ?>"></div>
                </div>
                <div class="prog-label">
                    <span class="pct"><?= $pct ?>% tercapai<?= $pc['fire'] ? ' 🔥' : '' ?></span>
                    <span>Target: Rp<?= number_format($campaign['target_amount'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
        <div class="summary-right">
            <div class="s-pill blue">
                <div class="pl">Total Donasi</div>
                <div class="pv"><?= count($donations) ?></div>
            </div>
            <div class="s-pill green">
                <div class="pl">Terverifikasi</div>
                <div class="pv">Rp<?= number_format($total_verified, 0, ',', '.') ?></div>
            </div>
            <div class="s-pill amber">
                <div class="pl">Pending</div>
                <div class="pv">Rp<?= number_format($total_pending, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <?php if ($flash): 
        [$type, $msg] = explode('|', $flash, 2); ?>
        <div class="flash <?= htmlspecialchars($type) ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Table heading -->
    <div class="section-head">
        <h2>Daftar Donasi (<?= count($donations) ?>)</h2>
        <?php if ($count_pending > 0): ?>
            <span class="pending-badge"><?= $count_pending ?> menunggu verifikasi</span>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Donatur</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Bukti</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($donations)): ?>
                    <tr class="empty-row"><td colspan="7">Belum ada donasi untuk campaign ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($donations as $i => $d): ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:.78rem;"><?= $i + 1 ?></td>
                        <td>
                            <div class="donor-cell">
                                <div class="d-name"><?= htmlspecialchars($d['donor_name']) ?></div>
                                <div class="d-email"><?= htmlspecialchars($d['donor_email']) ?></div>
                            </div>
                        </td>
                        <td class="amount-cell">Rp<?= number_format($d['amount'], 0, ',', '.') ?></td>
                        <td>
                            <span class="status-badge status-<?= $d['status'] ?>">
                                <?= $d['status'] === 'verified' ? 'Terverifikasi' : ($d['status'] === 'pending' ? 'Pending' : 'Ditolak') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($d['proof_file'])): ?>
                                <a class="proof-link" href="uploads/<?= htmlspecialchars($d['proof_file']) ?>" target="_blank">Lihat Bukti</a>
                            <?php else: ?>
                                <span class="no-proof">Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        <td class="date-cell"><?= date('d M Y, H:i', strtotime($d['created_at'])) ?></td>
                        <td>
                            <?php if ($d['status'] === 'pending'): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="donation_id" value="<?= $d['id'] ?>">
                                    <div class="action-cell">
                                        <button type="submit" name="verify" class="btn-verify">Verifikasi</button>
                                        <button type="submit" name="reject" class="btn-reject"
                                                onclick="return confirm('Tolak donasi ini?')">Tolak</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <span style="font-size:.75rem;color:#cbd5e1;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="page-footer">Copyright &copy; 2026 Crowdfunding Sosial. All Rights Reserved</div>

</div>
</body>
</html>