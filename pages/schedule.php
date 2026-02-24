<?php $page_title = 'Schedule'; require_once __DIR__ . '/../inc/init.php'; include __DIR__ . '/../inc/header.php'; ?>

<h1>Schedule</h1>

<form class="filters" method="get" action="">
  <div>
    <div class="small muted">Date</div>
    <?php
      $today = new DateTime('today');
      $selDate = isset($_GET['date']) ? DateTime::createFromFormat('Y-m-d', $_GET['date']) : $today;
      if (!$selDate) $selDate = $today;
      for ($i=0; $i<7; $i++) {
        $d = (clone $today)->modify("+{$i} day");
        $isSel = $d->format('Y-m-d') === $selDate->format('Y-m-d');
        $label = ($i === 0) ? 'Today' : (($i === 1) ? 'Tomorrow' : $d->format('d/m/Y'));
        echo '<a class="chip'.($isSel?' active':'').'" href="?date='.$d->format('Y-m-d').'">'.$label.'</a> ';
      }
    ?>
  </div>
  <div>
    <div class="small muted">Time</div>
    <?php $selTime = isset($_GET['time']) ? $_GET['time'] : ''; ?>
    <select name="time">
      <option value="">Any time</option>
      <?php for ($h=0;$h<24;$h++){ for($m=0;$m<60;$m+=30){ $t=sprintf('%02d:%02d',$h,$m); echo '<option value="'.$t.'"'.($selTime===$t?' selected':'').'>'.$t.'</option>'; }} ?>
    </select>
  </div>
  <div class="action-buttons">
    <button type="submit" class="btn">Apply</button>
    <a class="btn btn-secondary" href="/cinema-test/pages/schedule.php">Reset</a>
  </div>
  <input type="hidden" name="date" value="<?php echo h($selDate->format('Y-m-d')); ?>">
  <p class="muted small">Select a date to view all sessions for that day. Choose a time and click Apply to filter to sessions within ±3 hours.</p>
</form>

<?php
  $date = $selDate->format('Y-m-d');
  $shows = get_shows(null, $date, null);
  // Optional time filter ±3 hours
  if (!empty($selTime)) {
    $target = DateTime::createFromFormat('Y-m-d H:i', $date.' '.$selTime);
    if ($target) {
      $shows = array_values(array_filter($shows, function($s) use ($target) {
        $st = new DateTime($s['start_at']);
        $diff = abs($st->getTimestamp() - $target->getTimestamp())/60; // minutes
        return $diff <= 180; // within ±3 hours
      }));
    }
  }
?>

<?php
  // Group shows by movie for the chosen date
  $byMovie = [];
  foreach ($shows as $row) { $byMovie[$row['movie_id']][] = $row; }
?>

<div class="movie-schedule-list">
  <?php foreach ($byMovie as $mid => $list): $movie = get_movie((int)$mid); $poster = (!empty($movie['poster_path']) ? $movie['poster_path'] : '/cinema-test/assets/img/placeholder.svg'); ?>
    <article class="movie-schedule">
      <div class="ms-left">
        <img src="<?php echo h($poster); ?>" alt="<?php echo h($movie['title']); ?> poster">
      </div>
      <div class="ms-right">
        <h2><?php echo h($movie['title']); ?></h2>
        <div class="muted small"><?php echo (int)$movie['duration_min']; ?> min</div>
        <?php if (!empty($movie['synopsis'])): ?><p class="ms-synopsis"><?php echo h($movie['synopsis']); ?></p><?php endif; ?>
        <div class="time-grid">
          <?php foreach ($list as $s): ?>
            <a class="time-card" href="/cinema-test/pages/details.php?id=<?php echo (int)$s['id']; ?>">
              <div class="time"><?php echo date('g:i A', strtotime($s['start_at'])); ?></div>
              <div class="sub"><?php echo h($s['screen_name']); ?> • $<?php echo number_format((float)$s['base_price'],2); ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (empty($byMovie)): ?>
    <p class="muted">No sessions found for this date/time filter. Try a different time.</p>
  <?php endif; ?>
</div>

<style>
.movie-schedule-list { display: flex; flex-direction: column; gap: 18px; }
.movie-schedule { display: grid; grid-template-columns: 160px 1fr; gap: 16px; padding: 14px; border:1px solid var(--border); background: var(--card); border-radius: 12px; }
.movie-schedule .ms-left img { width: 160px; height: 230px; object-fit: cover; border-radius: 10px; border:1px solid var(--border); }
.movie-schedule .ms-right h2 { margin: 0 0 6px; }
.movie-schedule .ms-synopsis { margin-top: 8px; }
.time-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-top: 12px; }
.time-card { display: block; background: #232832; border:1px solid var(--border); border-radius: 10px; padding: 10px 12px; color: inherit; }
.time-card .time { font-weight: 700; }
.time-card .sub { font-size: 12px; color: var(--muted); margin-top: 4px; }
.time-card:hover { background: #2a2f3a; text-decoration: none; }
@media (max-width: 700px){ .movie-schedule { grid-template-columns: 120px 1fr; } .movie-schedule .ms-left img{ width:120px; height:170px; } }
</style>

<style>
.chip.active { background: #3a4352; }
.action-buttons { display: flex; gap: 10px; align-items: center; }
.action-buttons .btn { width: 140px; text-align: center; display: inline-flex; justify-content: center; }
</style>

<?php include __DIR__ . '/../inc/footer.php'; ?>

