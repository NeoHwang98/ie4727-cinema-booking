<?php
require_once dirname(__DIR__) . '/inc/init.php';

$error = '';

function pick($arr, $keys, $default = '') {
  foreach ($keys as $k) { if (isset($arr[$k]) && $arr[$k] !== '' && $arr[$k] !== null) return $arr[$k]; }
  return $default;
}
function format_dt($val) {
  if (!$val) return '';
  if ($val instanceof DateTimeInterface) return $val->format('D, d M Y H:i');
  $ts = is_numeric($val) ? (int)$val : strtotime((string)$val);
  if ($ts === false) return (string)$val;
  return date('D, d M Y H:i', $ts);
}

// Remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
  $idx = filter_var($_POST['remove'], FILTER_VALIDATE_INT);
  if ($idx !== false) { cart_remove((int)$idx); }
  header('Location: cart.php');
  exit;
}

// Proceed with selected items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_selected'])) {
  $selection = isset($_POST['choose']) && is_array($_POST['choose']) ? array_map('intval', $_POST['choose']) : [];
  $selection = array_values(array_unique(array_filter($selection, fn($v)=>$v>=0)));
  if (empty($selection)) {
    $error = 'No items were selected.';
  } else {
    $_SESSION['cart_selection'] = $selection;
    header('Location: checkout.php');
    exit;
  }
}

$items = function_exists('cart_detailed') ? cart_detailed() : (($_SESSION['cart'] ?? []));
$preSel = isset($_SESSION['cart_selection']) && is_array($_SESSION['cart_selection']) ? array_map('intval', $_SESSION['cart_selection']) : [];
?>

<?php include dirname(__DIR__) . '/inc/header.php'; ?>

<style>
  .cart-wrap { max-width: 1100px; margin: 0 auto; padding: 24px 16px; }
  .cart-title { font-size: 24px; font-weight: 600; margin: 8px 0 20px; }
  .cart-list { display: flex; flex-direction: column; gap: 12px; }
  .cart-item { display: grid; grid-template-columns: 28px 92px 1fr auto; gap: 12px; align-items: center; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--card); color: var(--text); }
  .cart-item .thumb { width: 92px; height: 132px; object-fit: cover; border-radius: 4px; background: #0c0f14; border:1px solid var(--border); }
  .cart-item .title { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
  .cart-item .meta { color: var(--muted); font-size: 13px; margin-bottom: 6px; }
  .cart-item .seats { color: var(--text); font-size: 13px; }
  .cart-item .price { text-align: right; font-size: 14px; white-space: nowrap; }
  .cart-item .btn-remove { background: none; border: none; color: #ff9a9a; cursor: pointer; font-size: 13px; text-decoration: underline; padding: 4px 8px; }
  .cart-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; }
  .cart-select { display: flex; align-items: center; gap: 12px; }
  .btn-primary { background: var(--primary); color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }
  .empty { padding: 20px; color: var(--muted); }
  @media (max-width: 640px) { .cart-item { grid-template-columns: 24px 72px 1fr; } .cart-item .price { grid-column: 3 / 4; justify-self: end; } }
</style>

<div class="cart-wrap">
  <div class="cart-title">Your Cart</div>
  <?php if (!empty($error)): ?><p class="badge" style="background:#633; color:#fff; display:inline-block; padding:6px 10px; border-radius:6px;"><?php echo h($error); ?></p><?php endif; ?>

  <?php if (empty($items)) : ?>
    <div class="empty">Your cart is empty.</div>
  <?php else: ?>
  <form method="post" action="cart.php" id="cartForm">
    <div class="cart-list">
      <?php foreach ($items as $i => $it):
        $show   = $it['show'] ?? [];
        $title  = pick($show, ['movie_title','title','name'], 'Untitled');
        $dtRaw  = pick($show, ['start_at','show_datetime','start_time','time','show_time'], '');
        $dt     = format_dt($dtRaw);
        $screen = pick($show, ['screen_name','screen'], '');
        $poster = '/cinema-test/assets/img/placeholder.svg';
        if (!empty($show['movie_id'])) { $mv = get_movie((int)$show['movie_id']); if ($mv && !empty($mv['poster_path'])) $poster = $mv['poster_path']; }
        $price  = (float) ($it['price_each'] ?? 0);
        $qty    = (int)   ($it['tickets'] ?? 1);
        $seats  = $it['seats'] ?? [];
        if (!is_array($seats)) { $seats = array_filter(array_map('trim', explode(',', (string)$seats))); }
        $seatsText = $seats ? implode(', ', array_map('h', $seats)) : '—';
        $meta = $dt . ($screen !== '' ? ' — ' . $screen : '');
        $subtotal = $price * $qty;
      ?>
      <div class="cart-item">
        <input type="checkbox" name="choose[]" value="<?= (int)$i ?>" aria-label="Select item" <?= in_array((int)$i, $preSel, true) ? 'checked' : '' ?>>
        <img class="thumb" src="<?= h($poster) ?>" alt="<?= h($title) ?> poster">
        <div>
          <div class="title"><?= h($title) ?></div>
          <div class="meta"><?= h($meta) ?></div>
          <div class="seats">Seats: <?= $seatsText ?></div>
        </div>
        <div class="price">
          <?= (int)$qty ?> @ $<?= number_format($price, 2) ?> = $<?= number_format($subtotal, 2) ?><br>
          <button class="btn-remove" type="submit" name="remove" value="<?= (int)$i ?>">Remove</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="cart-footer">
      <div class="cart-select"><label><input type="checkbox" id="selectAll"> Select all</label></div>
      <div><button class="btn-primary" type="submit" name="checkout_selected" value="1">Proceed to Checkout</button></div>
    </div>
  </form>
  <?php endif; ?>
</div>

<script>
  const selectAll = document.getElementById('selectAll');
  const itemCbs = Array.from(document.querySelectorAll('input[name="choose[]"]'));
  const updateSelectAll = () => {
    if (!selectAll) return;
    const total = itemCbs.length;
    const checked = itemCbs.filter(cb => cb.checked).length;
    selectAll.indeterminate = checked > 0 && checked < total;
    selectAll.checked = checked === total && total > 0;
  };
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      itemCbs.forEach(cb => cb.checked = selectAll.checked);
    });
    itemCbs.forEach(cb => cb.addEventListener('change', updateSelectAll));
    updateSelectAll();
  }
</script>

<?php include dirname(__DIR__) . '/inc/footer.php'; ?>
