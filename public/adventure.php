<?php
// /adventure.php — site page (no forum header), stamina + zones + pity + items/inventory + inventory UI
session_start();
require_once __DIR__ . '/includes/db.php';

// --- minimal auth helpers ---
function current_user_id() { return $_SESSION['user_id'] ?? null; }
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_input(){ echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'; }
function csrf_ok($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }
function require_member() {
  if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
  }
}
require_member();

/* ---------- Friendly item-name fallbacks ---------- */
$ITEM_NAME_FALLBACKS = [
  // COMMON
  501 => 'Puppy Plush',
  502 => 'Kitty Plush',
  503 => 'Bunny Plush',
  504 => 'Teddy Toy',
  // UNCOMMON
  601 => 'Mature Potion',
  602 => 'Glow',
  603 => 'Rainbowed',
  604 => 'Mini Me',
  // RARE
  701 => 'Raven',
];

/* -----------------------------------------------------------
   DB Introspection helpers
------------------------------------------------------------*/
function db_table_exists(PDO $pdo, string $tbl): bool {
  try { $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tbl)); return (bool)$stmt->fetchColumn(); }
  catch (Throwable $e) { return false; }
}
function db_table_columns_full(PDO $pdo, string $tbl): array {
  try {
    $cols = [];
    $q = $pdo->query("SHOW COLUMNS FROM `$tbl`");
    if ($q) foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = $r;
    return $cols;
  } catch (Throwable $e) { return []; }
}
function db_table_columns(PDO $pdo, string $tbl): array {
  $raw = db_table_columns_full($pdo, $tbl);
  $out = [];
  foreach ($raw as $k=>$r) $out[$k] = true;
  return $out;
}

/* Map user_pets columns to canonical names: id, name, level, user_id */
function map_user_pets_schema(PDO $pdo): array {
  $tbl = 'user_pets';
  if (!db_table_exists($pdo, $tbl)) return ['error'=>"Table `$tbl` not found in current database."];
  $cols = array_keys(db_table_columns($pdo, $tbl)); // lowercased

  $pick = function(array $cands) use ($cols) {
    foreach ($cands as $c) if (in_array($c, $cols, true)) return $c;
    return null;
  };
  $id    = $pick(['id','pet_id','uid','pk','petid']);
  $name  = $pick(['name','pet_name','nickname','petname','display_name']);
  $level = $pick(['level','pet_level','lvl','petlvl','xp_level']);
  $uid   = $pick(['user_id','owner_id','uid','user','member_id','player_id']);

  $missing = [];
  if (!$id)    $missing[] = 'id';
  if (!$name)  $missing[] = 'name';
  if (!$level) $missing[] = 'level';
  if (!$uid)   $missing[] = 'user_id';

  if ($missing) {
    return ['error'=>"user_pets missing: ".implode(', ', $missing).". Found: ".implode(', ', $cols)];
  }
  return ['table'=>'user_pets','id'=>$id, 'name'=>$name, 'level'=>$level, 'user_id'=>$uid];
}

