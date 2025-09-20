<?php
require_once __DIR__ . '/_common.php';



require_member();




/* ---------- schema helpers ---------- */
function table_cols(PDO $pdo, string $table): array {
  $cols=[]; $q=$pdo->query("SHOW COLUMNS FROM {$table}");
  if ($q) foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])]=true;
  return $cols;
}
function has_col(array $cols,string $c):bool{ return isset($cols[strtolower($c)]); }

/* detect schema bits */
$pcols = table_cols($pdo,'posts');
$authorCol = null;
foreach (['author','poster','username'] as $c) if (has_col($pcols,$c)) { $authorCol=$c; break; }
if (!$authorCol) $authorCol='author';
$tsCol = null;
foreach (['created_at','post_time','posted_at','created'] as $c) if (has_col($pcols,$c)) { $tsCol=$c; break; }
if (!$tsCol) $tsCol='created_at';

$tcols = table_cols($pdo,'threads');
$titleCol = has_col($tcols,'title') ? 'title' : (has_col($tcols,'subject')?'subject':'title');
$lastCol = has_col($tcols,'last_post_at') ? 'last_post_at' : (has_col($tcols,'created_at')?'created_at':null);

/* fetch forums */
$all=$pdo->query("SELECT id,name,parent_id,is_container,admin_only FROM forums ORDER BY parent_id IS NULL DESC,name DESC")->fetchAll(PDO::FETCH_ASSOC);
$cats=[];$children=[];
foreach($all as $f){ if(!empty($f['is_container']))$cats[]=$f; else $children[]=$f; }
$byParent=[];
foreach($children as $ch){ $byParent[(int)($ch['parent_id']??0)][]=$ch; }

/* meta for latest thread in forum */
function forum_latest(PDO $pdo,int $fid,string $titleCol,?string $lastCol,string $tsCol,string $authorCol):?array{
  $order=$lastCol?"ORDER BY t.$lastCol DESC":"ORDER BY t.id DESC";
  $t=$pdo->prepare("SELECT t.id,t.$titleCol AS title FROM threads t WHERE t.forum_id=? $order LIMIT 1");
  $t->execute([$fid]); $th=$t->fetch(PDO::FETCH_ASSOC);
  if(!$th) return null; $tid=(int)$th['id'];

  $first=$pdo->prepare("SELECT $authorCol AS a,$tsCol AS ts FROM posts WHERE thread_id=:tid OR topic_id=:tid ORDER BY $tsCol ASC,id ASC LIMIT 1");
  $first->execute([':tid'=>$tid]); $f=$first->fetch(PDO::FETCH_ASSOC);

  $last=$pdo->prepare("SELECT $authorCol AS a,$tsCol AS ts FROM posts WHERE thread_id=:tid OR topic_id=:tid ORDER BY $tsCol DESC,id DESC LIMIT 1");
  $last->execute([':tid'=>$tid]); $l=$last->fetch(PDO::FETCH_ASSOC);

  $cnt=$pdo->prepare("SELECT COUNT(*) FROM posts WHERE thread_id=:tid OR topic_id=:tid");
  $cnt->execute([':tid'=>$tid]); $posts=(int)$cnt->fetchColumn(); $replies=max(0,$posts-1);

  return [
    'id'=>$tid,'title'=>$th['title'],
    'starter'=>$f['a']??'','started'=>$f['ts']??'',
    'last_by'=>$l['a']??'','last_at'=>$l['ts']??'',
    'replies'=>$replies
  ];
}

forum_header('The Forums');


?>



<style>
  .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
  .muted{color:#666}
  table.forums{width:100%;border-collapse:collapse;margin-top:10px}
  th,td{padding:8px;border-bottom:1px solid #e3e3e3;text-align:left}
  th{background:#f9f9f9;font-weight:600}
  .nowrap{white-space:nowrap}
  .right{text-align:right}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #aaa;background:#fafafa;font-size:12px;margin-left:6px}
  .dim{color:#888;font-size:90%}
</style>



<?php if (empty($cats)&&empty($children)): ?>
  <div class="card">No forums yet.</div>
<?php endif; ?>

<?php foreach($cats as $cat): if(!can_view_forum($cat)) continue; ?>
  <div class="card">
    <h2 style="margin:0 0 8px;">
      <?= e($cat['name']) ?>
      <?php if(!empty($cat['admin_only'])):?><span class="pill">Staff</span><?php endif; ?>
    </h2>
    <?php $subs=$byParent[(int)$cat['id']]??[]; ?>
    <?php if(empty($subs)): ?>
      <div class="muted">No subforums yet.</div>
    <?php else: ?>
      <table class="forums">
        <thead>
          <tr>
            <th>Forum</th>
            <th>Latest Thread</th>
            <th class="right">Replies</th>
            <th>Last Reply</th>
            <th class="nowrap">When</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($subs as $sf): if(!can_view_forum($sf)) continue;
          $meta=forum_latest($pdo,(int)$sf['id'],$titleCol,$lastCol,$tsCol,$authorCol);
        ?>
          <tr>
            <td>
              <a href="/forum/forum.php?id=<?= (int)$sf['id'] ?>"><?= e($sf['name']) ?></a>
              <?php if(!empty($sf['admin_only'])):?><span class="pill">Staff</span><?php endif; ?>
            </td>
            <?php if($meta): ?>
              <td>
                <a href="/forum/thread.php?id=<?= (int)$meta['id'] ?>"><?= e($meta['title']) ?></a><br>
                <span class="dim">by <?= e($meta['starter']?:'—') ?> • <?= e($meta['started']?:'') ?></span>
              </td>
              <td class="right"><?= (int)$meta['replies'] ?></td>
              <td><?= e($meta['last_by']?:'—') ?></td>
              <td class="nowrap"><?= e($meta['last_at']?:'') ?></td>
            <?php else: ?>
              <td colspan="4"><span class="dim">No threads yet</span></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php forum_footer(); ?>