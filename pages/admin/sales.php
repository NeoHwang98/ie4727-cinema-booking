<?php
$embedded = $embedded ?? false;

if (!$embedded) {
  $page_title = 'Sales Report';
  include __DIR__ . '/../../inc/header.php';
  if (!auth_is_admin()) {
    echo '<p>Admins only.</p>';
    include __DIR__ . '/../../inc/footer.php';
    exit;
  }
}
?>

<?php if (!$embedded): ?>
<h1>Sales Report</h1>
<?php else: ?>
<h2>Sales Report</h2>
<?php endif; ?>

<?php
$group = $_GET['group'] ?? 'date';
$groupModes = [
  'date'  => ['label' => 'DATE(b.created_at)', 'heading' => 'Date',  'limit' => 14],
  'month' => ['label' => "DATE_FORMAT(b.created_at, '%Y-%m')", 'heading' => 'Month', 'limit' => 12],
  'movie' => ['label' => 'm.title', 'heading' => 'Movie', 'limit' => 25],
];
if (!isset($groupModes[$group])) { $group = 'date'; }
$dir = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';
$mode = $groupModes[$group];
$labelExpr = $mode['label'] . ' AS lbl';
$limit = (int)$mode['limit'];
$selectMovies = in_array($group, ['date','month'], true) ? ", GROUP_CONCAT(DISTINCT m.title ORDER BY m.title SEPARATOR ', ') AS movies" : '';
$sql = "SELECT {$labelExpr}{$selectMovies},
               SUM(b.total_tickets) AS tickets,
               SUM(b.total_amount - b.discount_amount) AS revenue
        FROM bookings b
        JOIN booking_items bi ON bi.booking_id = b.id
        JOIN shows s ON s.id = bi.show_id
        JOIN movies m ON m.id = s.movie_id
        GROUP BY {$mode['label']}
        ORDER BY " . ($group === 'movie' ? "revenue {$dir}, lbl ASC" : "MIN(b.created_at) {$dir}") . "
        LIMIT {$limit}";
$res = db()->query($sql);
$rows = [];
$totalRevenue = 0.0;
$totalTickets = 0;
while ($res && $r = $res->fetch_assoc()) {
  $rows[] = $r;
  $totalRevenue += (float)$r['revenue'];
  $totalTickets += (int)$r['tickets'];
}
$popular = fetch_one(db()->prepare("SELECT m.title, SUM(bi.tickets*bi.price_each) AS revenue
  FROM booking_items bi
  JOIN shows s ON s.id=bi.show_id
  JOIN movies m ON m.id=s.movie_id
  JOIN bookings b ON b.id=bi.booking_id
  GROUP BY m.id
  ORDER BY revenue DESC
  LIMIT 1"));
?>

<form method="get" class="row gap" style="margin-bottom:16px; flex-wrap:wrap;">
  <input type="hidden" name="tab" value="sales">
  <label>Group By
    <select name="group">
      <?php foreach ($groupModes as $key => $cfg): ?>
        <option value="<?php echo $key; ?>" <?php if ($group === $key) echo 'selected'; ?>>
          <?php echo h($cfg['heading']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Sort
    <select name="dir">
      <option value="desc" <?php if ($dir === 'DESC') echo 'selected'; ?>>Newest/Highest first</option>
      <option value="asc" <?php if ($dir === 'ASC') echo 'selected'; ?>>Oldest/Lowest first</option>
    </select>
  </label>
  <button type="submit" class="btn btn-sm">Apply</button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th><?php echo h($mode['heading']); ?></th>
        <?php if ($selectMovies): ?><th>Movies purchased</th><?php endif; ?>
        <th>Tickets</th>
        <th>Revenue</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="<?php echo $selectMovies ? 4 : 3; ?>" class="muted">No data.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?php echo h($r['lbl']); ?></td>
          <?php if ($selectMovies): ?>
            <td><?php echo h($r['movies'] ?: '�'); ?></td>
          <?php endif; ?>
          <td><?php echo (int)$r['tickets']; ?></td>
          <td>$<?php echo number_format((float)$r['revenue'], 2); ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <th>Total</th>
        <?php if ($selectMovies): ?><th>�</th><?php endif; ?>
        <th><?php echo (int)$totalTickets; ?></th>
        <th>$<?php echo number_format($totalRevenue, 2); ?></th>
      </tr>
    </tfoot>
  </table>
</div>

<?php if ($popular): ?>
  <p><strong>Most popular movie (by revenue):</strong> <?php echo h($popular['title']); ?> &mdash; $<?php echo number_format((float)$popular['revenue'],2); ?></p>
<?php endif; ?>

<?php if (!$embedded) { include __DIR__ . '/../../inc/footer.php'; } ?>