/* -----------------------------------------------------------
   Adventure systems
------------------------------------------------------------*/
function adv_get_state(PDO $pdo, int $pet_id): array {
  $st = $pdo->prepare("SELECT * FROM pet_adventure_state WHERE pet_id=?");
  $st->execute([$pet_id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    $pdo->prepare("INSERT INTO pet_adventure_state (pet_id) VALUES (?)")->execute([$pet_id]);
    $row = [
      'pet_id'=>$pet_id,'stamina_current'=>5,'stamina_max'=>5,
      'stamina_refill'=>date('Y-m-d'),'pity_rare_count'=>0
    ];
  }
  if ($row['stamina_refill'] < date('Y-m-d')) { // daily refill
    $row['stamina_current'] = $row['stamina_max'];
    $row['stamina_refill']  = date('Y-m-d');
    $pdo->prepare("UPDATE pet_adventure_state SET stamina_current=?, stamina_refill=? WHERE pet_id=?")
        ->execute([$row['stamina_current'], $row['stamina_refill'], $pet_id]);
  }
  return $row;
}

// Schema-adaptive: don't assume is_active/sort_order exist
function adv_list_zones(PDO $pdo): array {
  $cols = db_table_columns($pdo, 'adventure_zones');

  $select = ["id","name"];
  foreach (['min_level','stamina_cost'] as $c) { if (isset($cols[$c])) $select[] = $c; }
  $select_list = implode(',', $select);

  $where = isset($cols['is_active']) ? " WHERE is_active = 1 " : "";

  $orderPieces = [];
  if (isset($cols['sort_order'])) $orderPieces[] = "sort_order";
  $orderPieces[] = "id";
  $order = " ORDER BY " . implode(", ", $orderPieces);

  $sql = "SELECT $select_list FROM adventure_zones" . $where . $order;

  $st = $pdo->query($sql);
  return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
}

function adv_roll_rarity(PDO $pdo, int $pet_id, int $zone_id): string {
  $base = ['common'=>70,'uncommon'=>22,'rare'=>6,'epic'=>1.8,'legendary'=>0.2];
  if ($zone_id >= 2) { $base['common'] -= 10; $base['uncommon'] += 5; $base['rare'] += 4; }
  if ($zone_id >= 3) { $base['common'] -= 10; $base['rare'] += 6; $base['epic'] += 2; $base['legendary'] += 1; }

  $st = $pdo->prepare("SELECT pity_rare_count FROM pet_adventure_state WHERE pet_id=?");
  $st->execute([$pet_id]);
  $pity = (int)$st->fetchColumn();
  if ($pity >= 9) { $base['common']=0; $base['uncommon']=0; }

  $sum = array_sum($base);
  $roll = mt_rand(1, (int)round($sum * 1000));
  $acc = 0; foreach ($base as $rar=>$w) { $acc += (int)round($w*1000); if ($roll <= $acc) return $rar; }
  return 'common';
}

function adv_pick_item(PDO $pdo, int $zone_id, string $rarity): ?array {
  $st = $pdo->prepare("SELECT item_id, weight FROM adventure_zone_drops WHERE zone_id=? AND rarity=?");
  $st->execute([$zone_id, $rarity]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  if (!$rows) return null;
  $sum = 0; foreach ($rows as $r) $sum += (int)$r['weight'];
  $roll = mt_rand(1, $sum);
  $acc = 0; foreach ($rows as $r) { $acc += (int)$r['weight']; if ($roll <= $acc) return $r; }
  return $rows[0];
}

function adv_spend_stamina(PDO $pdo, int $pet_id, int $cost): bool {
  $st = adv_get_state($pdo, $pet_id);
  if ($st['stamina_current'] < $cost) return false;
  $pdo->prepare("UPDATE pet_adventure_state SET stamina_current=? WHERE pet_id=?")
      ->execute([$st['stamina_current'] - $cost, $pet_id]);
  return true;
}

function adv_update_pity(PDO $pdo, int $pet_id, string $rarity): void {
  if (in_array($rarity, ['rare','epic','legendary'], true)) {
    $pdo->prepare("UPDATE pet_adventure_state SET pity_rare_count=0 WHERE pet_id=?")->execute([$pet_id]);
  } else {
    $pdo->prepare("UPDATE pet_adventure_state SET pity_rare_count=pity_rare_count+1 WHERE pet_id=?")->execute([$pet_id]);
  }
}

/* ---------- Items + Inventory helpers ---------- */
function map_items_schema(PDO $pdo): ?array {
  foreach (['items','shop_items','game_items'] as $tbl) {
    try {
      $q = $pdo->query("SHOW COLUMNS FROM `$tbl`");
      if (!$q) continue;
      $cols = [];
      foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;
      $idCol = null; foreach (['id','item_id'] as $c) if (isset($cols[$c])) { $idCol = $c; break; }
      $nameCol = null; foreach (['name','title','display_name','item_name','label'] as $c) if (isset($cols[$c])) { $nameCol = $c; break; }
      if ($idCol && $nameCol) return ['table'=>$tbl,'id'=>$idCol,'name'=>$nameCol];
    } catch (Throwable $e) {}
  }
  return null;
}

function get_item_name(PDO $pdo, int $item_id): ?string {
  static $cache = [];
  if (isset($cache[$item_id])) return $cache[$item_id];

  $tables = ['items','shop_items','game_items'];
  $idCols = ['id','item_id'];
  $nameCols = ['name','title','display_name','item_name','label'];

  foreach ($tables as $tbl) {
    try {
      $colsStmt = $pdo->query("SHOW COLUMNS FROM `$tbl`");
      if (!$colsStmt) continue;
      $cols = [];
      foreach ($colsStmt->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;

      $idCol = null; foreach ($idCols as $c) if (isset($cols[$c])) { $idCol = $c; break; }
      $nmCol = null; foreach ($nameCols as $c) if (isset($cols[$c])) { $nmCol = $c; break; }
      if (!$idCol || !$nmCol) continue;

      $st = $pdo->prepare("SELECT `$nmCol` AS name FROM `$tbl` WHERE `$idCol` = ?");
      $st->execute([$item_id]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!empty($row['name'])) return $cache[$item_id] = (string)$row['name'];
    } catch (Throwable $e) {}
  }

  global $ITEM_NAME_FALLBACKS;
  if (isset($ITEM_NAME_FALLBACKS[$item_id])) return $cache[$item_id] = $ITEM_NAME_FALLBACKS[$item_id];

  return null;
}

/* ---------- NEW: Resolve alias item ids → canonical catalog ids ---------- */
function resolve_item_id(PDO $pdo, int $maybeId): int {
  // 1) If id already exists in a known items table, keep it
  $tables = ['items','shop_items','game_items'];
  foreach ($tables as $tbl) {
    try {
      $st = $pdo->prepare("SHOW COLUMNS FROM `$tbl`");
      if (!$st) continue;
      $cols = [];
      foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;
      if (!isset($cols['id']) && !isset($cols['item_id'])) continue;
      $idCol = isset($cols['id']) ? 'id' : 'item_id';

      $chk = $pdo->prepare("SELECT 1 FROM `$tbl` WHERE `$idCol` = ? LIMIT 1");
      $chk->execute([$maybeId]);
      if ($chk->fetchColumn()) return $maybeId;
    } catch (Throwable $e) { /* try next */ }
  }

  // 2) Map alias -> name (from fallbacks), then look up canonical id by name
  global $ITEM_NAME_FALLBACKS;
  $name = $ITEM_NAME_FALLBACKS[$maybeId] ?? null;
  if ($name) {
    foreach ($tables as $tbl) {
      try {
        $st = $pdo->query("SHOW COLUMNS FROM `$tbl`");
        if (!$st) continue;
        $cols = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;

        $idCol  = isset($cols['id']) ? 'id' : (isset($cols['item_id']) ? 'item_id' : null);
        $nameCol= null;
        foreach (['name','title','display_name','item_name','label'] as $c) if (isset($cols[$c])) { $nameCol=$c; break; }
        if (!$idCol || !$nameCol) continue;

        $q = $pdo->prepare("SELECT `$idCol` AS id FROM `$tbl` WHERE `$nameCol` = ? LIMIT 1");
        $q->execute([$name]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!empty($row['id'])) return (int)$row['id'];
      } catch (Throwable $e) { /* try next */ }
    }
  }

  // 3) Could not resolve — return original id
  return $maybeId;
}

/* ---------- Smarter inventory detector ---------- */
function map_inventory_schema(PDO $pdo): ?array {
  // Quick known patterns
  $quick = [
    ['user_inventory','user_id','item_id',['qty','quantity','count','amount','stock']],
    ['inventory','user_id','item_id',['qty','quantity','count','amount','stock']],
    ['user_items','user_id','item_id',['quantity','qty','count','amount','stock']],
    ['items_owned','user_id','item_id',['qty','quantity','count','amount','stock']],
    ['owned_items','user_id','item_id',['qty','quantity','count','amount','stock']],
  ];
  foreach ($quick as [$tbl,$u,$i,$qs]) {
    try {
      $q1 = $pdo->query("SHOW COLUMNS FROM `$tbl`");
      if (!$q1) continue;
      $cols = [];
      foreach ($q1->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;
      if (!isset($cols[$u]) || !isset($cols[$i])) continue;
      foreach ($qs as $qcol) if (isset($cols[$qcol])) {
        return ['table'=>$tbl,'user'=>$u,'item'=>$i,'qty'=>$qcol];
      }
    } catch (Throwable $e) {}
  }

  // Generic scan
  try { $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) { return null; }
  if (!$tables) return null;

  $userCandidates = ['user_id','userid','member_id','player_id','account_id','owner_id','uid','user'];
  $itemCandidates = ['item_id','iid','object_id','product_id','thing_id'];
  $qtyCandidates  = ['qty','quantity','count','amount','stock','num','total'];

  foreach ($tables as $tbl) {
    try {
      $q1 = $pdo->query("SHOW COLUMNS FROM `$tbl`");
      if (!$q1) continue;
      $cols = [];
      foreach ($q1->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;

      $u = null; foreach ($userCandidates as $c) if (isset($cols[$c])) { $u = $c; break; }
      $i = null; foreach ($itemCandidates as $c) if (isset($cols[$c])) { $i = $c; break; }
      if (!$u || !$i) continue;

      $q = null; foreach ($qtyCandidates as $c) if (isset($cols[$c])) { $q = $c; break; }
      if (!$q) continue;

      return ['table'=>$tbl,'user'=>$u,'item'=>$i,'qty'=>$q];
    } catch (Throwable $e) {}
  }
  return null;
}

/* ---------- Robust grant_item with manual upsert fallback ---------- */
function grant_item(PDO $pdo, int $user_id, int $item_id, int $qty): bool {
  $map = map_inventory_schema($pdo);
  if (!$map) {
    error_log("[adventure] No inventory table detected; cannot grant item_id=$item_id to user_id=$user_id");
    return false;
  }

  $tbl = $map['table']; $u = $map['user']; $i = $map['item']; $q = $map['qty'];

  // Try ON DUPLICATE
  try {
    $sql = "INSERT INTO `$tbl` (`$u`,`$i`,`$q`) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE `$q` = `$q` + VALUES(`$q`)";
    $ok = $pdo->prepare($sql)->execute([$user_id,$item_id,$qty]);
    if ($ok) return true;
  } catch (Throwable $e) {
    // Fall through
  }

  // Manual upsert
  try {
    $sel = $pdo->prepare("SELECT `$q` FROM `$tbl` WHERE `$u`=? AND `$i`=? LIMIT 1");
    $sel->execute([$user_id,$item_id]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $upd = $pdo->prepare("UPDATE `$tbl` SET `$q` = `$q` + ? WHERE `$u`=? AND `$i`=?");
      return $upd->execute([$qty,$user_id,$item_id]);
    } else {
      $ins = $pdo->prepare("INSERT INTO `$tbl` (`$u`,`$i`,`$q`) VALUES (?,?,?)");
      return $ins->execute([$user_id,$item_id,$qty]);
    }
  } catch (Throwable $e) {
    error_log("[adventure] grant_item failed: ".$e->getMessage());
    return false;
  }
}

function grant_simbucks(PDO $pdo, int $user_id, int $amount): bool {
  try {
    $q = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'simbucks'");
    if (!$q || !$q->fetch()) return false;
    return $pdo->prepare("UPDATE users SET simbucks = simbucks + ? WHERE id = ?")->execute([$amount, $user_id]);
  } catch (Throwable $e) { return false; }
}

/* ---------- Robust: ensure FK-safe location row exists ---------- */
function ensure_location_id(PDO $pdo, int $zone_id): ?int {
  try {
    $q = $pdo->prepare("SELECT id FROM adventure_locations WHERE id = ?");
    $q->execute([$zone_id]);
    $id = $q->fetchColumn();
    if ($id) return (int)$id;
  } catch (Throwable $e) {}

  $zoneName = null;
  try {
    $qz = $pdo->prepare("SELECT name FROM adventure_zones WHERE id = ?");
    $qz->execute([$zone_id]);
    $zoneName = $qz->fetchColumn() ?: ('Zone '.$zone_id);
  } catch (Throwable $e) {
    $zoneName = 'Zone '.$zone_id;
  }

  $cols = db_table_columns_full($pdo, 'adventure_locations');
  if (!$cols) return null;

  $insertCols = [];
  $values = [];
  $exprCols = [];

  if (isset($cols['id'])) { $insertCols[] = 'id'; $values[] = $zone_id; }
  foreach (['name','title','label'] as $c) if (isset($cols[$c])) { $insertCols[] = $c; $values[] = $zoneName; break; }

  foreach ($cols as $cname => $meta) {
    if (in_array($cname, ['id','name','title','label'], true)) continue;
    $notNull = (strtoupper((string)$meta['Null']) === 'NO');
    $hasDefault = ($meta['Default'] !== null);
    $auto = stripos((string)$meta['Extra'], 'auto_increment') !== false;
    if (!$notNull || $hasDefault || $auto) continue;

    $type = strtolower((string)$meta['Type']);
    if (strpos($type,'int') !== false || strpos($type,'decimal') !== false || strpos($type,'float') !== false || strpos($type,'double') !== false) {
      $insertCols[] = $cname; $values[] = 0;
    } elseif (strpos($type,'datetime') !== false || strpos($type,'timestamp') !== false) {
      $insertCols[] = $cname; $exprCols[$cname] = 'NOW()';
    } elseif ($type === 'date') {
      $insertCols[] = $cname; $exprCols[$cname] = 'CURDATE()';
    } elseif (preg_match('/^enum\((.+)\)$/', $type, $m)) {
      $opts = str_getcsv($m[1], ',', "'");
      $choice = $opts[0] ?? '';
      $insertCols[] = $cname; $values[] = $choice;
    } else {
      $insertCols[] = $cname; $values[] = '';
    }
  }

  $valsSql = [];
  foreach ($insertCols as $c) $valsSql[] = isset($exprCols[$c]) ? $exprCols[$c] : '?';

  try {
    $sql = "INSERT INTO adventure_locations (".implode(',', $insertCols).") VALUES (".implode(',', $valsSql).")
            ON DUPLICATE KEY UPDATE id = VALUES(id)";
    $st = $pdo->prepare($sql);
    $st->execute($values);
    return (int)$zone_id;
  } catch (Throwable $e) {
    return null;
  }
}

/* ---------- Run logger ---------- */
function adv_log_run(PDO $pdo, array $data): void {
  $cols_have = db_table_columns($pdo, 'adventure_runs');

  $zone_id = isset($data['zone_id']) ? (int)$data['zone_id'] : null;
  $location_id = null;
  if (isset($cols_have['location_id']) && $zone_id !== null) {
    $location_id = ensure_location_id($pdo, $zone_id);
  }

  $cand = [
    'pet_id'          => $data['pet_id'] ?? null,
    'zone_id'         => $zone_id,
    'location_id'     => $location_id,
    'awarded_item_id' => $data['awarded_item_id'] ?? null,
    'simbucks'        => $data['simbucks'] ?? 0,
    'rarity_awarded'  => $data['rarity'] ?? null,
    'is_crit'         => $data['is_crit'] ?? 0,
    'rewards_json'    => $data['rewards_json'] ?? null,
  ];

  $cols = []; $ph=[]; $vals=[];
  foreach ($cand as $k=>$v) {
    if (!isset($cols_have[$k])) continue;
    if ($k === 'location_id' && $v === null) continue;
    $cols[]=$k; $ph[]='?'; $vals[]=$v;
  }
  if (empty($cols)) return;
  $use_created_at = isset($cols_have['created_at']);
  $sql = "INSERT INTO adventure_runs (".implode(',', $cols).($use_created_at?",created_at":"").") VALUES (".implode(',', $ph).($use_created_at?",NOW()":"").")";
  $pdo->prepare($sql)->execute($vals);
}

/* ---------- Inventory fetch for UI ---------- */
function fetch_inventory(PDO $pdo, int $user_id, int $limit = 100): array {
  $map = map_inventory_schema($pdo);
  if (!$map) return ['items'=>[], 'error'=>'Inventory table not detected.'];
  $tbl = $map['table']; $u = $map['user']; $i = $map['item']; $q = $map['qty'];

  try {
    $sql = "SELECT `$i` AS item_id, SUM(`$q`) AS qty
            FROM `$tbl`
            WHERE `$u` = ?
            GROUP BY `$i`
            HAVING SUM(`$q`) > 0
            ORDER BY qty DESC, item_id ASC
            LIMIT ".(int)$limit;
    $st = $pdo->prepare($sql);
    $st->execute([$user_id]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    return ['items'=>$rows, 'error'=>null];
  } catch (Throwable $e) {
    error_log('[adventure] fetch_inventory failed: '.$e->getMessage());
    return ['items'=>[], 'error'=>'Failed to read inventory.'];
  }
}

/* -----------------------------------------------------------
   Load pets & zones
------------------------------------------------------------*/
$uid = (int)(current_user_id() ?? 0);
$pets = []; $pets_error = '';
$map = map_user_pets_schema($pdo);
if (!empty($map['error'])) {
  $pets_error = $map['error'];
} else {
  $sql = "SELECT `{$map['id']}` AS id, `{$map['name']}` AS name, `{$map['level']}` AS level, `{$map['user_id']}` AS user_id
          FROM `{$map['table']}` WHERE `{$map['user_id']}` = :uid ORDER BY `{$map['name']}`";
  $ps = $pdo->prepare($sql); $ps->execute([':uid'=>$uid]); $pets = $ps->fetchAll(PDO::FETCH_ASSOC);
}
$zones = adv_list_zones($pdo);

/* -----------------------------------------------------------
   Handle POST (run adventure)
------------------------------------------------------------*/
$errors = []; $success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $errors[] = "Security token invalid. Please reload.";
  } else {
    $pet_id  = (int)($_POST['pet_id'] ?? 0);
    $zone_id = (int)($_POST['zone_id'] ?? 0);

    $p = null; foreach ($pets as $row) { if ((int)$row['id'] === $pet_id) { $p = $row; break; } }
    if (!$p) $errors[] = "Please choose a valid pet.";
    $z = null; foreach ($zones as $row) { if ((int)$row['id'] === $zone_id) { $z = $row; break; } }
    if (!$z) $errors[] = "Please choose a valid zone.";

    if (!$errors) {
      $pet_level = (int)($p['level'] ?? 1);
      if ($pet_level < (int)$z['min_level']) {
        $errors[] = "This pet does not meet the level requirement for that zone.";
      } else {
        $state = adv_get_state($pdo, $pet_id);
        $cost  = (int)$z['stamina_cost'];
        if (!adv_spend_stamina($pdo, $pet_id, $cost)) {
          $errors[] = "Not enough stamina for this adventure.";
        } else {
          $rarity = adv_roll_rarity($pdo, $pet_id, $zone_id);

          $critChance = 7 + (($pet_level >= 3) ? 3 : 0);
          $isCrit = (mt_rand(1,100) <= $critChance) ? 1 : 0;
          if ($pet_level >= 3 && $rarity === 'common') {
            if (mt_rand(1,100) <= 25) $rarity = 'uncommon';
          }

          $drop   = adv_pick_item($pdo, $zone_id, $rarity); // may be null
          $awarded_item_id = $drop['item_id'] ?? null;
          $simbucks = 0; $item_name = null; $granted = null;

          if (!$awarded_item_id) {
            $base = 5 + $zone_id*2 + (int)floor($pet_level/5)*2;
            $simbucks = $isCrit ? $base*2 : $base;
            if ($pet_level >= 3) $simbucks = (int)round($simbucks * 1.10);
            grant_simbucks($pdo, $uid, $simbucks);
          } else {
            $qty = $isCrit ? 2 : 1;

            // NEW: translate alias ids (e.g., 501) to your real items.id
            $canonical_item_id = resolve_item_id($pdo, (int)$awarded_item_id);

            $granted = grant_item($pdo, $uid, $canonical_item_id, $qty);
            // Prefer canonical name; fallback to alias-name if needed
            $item_name = get_item_name($pdo, $canonical_item_id);
            if (!$item_name) $item_name = get_item_name($pdo, (int)$awarded_item_id);

            // For logging, store the canonical id so reports match inventory
            $awarded_item_id = $canonical_item_id;
          }

          // Log
          adv_log_run($pdo, [
            'pet_id'        => $pet_id,
            'zone_id'       => $zone_id,
            'awarded_item_id'=> $awarded_item_id,
            'simbucks'      => $simbucks,
            'rarity'        => $rarity,
            'is_crit'       => $isCrit,
            'rewards_json'  => json_encode(['item_id'=>$awarded_item_id,'simbucks'=>$simbucks,'crit'=>$isCrit], JSON_UNESCAPED_UNICODE),
          ]);

          // Message
          $zoneName = ''; foreach ($zones as $row) if ((int)$row['id']===$zone_id) { $zoneName=$row['name']; break; }
          if ($awarded_item_id) {
            $display = $item_name ? $item_name : ('item #'.(int)$awarded_item_id);
            $suffix  = $isCrit ? ' — Critical haul! ×2' : '';
            $invNote = ($granted === true) ? ' — added to your inventory!' : ' — (inventory not configured)';
            $success = ['message' => sprintf('%s explored %s and found %s (%s)%s%s.',
                        $p['name'], $zoneName, $display, $rarity, $suffix, $invNote)];
          } else {
            $suffix  = $isCrit ? ' — Critical bonus!' : '';
            $success = ['message' => sprintf('%s explored %s and earned +%d Simbucks%s.',
                        $p['name'], $zoneName, (int)$simbucks, $suffix)];
          }

          adv_update_pity($pdo, $pet_id, $rarity);
        }
      }
    }
  }
}

/* -----------------------------------------------------------
   Page (no forum header/footer)
------------------------------------------------------------*/
include __DIR__ . '/menu.php'; // site-wide header/menu
?>
<div class="container" style="max-width: 1000px; margin: 24px auto;">
  <h1>Adventure</h1>
<head><title>The Adventure!</title></head>
  <style>
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap:1rem; }
    .card { padding:1rem; border:1px solid #ddd; border-radius:12px; background:#fff; }
    .muted { color:#666; font-size:.95rem; }
    .error { background:#fde8e8; border:1px solid #f5b5b5; padding:.75rem 1rem; border-radius:10px; color:#8a1f1f; }
    .success{ background:#ebf9f1; border:1px solid #a8e5c7; padding:.75rem 1rem; border-radius:10px; color:#145a32; }
    label { font-weight: 600; }
    select, button { padding: .6rem .8rem; border-radius: 10px; border: 1px solid #ccc; background:#fff; }
    button { cursor: pointer; background: #3b82f6; color:#fff; border:0; font-weight: 700; }
    button:disabled { opacity:.6; cursor:not-allowed; }
    .bar { height:10px; background:#eee; border-radius:8px; overflow:hidden; }
    .bar > div{ height:10px; background:#22c55e; }
    .pill { display:inline-block; padding:2px 8px; border-radius:999px; background:#f1f5f9; border:1px solid #cbd5e1; font-size:12px; margin-left:6px }
    code { background:#f8fafc; padding:2px 6px; border-radius:6px; border:1px solid #e2e8f0; }
    .tiny { color:#888; font-size:12px; }
  </style>

  <?php if (!empty($pets_error)): ?>
    <div class="error"><?= e($pets_error) ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="error">
      <strong>Heads up:</strong>
      <ul style="margin:.5rem 0 .25rem .9rem;">
        <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="success"><strong>Result:</strong> <?= e($success['message']) ?></div>
  <?php endif; ?>

  <?php
    $state_preview = null;
    if (!empty($pets)) $state_preview = adv_get_state($pdo, (int)$pets[0]['id']);
  ?>

  <div class="card" style="margin-bottom:1rem;">
    <p class="muted" style="margin:.25rem 0 1rem;">
      Send a pet to explore a zone and see what they bring back! Each pet has a limited <strong>stamina</strong> that refills daily.
    </p>

    <form method="post" autocomplete="off">
      <?php csrf_input(); ?>
      <div class="grid">
        <div class="card" style="background:#fafafa;">
          <label for="pet">Choose a Pet</label><br>
          <select name="pet_id" id="pet" required>
            <option value="">— Select your pet —</option>
            <?php foreach ($pets as $p): ?>
              <?php $st = adv_get_state($pdo, (int)$p['id']); ?>
              <option value="<?= (int)$p['id'] ?>">
                <?= e($p['name']) ?> (Lv <?= (int)$p['level'] ?><?= ((int)$p['level'] >= 3 ? ' • L3 perks' : '') ?>)
                — stamina <?= (int)$st['stamina_current'] ?>/<?= (int)$st['stamina_max'] ?>, pity <?= (int)$st['pity_rare_count'] ?>/9
              </option>
            <?php endforeach; ?>
          </select>
          <div class="muted" style="margin-top:.5rem;">Stamina refills daily on first visit.</div>
        </div>

        <div class="card" style="background:#fafafa%;">
          <label for="zone">Choose a Zone</label><br>
          <select name="zone_id" id="zone" required>
            <option value="">— Select a zone —</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= (int)$z['id'] ?>">
                <?= e($z['name']) ?> — min Lv <?= (int)$z['min_level'] ?> — cost <?= (int)$z['stamina_cost'] ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="muted" style="margin-top:.5rem;">Higher zones increase rare/epic chances but may cost more stamina.</div>
        </div>
      </div>

      <div style="margin-top:1rem;">
        <button type="submit" <?= empty($pets) ? 'disabled' : '' ?>>Adventure!</button>
      </div>
    </form>
  </div>

  <?php if ($state_preview): ?>
    <?php $s = $state_preview; $pct = $s['stamina_max']>0 ? max(0,min(100, round(100*$s['stamina_current']/$s['stamina_max']))):0; ?>
    <div class="card" style="margin-bottom:1rem;">
      <div><strong>Stamina preview (first pet):</strong>
        <span class="pill"><?= (int)$s['stamina_current'] ?>/<?= (int)$s['stamina_max'] ?></span>
        <span class="pill">Pity: <?= (int)$s['pity_rare_count'] ?>/9</span>
      </div>
      <div class="bar" style="margin:6px 0 0;"><div style="width: <?= $pct ?>%"></div></div>
    </div>
  <?php endif; ?>

  <?php
    // Inventory panel for the current user
    $inv = fetch_inventory($pdo, $uid, 200);
  ?>
  <div class="card">
    <h3 style="margin:0 0 .5rem 0;">My Inventory</h3>
    <?php if ($inv['error']): ?>
      <div class="muted">Inventory not configured: <?= e($inv['error']) ?></div>
    <?php elseif (empty($inv['items'])): ?>
      <div class="muted">No items yet. Go on an adventure!</div>
    <?php else: ?>
      <ul style="margin:.25rem 0 0 1rem;">
        <?php foreach ($inv['items'] as $row):
          $iid = (int)$row['item_id'];
          $qty = (int)$row['qty'];
          $nm  = get_item_name($pdo, $iid) ?: ("Item #".$iid);
        ?>
          <li><?= e($nm) ?> <span class="pill">×<?= $qty ?></span>
            <span class="tiny">(#<?= $iid ?>)</span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div class="muted tiny" style="margin-top:.5rem;">(This uses automatic schema detection for your inventory table.)</div>
  </div>

  <p class="muted" style="margin:1rem 0 0;">
    Tip: manage zone drops in <code>adventure_zone_drops</code> (weights &amp; rarities).
  </p>
</div>

<?php include __DIR__ . '/footer.php'; ?>