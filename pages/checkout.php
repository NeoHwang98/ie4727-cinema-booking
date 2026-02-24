<?php $page_title = 'Checkout'; include __DIR__ . '/../inc/header.php'; ?>
<?php
// Build items and respect selection from Cart (if any)
$items = cart_detailed();
// Only filter when a non-empty selection exists; otherwise use full cart
if (isset($_SESSION['cart_selection']) && is_array($_SESSION['cart_selection']) && count($_SESSION['cart_selection']) > 0) {
  $sel = array_map('intval', $_SESSION['cart_selection']);
  $filtered = array_values(array_filter($items, function($it) use ($sel){ return in_array((int)$it['index'], $sel, true); }));
  if (!empty($filtered)) { $items = $filtered; }
}
// Totals for the items we are actually checking out now
$totals = ['tickets'=>0,'amount'=>0.0];
foreach ($items as $it) { $totals['tickets'] += (int)$it['tickets']; $totals['amount'] += (float)$it['subtotal']; }
?>

<h1>Checkout</h1>

<?php $requires_selection = !empty($_SESSION['cart_selection']) && is_array($_SESSION['cart_selection']); ?>
<?php if (!$items): ?>
  <p>Your cart is empty. <a href="/cinema-test/pages/movies.php">Add sessions</a> first.</p>
<?php else: ?>
  <section>
    <h2>Summary</h2>
    <ul>
      <?php foreach ($items as $it): $s=$it['show']; ?>
        <li>
          <?php echo h($s['movie_title']); ?> —
          <?php echo date('D, d M Y H:i', strtotime($s['start_at'])); ?> —
          <?php echo h($s['screen_name']); ?> —
          <?php echo (int)$it['tickets']; ?> tickets
        </li>
      <?php endforeach; ?>
    </ul>
    <p><strong>Total:</strong> $<?php echo number_format((float)$totals['amount'], 2); ?></p>
  </section>

  <section>
    <h2>Your Details</h2>
    <form id="checkout-form" data-requires-selection="<?php echo $requires_selection ? '1' : '0'; ?>" method="post" action="/cinema-test/pages/confirm.php" novalidate>
      <div class="grid grid-2">
        <label>Full Name
          <input type="text" name="name" required minlength="2" maxlength="150" placeholder="Jane Doe">
        </label>
        <label>Email
          <input type="email" name="email" required placeholder="jane@example.com">
        </label>
        <div>
          <label>Phone (Singapore)</label>
          <input type="tel" name="phone_sg" inputmode="numeric" pattern="^[89][0-9]{7}$" maxlength="8" placeholder="8 digits, starts with 8 or 9" required oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,8)">
          <small class="muted">Must start with 8 or 9 and be 8 digits.</small>
        </div>
      </div>
      <h3>Coupon</h3>
      <div class="row gap">
        <input type="text" name="coupon" placeholder="Enter coupon code" style="max-width:240px;">
      </div>
      <?php if ($requires_selection): foreach ($_SESSION['cart_selection'] as $idx): ?>
        <input type="hidden" name="selected[]" value="<?= (int)$idx ?>">
      <?php endforeach; endif; ?>
      <h3>Payment</h3>
      <div class="grid grid-2">
        <label>Card Number
          <input type="text" name="card" inputmode="numeric" required pattern="^[0-9]{16}$" maxlength="16" placeholder="16 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
        </label>
        <label>Name on Card
          <input type="text" name="card_name" required minlength="2" maxlength="150" placeholder="JANE DOE">
        </label>
        <label>Expiry (Month)
          <input type="month" name="expiry_month" required min="<?php echo date('Y-m'); ?>">
        </label>
        <label>CVV
          <input type="text" name="cvv" inputmode="numeric" required pattern="^[0-9]{3}$" maxlength="3" placeholder="123" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
        </label>
      </div>
      <button class="btn" type="submit">Pay</button>
    </form>
  </section>
<?php endif; ?>

<?php include __DIR__ . '/../inc/footer.php'; ?>

<script>
  (function(){
    const form = document.getElementById('checkout-form');
    if (!form) return;
    form.addEventListener('submit', function(e){
      if (form.dataset.requiresSelection !== '1') { return; }
      const selected = form.querySelectorAll('input[name="selected[]"]');
      if (selected.length === 0) {
        e.preventDefault();
        let msg = document.getElementById('sel-msg');
        if (!msg) {
          msg = document.createElement('p');
          msg.id = 'sel-msg';
          msg.className = 'small error';
          form.insertBefore(msg, form.firstElementChild);
        }
        msg.textContent = 'No items were checked.';
        window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
      }
    });
  })();
  </script>
