<?php $page_title = 'My Bookings'; include __DIR__ . '/../inc/header.php'; ?>
<h1>My Bookings</h1>
<?php if (!auth_user()): ?>
  <p>Please <a href="/cinema-test/pages/login.php">log in</a> to view your bookings.</p>
<?php else: ?>
  <?php
    $u = auth_user();
    $filter = ($_GET['type'] ?? 'upcoming') === 'past' ? 'past' : 'upcoming';
    $order = $filter === 'past' ? 'DESC' : 'ASC';
    $timeCond = $filter === 'past' ? '<' : '>=';
    $sql = "SELECT b.*,
                   MIN(s.start_at) AS first_show,
                   MAX(s.start_at) AS last_show,
                   GROUP_CONCAT(DISTINCT m.title ORDER BY s.start_at SEPARATOR ', ') AS titles
              FROM bookings b
              JOIN customers c ON c.id = b.customer_id
              JOIN booking_items bi ON bi.booking_id = b.id
              JOIN shows s ON s.id = bi.show_id
              JOIN movies m ON m.id = s.movie_id
             WHERE c.email = ?
          GROUP BY b.id
            HAVING first_show $timeCond NOW()
          ORDER BY first_show $order";
    $stmt = db()->prepare($sql);
    $stmt->bind_param('s', $u['email']);
    $rows = fetch_all($stmt);
  ?>
    <p>
      <a class="chip<?php echo $filter === 'past' ? '' : ' active'; ?>" href="?type=upcoming">Upcoming</a>
      <a class="chip<?php echo $filter === 'past' ? ' active' : ''; ?>" href="?type=past">Past</a>
    </p>
    <?php if (!$rows): ?>
      <p class="muted">No <?php echo $filter === 'past' ? 'past' : 'upcoming'; ?> bookings found.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($rows as $r): ?>
          <div class="card"><div class="card-body">
            <div class="small muted">#<?php echo (int)$r['id']; ?> &bull; <?php echo h($r['first_show'] ?? $r['created_at']); ?></div>
            <div><strong><?php echo h($r['titles'] ?? ''); ?></strong></div>
            <div class="small">Tickets: <?php echo (int)$r['total_tickets']; ?> &bull; Paid: $<?php echo number_format((float)$r['total_amount'] - (float)$r['discount_amount'], 2); ?></div>
            <a class="btn btn-sm" href="/cinema-test/pages/acknowledgement.php?id=<?php echo (int)$r['id']; ?>">View</a>
          </div></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/../inc/footer.php'; ?>
