<?php $page_title = 'Movies'; include __DIR__ . '/../inc/header.php'; ?>

<?php
  $tab = ($_GET['tab'] ?? 'now') === 'soon' ? 'soon' : 'now';
  $allMovies = get_movies(null, null, null);
  $today = new DateTime('today');

  $movies = array_values(array_filter($allMovies, function($m) use ($tab, $today){
    $release = !empty($m['release_date']) ? DateTime::createFromFormat('Y-m-d', $m['release_date']) : null;
    if ($release) {
      return $tab === 'soon' ? $release > $today : $release <= $today;
    }
    return $tab === ($m['status'] === 'soon' ? 'soon' : 'now');
  }));

  usort($movies, function($a, $b) use ($tab){
    $ra = $a['release_date'] ?? '';
    $rb = $b['release_date'] ?? '';
    if ($ra === $rb) { return strcmp($a['title'], $b['title']); }
    if ($tab === 'soon') {
      return strcmp($ra ?: '9999-99-99', $rb ?: '9999-99-99');
    }
    return strcmp($ra ?: '0000-00-00', $rb ?: '0000-00-00');
  });
?>

<div class="tabs">
  <a class="tab <?php echo $tab==='now'?'active':''; ?>" href="/cinema-test/pages/movies.php?tab=now">Now Showing</a>
  <a class="tab <?php echo $tab==='soon'?'active':''; ?>" href="/cinema-test/pages/movies.php?tab=soon">Coming Soon</a>
</div>

<div class="movie-list">
  <?php foreach ($movies as $m): $src = $m['poster_path'] ?: '/cinema-test/assets/posters/placeholder.jpg'; ?>
    <article class="movie-row">
      <a class="poster" href="/cinema-test/pages/movie.php?id=<?php echo (int)$m['id']; ?>">
        <img src="<?php echo h($src); ?>" alt="<?php echo h($m['title']); ?> poster">
      </a>
      <div class="details">
        <h2 class="title"><?php echo h($m['title']); ?></h2>
        <div class="meta muted small"><?php echo h($m['rating']); ?> &bull; <?php echo (int)$m['duration_min']; ?> min <?php if(!empty($m['release_date'])) echo ' &bull; ' . h($m['release_date']); ?></div>
        <p class="synopsis"><?php echo h($m['synopsis']); ?></p>
        <div class="actions">
          <a class="btn" href="/cinema-test/pages/movie.php?id=<?php echo (int)$m['id']; ?>">Times & Tickets</a>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<style>
.tabs { display:flex; gap:18px; margin-bottom: 12px; }
.tab { padding: 6px 0; color: var(--muted); border-bottom: 2px solid transparent; }
.tab.active { color: var(--text); border-bottom-color: var(--accent); }
.movie-list { display:flex; flex-direction:column; gap: 18px; }
.movie-row { display:grid; grid-template-columns: 180px 1fr; gap: 16px; padding: 14px; border:1px solid var(--border); border-radius: 12px; background: var(--card); }
.movie-row .poster img { width: 180px; height: 260px; object-fit: cover; border-radius: 10px; border:1px solid var(--border); }
.movie-row .title { margin: 2px 0 6px; }
.movie-row .synopsis { margin: 8px 0 0; }
.movie-row .actions { margin-top: 10px; display:flex; gap: 8px; }
</style>

<?php include __DIR__ . '/../inc/footer.php'; ?>
