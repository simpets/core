<?php
// /adventure/_helpers.php
require_once __DIR__ . '/../forum/_common.php'; // for db + session helpers

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
  // daily refill
  if ($row['stamina_refill'] < date('Y-m-d')) {
    $row['stamina_current'] = $row['stamina_max'];
    $row['stamina_refill'] = date('Y-m-d');
    $upd = $pdo->prepare("UPDATE pet_adventure_state SET stamina_current=?, stamina_refill=? WHERE pet_id=?");
    $upd->execute([$row['stamina_current'], $row['stamina_refill'], $pet_id]);
  }
  return $row;
}

function adv_list_zones(PDO $pdo, int $pet_level): array {
  $st = $pdo->prepare("SELECT id, name, min_level, stamina_cost FROM adventure_zones
                       WHERE is_active=1 AND min_level<=? ORDER BY sort_order, id");
  $st->execute([$pet_level]);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

/** Rolls a rarity, with pity: guarantee >= rare every N runs without a rare+ */
function adv_roll_rarity(PDO $pdo, int $pet_id, int $zone_id): string {
  // base weights (tweakable; higher zones bias better loot)
  $base = ['common'=>70,'uncommon'=>22,'rare'=>6,'epic'=>1.8,'legendary'=>0.2];
  if ($zone_id >= 2) { $base['common'] -= 10; $base['uncommon'] += 5; $base['rare'] += 4; }
  if ($zone_id >= 3) { $base['common'] -= 10; $base['rare'] += 6; $base['epic'] += 2; $base['legendary'] += 1; }

  // pity: 9 runs without rare+ -> force rare floor
  $st = $pdo->prepare("SELECT pity_rare_count FROM pet_adventure_state WHERE pet_id=?");
  $st->execute([$pet_id]);
  $pity = (int)$st->fetchColumn();
  $force_min_rare = ($pity >= 9);

  $rollTable = $base;
  if ($force_min_rare) { $rollTable['common']=0; $rollTable['uncommon']=0; }

  $sum = array_sum($rollTable);
  $r = mt_rand(1, (int)round($sum*1000));
  $acc = 0;
  foreach ($rollTable as $rar=>$w) {
    $acc += (int)round($w*1000);
    if ($r <= $acc) return $rar;
  }
  return 'common';
}

function adv_pick_item(PDO $pdo, int $zone_id, string $rarity): ?array {
  $st = $pdo->prepare("SELECT item_id, weight FROM adventure_zone_drops WHERE zone_id=? AND rarity=?");
  $st->execute([$zone_id, $rarity]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  if (!$rows) return null;
  $sum = 0; foreach ($rows as $r) $sum += (int)$r['weight'];
  $pick = mt_rand(1, $sum);
  $acc=0;
  foreach ($rows as $r) { $acc += (int)$r['weight']; if ($pick <= $acc) return $r; }
  return $rows[array_key_first($rows)];
}

function adv_spend_stamina(PDO $pdo, int $pet_id, int $cost): bool {
  $st = adv_get_state($pdo, $pet_id);
  if ($st['stamina_current'] < $cost) return false;
  $new = $st['stamina_current'] - $cost;
  $upd = $pdo->prepare("UPDATE pet_adventure_state SET stamina_current=? WHERE pet_id=?");
  $upd->execute([$new, $pet_id]);
  return true;
}

function adv_update_pity(PDO $pdo, int $pet_id, string $rarity): void {
  if (in_array($rarity, ['rare','epic','legendary'], true)) {
    $pdo->prepare("UPDATE pet_adventure_state SET pity_rare_count=0 WHERE pet_id=?")->execute([$pet_id]);
  } else {
    $pdo->prepare("UPDATE pet_adventure_state SET pity_rare_count=pity_rare_count+1 WHERE pet_id=?")->execute([$pet_id]);
  }
}