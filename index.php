<?php $page_title = 'NECT - Home'; include __DIR__ . '/inc/header.php'; ?>

<?php $now = get_movies('now'); ?>

<section>
  <h2>Highlights</h2>
  <div class="hero-panels-wrap">
    <button class="scroll-arrow left" data-scroll="-1" data-target="#heroPanels">&lt;</button>
    <button class="scroll-arrow right" data-scroll="1" data-target="#heroPanels">&gt;</button>
    <div id="heroPanels" class="hero-panels" data-mode="cycle" data-index="0">
      <?php foreach (array_slice($now, 0, 8) as $i=>$m): 
      // Prefer a horizontal highlight image if provided
      $bg = '/cinema-test/assets/img/placeholder.svg';
      $hiWeb = '/cinema-test/assets/highlights/';
      $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $m['title']));
      $candidates = [];
      foreach (['jpg','jpeg','png','webp','JPG','JPEG','PNG','WEBP'] as $ext) {
        $candidates[] = ['movie-' . (int)$m['id'] . '.' . $ext, $hiWeb . 'movie-' . (int)$m['id'] . '.' . $ext];
        $candidates[] = [$slug . '.' . $ext, $hiWeb . $slug . '.' . $ext];
      }
      foreach ($candidates as [$name, $web]) {
        $fs1 = __DIR__ . '/assets/highlights/' . $name;
        $fs2 = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . '/cinema-test/assets/highlights/' . $name;
        if (file_exists($fs1) || ($fs2 && file_exists($fs2))) { $bg = $web; break; }
      }
      if ($bg === '/cinema-test/assets/img/placeholder.svg' && !empty($m['poster_path'])) { $bg = h($m['poster_path']); }
      ?>
      <a class="panel <?php echo $i===0?'active':''; ?>" href="/cinema-test/pages/movie.php?id=<?php echo (int)$m['id']; ?>" style="background-image:url('<?php echo $bg; ?>');">
        <div class="panel-overlay">
          <div class="badge-soft">NOW SHOWING</div>
          <h3><?php echo h($m['title']); ?></h3>
          <p class="muted"><?php echo h(substr($m['synopsis'],0,140)); ?>...</p>
          <span class="linkish">Book Now &raquo;</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section>
  <h2>Now Showing</h2>
  <div class="strip">
    <?php foreach ($now as $m): $src = $m['poster_path'] ? h($m['poster_path']) : '/cinema-test/assets/img/placeholder.svg'; ?>
      <a class="tile-lg" href="/cinema-test/pages/movie.php?id=<?php echo (int)$m['id']; ?>">
        <img src="<?php echo $src; ?>" alt="<?php echo h($m['title']); ?> poster">
        <div class="tile-caption">
          <div class="title"><?php echo h($m['title']); ?></div>
          <div class="small muted"><?php echo h($m['rating']); ?> • <?php echo (int)$m['duration_min']; ?> min</div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section>
  <h2>Rewards & Coupons</h2>
  <p class="muted small">Sign in to apply coupons at checkout.</p>
  <?php $stmt = db()->prepare("SELECT * FROM coupons WHERE active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY id DESC LIMIT 6"); $coupons = fetch_all($stmt); ?>
  <div class="grid grid-2">
    <?php foreach ($coupons as $c): ?>
      <div class="card"><div class="card-body">
        <div class="row space-between align-center">
          <h3><?php echo h($c['code']); ?></h3>
          <span class="badge"><?php echo h($c['discount_type']); ?> <?php echo h($c['value']); ?><?php echo $c['discount_type']==='percent'?'%':'$'; ?></span>
        </div>
        <p><?php echo h($c['description']); ?></p>
        <?php if ($c['min_total']>0): ?><div class="small muted">Min spend $<?php echo number_format((float)$c['min_total'],2); ?></div><?php endif; ?>
        <?php if ($c['expires_at']): ?><div class="small muted">Expires <?php echo h($c['expires_at']); ?></div><?php endif; ?>
      </div></div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Coming Soon intentionally omitted on home page -->

<?php include __DIR__ . '/inc/footer.php'; ?>
