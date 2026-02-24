<?php $page_title = 'Select Seats'; include __DIR__ . '/../inc/header.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$show = $id ? get_show($id) : null;
if (!$show) { echo '<h1>Session not found</h1>'; include __DIR__ . '/../inc/footer.php'; exit; }

// Require login to purchase/select seats
if (!auth_user()) {
  $next = '/cinema-test/pages/details.php?id=' . $show['id'];
  header('Location: /cinema-test/pages/login.php?next=' . urlencode($next));
  exit;
}

// Pricing: single uniform price
$base = (float)$show['base_price'];

// Reserved seats from existing bookings for this show
$stmt = db()->prepare("SELECT seat_labels FROM booking_items WHERE show_id = ? AND seat_labels IS NOT NULL AND seat_labels <> ''");
$stmt->bind_param('i', $show['id']);
$rows = fetch_all($stmt);
$reserved = [];
foreach ($rows as $r) {
  foreach (explode(',', $r['seat_labels']) as $s) { $s = trim($s); if ($s !== '') $reserved[$s] = true; }
}

// Account purchase limit per show
$maxPerAccount = 2; $alreadyBought = 0; $remaining = $maxPerAccount;
if ($u = auth_user()) {
  $email = $u['email'] ?? '';
  if ($email) {
    $stmt = db()->prepare("SELECT COALESCE(SUM(bi.tickets),0) AS cnt
                           FROM booking_items bi
                           JOIN bookings b ON b.id = bi.booking_id
                           JOIN customers c ON c.id = b.customer_id
                           WHERE bi.show_id = ? AND c.email = ?");
    $stmt->bind_param('is', $show['id'], $email);
    $row = fetch_one($stmt);
    $alreadyBought = (int)($row['cnt'] ?? 0);
    $remaining = max(0, $maxPerAccount - $alreadyBought);
  }
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $seats = isset($_POST['seats']) && is_array($_POST['seats']) ? array_slice($_POST['seats'], 0, 10) : [];
  // Remove any reserved seats just in case
  $seats = array_values(array_filter($seats, function($s) use ($reserved){ return !isset($reserved[$s]); }));
  $limit = $remaining; // strictly enforce remaining allowance for this account
  if (count($seats) === 0) {
    echo '<p class="badge" style="background:#633;">Please select at least one available seat.</p>';
  } elseif (count($seats) > $limit) {
    echo '<p class="badge" style="background:#633;">For this session, an account may purchase up to 2 seats. You have ' . $alreadyBought . ' already; you can add at most ' . $remaining . ' more.</p>';
  } else {
    cart_add($show['id'], count($seats), $seats, $base);
    header('Location: /cinema-test/pages/cart.php'); exit;
  }
}
?>

<h1><?php echo h($show['movie_title']); ?></h1>
<p class="muted"><?php echo date('D, d M Y H:i', strtotime($show['start_at'])); ?> • <?php echo h($show['cinema_name']); ?> — <?php echo h($show['screen_name']); ?></p>

<div class="row gap">
  <div class="col">
    <h2>Choose Seats</h2>
    <form method="post" id="seat-form">
      <div class="screen-bar">SCREEN</div>
      <div class="seat-grid">
        <?php
        // 10 rows x 12 cols, uniform price, mark reserved
        $rowsL = range('A','J'); $cols = range(1,12);
        $lockAll = ($remaining <= 0);
        foreach ($rowsL as $r) {
          echo '<div class="seat-row">';
          echo '<div class="seat-row-label">'.$r.'</div>';
          foreach ($cols as $c) {
            $code = $r.$c; // labels like A1, B2
            $isRes = isset($reserved[$code]);
            $disabled = $isRes || $lockAll;
            $cls = 'S';
            echo '<label class="seat seat-'.$cls.(($isRes||$lockAll)?' reserved':'').'">'
               . '<input type="checkbox" name="seats[]" value="'.$code.'"'.($disabled?' disabled':'').'>'
               . '<span></span>'
               . '</label>';
          }
          echo '</div>';
        }
        ?>
      </div>
      <div class="legend small">
        <span class="dot avail"></span> Available
        <span class="dot reserved"></span> Unavailable
        <span class="dot selected"></span> Selected
      </div>
      <div class="row space-between align-center" style="margin-top:10px;">
        <div id="seat-summary" class="small muted">No seats selected. Price: $<?php echo number_format($base,2); ?> per seat. Max 2 per account.</div>
        <button class="btn" type="submit" <?php echo ($remaining<=0?'disabled':''); ?>>Add to Cart</button>
      </div>
    </form>
  </div>
  <div class="col-3">
    <div class="card"><div class="card-body">
      <h3>Show Details</h3>
      <p><strong>Duration:</strong> <?php echo (int)$show['duration_min']; ?> min</p>
      <p><strong>Price:</strong> $<?php echo number_format($base,2); ?> per seat</p>
      <p><strong>Quantity:</strong> <span id="qtyCount">0</span></p>
      <p><strong>Total:</strong> $<span id="totalPrice">0.00</span></p>
      <?php if ($u = auth_user()): ?>
        <p class="small muted">You have purchased <?php echo (int)$alreadyBought; ?> seat(s) for this session. Remaining allowance: <?php echo (int)$remaining; ?>.</p>
      <?php endif; ?>
    </div></div>
  </div>
</div>

<style>
.screen-bar { text-align:center; margin:8px auto 12px; padding:6px 16px; background:#111419; border-radius:6px; border:1px solid var(--border); width: 60%; }
.seat-grid { display: grid; gap: 8px; background:#0c0f14; padding:12px; border:1px solid var(--border); border-radius:10px; }
.seat-row { display:flex; gap:8px; align-items:center; }
.seat-row-label { width:20px; text-align:center; color: var(--muted); }
.seat { width:24px; height:24px; border-radius:4px; border:1px solid var(--border); display:inline-flex; align-items:center; justify-content:center; position:relative; }
.seat input { display:none; }
.seat span { width:16px; height:16px; border-radius:3px; display:block; }
.seat-S span { background:#3a3f4d; }
.seat.reserved span { background:#2b2f36; opacity: .6; }
.seat input:checked + span { outline:2px solid #ff4b5c; background:#ff4b5c; }
.seat-legend { display:flex; gap:16px; margin-bottom:8px; align-items:center; }
.legend { display:flex; gap:18px; align-items:center; margin-top:8px; }
.dot { display:inline-block; width:12px; height:12px; border-radius:999px; border:1px solid var(--border); }
.dot.avail { background:#3a3f4d; }
.dot.reserved { background:#2b2f36; }
.dot.selected { background:#ff4b5c; border-color:#ff4b5c; }
</style>

<script>
 document.addEventListener('DOMContentLoaded', () => {
 const form = document.getElementById('seat-form');
 const summary = document.getElementById('seat-summary');
 const maxSeats = <?php echo (int)$remaining; ?>;
 const qtyEl = document.getElementById('qtyCount');
 const totalEl = document.getElementById('totalPrice');
 const unit = <?php echo json_encode(number_format($base,2,'.','')); ?>;
  // Fix subtitle to hide cinema/location and show only time + screen
  const subtitle = document.querySelector('h1 + p.muted');
  if (subtitle) {
    subtitle.textContent = <?php echo json_encode(date('D, d M Y H:i', strtotime($show['start_at'])) . ' — Screen: ' . $show['screen_name']); ?>;
  }
  form.addEventListener('change', () => {
    let checked = form.querySelectorAll('input[type=checkbox]:checked');
    if (checked.length > maxSeats) {
      // uncheck the most recently checked
      const last = Array.from(checked).pop();
      last.checked = false;
      alert('Not allowed: You have reached your 2-seat limit for this session.');
      checked = form.querySelectorAll('input[type=checkbox]:checked');
    }
    const seats = Array.from(checked).map(i => i.value);
    if (seats.length === 0) { summary.textContent = 'No seats selected.'; return; }
    summary.textContent = seats.length + ' seat(s): ' + seats.slice(0,8).join(', ') + (seats.length>8?'...':'');
    if (qtyEl) qtyEl.textContent = String(seats.length);
    if (totalEl) {
      const total = (seats.length * parseFloat(unit));
      totalEl.textContent = total.toFixed(2);
    }
  });
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
