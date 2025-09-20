<?php
// adventure_log.php — view recent adventure results for the logged-in user


declare(strict_types=1);


session_start();
require_once __DIR__ . '/includes/db.php';


if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$userId = (int)$_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT r.*, l.name AS location_name FROM adventure_runs r
JOIN adventure_locations l ON l.id = r.location_id
WHERE r.user_id = :uid ORDER BY r.created_at DESC LIMIT 50");
$stmt->execute([':uid' => $userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Adventure Log</title>
<style>
body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:#0b0b0e; color:#f1f1f1; }
.wrap { max-width: 900px; margin: 2rem auto; padding: 1.5rem; background:#15161a; border-radius: 16px; box-shadow: 0 6px 30px rgba(0,0,0,.35); }
table { width:100%; border-collapse: collapse; }
th, td { padding:.6rem .7rem; border-bottom:1px solid #2a2e36; text-align:left; }
th { background:#0f1116; }
.muted { color:#a9b1bd; }
</style>
</head>
<body>
<div class="wrap">
<h1>Adventure Log</h1>
<table>
<thead>
<tr>
<th>Date</th>
<th>Pet</th>
<th>Location</th>
<th>Result</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= htmlspecialchars($r['created_at']) ?></td>
<td><?= htmlspecialchars($r['pet_name']) ?> (#<?= (int)$r['pet_id'] ?>)</td>
<td><?= htmlspecialchars($r['location_name']) ?></td>
<td>
<?php if ($r['result_type'] === 'simbucks'): ?>
Found <?= (int)$r['amount'] ?> Simbucks
<?php elseif ($r['result_type'] === 'item'): ?>
Found item: <?= htmlspecialchars($r['result_item'] ?? 'Unknown') ?>
<?php else: ?>
Found nothing
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php if (!$rows): ?><p class="muted">No adventures logged yet.</p><?php endif; ?>
</div>
</body>
</html>