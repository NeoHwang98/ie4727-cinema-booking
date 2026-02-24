<?php
require_once dirname(__DIR__) . '/inc/init.php';

function pick($a, $keys, $def=null){ foreach($keys as $k){ if(isset($a[$k]) && $a[$k] !== '' && $a[$k] !== null) return $a[$k]; } return $def; }
function dtf($v,$fmt){ if(!$v) return ''; $ts = is_numeric($v) ? (int)$v : strtotime((string)$v); if($ts===false) return (string)$v; return date($fmt,$ts); }

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($movieId <= 0) { header('Location: /cinema-test/index.php'); exit; }

$movie = get_movie($movieId);
$page_title = $movie ? ($movie['title'] ?? 'Movie') : 'Movie';

$shows = get_shows(null, null, $movieId);

$groups = [];
foreach ($shows as $s) {
  $dtRaw = pick($s, ['start_at','show_datetime','start_time','time','show_time']);
  $key = dtf($dtRaw, 'Y-m-d');
  if (!$key) continue;
  if (!isset($groups[$key])) {
    $groups[$key] = [
      'label' => dtf($dtRaw, 'l, d F Y'),
      'items' => []
    ];
  }
  $groups[$key]['items'][] = $s;
}
ksort($groups);

include dirname(__DIR__) . '/inc/header.php';
?>

<style>
  .movie-wrap { max-width: 1200px; margin: 0 auto; padding: 24px 16px 32px; }
  .movie-head { display: grid; grid-template-columns: 180px 1fr; gap: 18px; align-items: start; margin-bottom: 18px; }
  .movie-poster { width: 180px; height: 260px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border); background: #0c0f14; }
  .movie-title { font-size: 26px; font-weight: 800; margin: 4px 0 6px; color: var(--text); }
  .movie-meta { color: var(--text); font-size: 14px; margin-bottom: 6px; }
  .movie-syn { color: var(--text); }
  .section-title { font-size: 20px; font-weight: 800; margin: 22px 0 12px; color: var(--text); }
  .date-group { margin: 18px 0 14px; }
  .date-heading { font-size: 16px; font-weight: 700; color: var(--text); margin: 0 0 10px; }
  .time-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
  .time-card { display: block; text-decoration: none; color: inherit; background: #202734; color: #eef1f6; border-radius: 10px; padding: 12px 14px; border: 1px solid #303848; }
  .time-card:hover { border-color: #e4002b; text-decoration: none; }
  .time-top { font-weight: 800; font-size: 16px; margin-bottom: 6px; }
  .time-sub { font-size: 13px; color: #cad1db; }
  @media (max-width: 700px) {
    .movie-head { grid-template-columns: 1fr; }
    .movie-poster { width: 100%; height: auto; aspect-ratio: 3/4; }
  }
</style>

<div class="movie-wrap">
  <div class="movie-head">
    <img class="movie-poster" src="<?= h(pick($movie ?? [], ['poster_path','poster','image'], '/cinema-test/assets/img/placeholder.svg')) ?>" alt="<?= h(pick($movie ?? [], ['title','name'], 'Movie')) ?> poster">
    <div>
      <div class="movie-title"><?= h(pick($movie ?? [], ['title','name'], 'Movie')) ?></div>
      <?php $meta = [];
        $r = pick($movie ?? [], ['rating']); if ($r) $meta[] = h($r);
        $d = pick($movie ?? [], ['duration','duration_min','duration_mins','duration_minutes']); if ($d) $meta[] = h(((int)$d) . ' mins');
        $s = pick($movie ?? [], ['subs','subtitle','languages']); if ($s) $meta[] = 'Subs: ' . h($s);
      ?>
      <?php if ($meta): ?><div class="movie-meta"><?= implode(' &bull; ', $meta) ?></div><?php endif; ?>
      <?php if ($syn = pick($movie ?? [], ['synopsis','description'])): ?><div class="movie-syn"><?= nl2br(h($syn)) ?></div><?php endif; ?>
    </div>
  </div>

  <div class="section-title">Showtimes</div>

  <?php if (!$groups): ?>
    <div>No upcoming sessions.</div>
  <?php else: ?>
    <?php foreach ($groups as $ymd => $grp): ?>
      <div class="date-group">
        <div class="date-heading"><?= h($grp['label']) ?></div>
        <div class="time-grid">
          <?php foreach ($grp['items'] as $row):
            $dt = pick($row, ['start_at','show_datetime','start_time','time','show_time']);
            $sid = (int) pick($row, ['id','show_id','showid'], 0);
            $timeLabel = dtf($dt, 'g:i A');
            $screen = pick($row, ['screen_name','screen'], '');
            $price = (float) pick($row, ['base_price','price','ticket_price'], 0);
          ?>
          <a class="time-card" href="/cinema-test/pages/details.php?id=<?= $sid ?>">
            <div class="time-top"><?= h($timeLabel) ?></div>
            <div class="time-sub"><?php if ($screen) { echo h($screen) . ' &bull; '; } ?>$<?= number_format($price, 2) ?></div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/inc/footer.php'; ?>
