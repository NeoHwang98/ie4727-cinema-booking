<?php $page_title = 'Acknowledgement'; include __DIR__ . '/../inc/header.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { echo '<p>Missing booking id.</p>'; include __DIR__ . '/../inc/footer.php'; exit; }
$stmt = db()->prepare("SELECT b.*, c.name, c.email FROM bookings b JOIN customers c ON c.id=b.customer_id WHERE b.id = ?");
$stmt->bind_param('i', $id);
$booking = fetch_one($stmt);
if (!$booking) { echo '<p>Booking not found.</p>'; include __DIR__ . '/../inc/footer.php'; exit; }
$stmt = db()->prepare("SELECT bi.*, s.start_at, m.title, m.rating, m.duration_min FROM booking_items bi JOIN shows s ON s.id=bi.show_id JOIN movies m ON m.id=s.movie_id WHERE bi.booking_id = ?");
$stmt->bind_param('i', $id);
$items = fetch_all($stmt);
?>

<h1>Thank you for your booking</h1>
<p>An email confirmation will be sent to the provided address.</p>

<div class="card"><div class="card-body">
  <div class="row space-between align-center">
    <div>
      <div><strong>Reference:</strong> #<?php echo (int)$booking['id']; ?></div>
      <div class="small muted">Tickets: <?php echo (int)$booking['total_tickets']; ?> • Paid: $<?php echo number_format((float)$booking['total_amount'] - (float)$booking['discount_amount'], 2); ?></div>
    </div>
    <div>
      <?php
      // Simple pseudo-QR SVG based on booking id
      $seed = (int)$booking['id']; srand($seed);
      $cells = 16; $size = 4; $svg = '<svg width="'.($cells*$size).'" height="'.($cells*$size).'" xmlns="http://www.w3.org/2000/svg">';
      for ($y=0;$y<$cells;$y++) { for ($x=0;$x<$cells;$x++) { $on = rand(0,1); if ($on) $svg .= '<rect x="'.($x*$size).'" y="'.($y*$size).'" width="'.$size.'" height="'.$size.'" fill="#fff"/>'; } }
      $svg .= '</svg>';
      echo '<div style="background:#000; padding:6px; border-radius:6px;">'.$svg.'</div>';
      ?>
    </div>
  </div>
</div></div>

<h2>Details</h2>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Movie</th><th>Rating</th><th>Duration</th><th>Date & Time</th><th>Seats</th><th>Qty</th><th>Price</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?php echo h($it['title']); ?></td>
          <td><?php echo h($it['rating']); ?></td>
          <td><?php echo (int)$it['duration_min']; ?> min</td>
          <td><?php echo date('D, d M Y H:i', strtotime($it['start_at'])); ?></td>
          <td><?php echo h($it['seat_labels']); ?></td>
          <td><?php echo (int)$it['tickets']; ?></td>
          <td>$<?php echo number_format((float)$it['price_each'] * (int)$it['tickets'], 2); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<p>
  <a class="btn" href="/cinema-test/index.php">Back to Home</a>
</p>

<?php include __DIR__ . '/../inc/footer.php'; ?>
