<?php
$embedded = $embedded ?? false;

if (!$embedded) {
  $page_title = 'Add Coupon';
  include __DIR__ . '/../../inc/header.php';
  if (!auth_is_admin()) {
    echo '<p>Admins only.</p>';
    include __DIR__ . '/../../inc/footer.php';
    exit;
  }
}
?>

<?php if (!$embedded): ?>
<h1>Add Coupon</h1>
<?php else: ?>
<h2>Add Coupon</h2>
<?php endif; ?>

<?php
$msg=''; $err='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = strtoupper(trim($_POST['code'] ?? ''));
  $desc = trim($_POST['description'] ?? '');
  $type = ($_POST['discount_type'] ?? 'percent') === 'amount' ? 'amount' : 'percent';
  $val = (float)($_POST['value'] ?? 0);
  $min = (float)($_POST['min_total'] ?? 0);
  $active = isset($_POST['active']) ? 1 : 0;
  $exp = trim($_POST['expires_at'] ?? '');
  if ($exp !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp)) { $err = 'Invalid expiry date (use the date picker).'; }
  if (!preg_match('/^[A-Z0-9-]{3,40}$/', $code)) { $err = 'Code must be 3-40 chars: A-Z, 0-9, dash.'; }
  if ($desc === '') { $err = 'Description required.'; }
  if ($min < 0) { $min = 0; }
  if ($type === 'percent') {
    if ($val <= 0 || $val > 100) { $err = 'Percent must be between 1 and 100.'; }
  } else {
    if ($val < 0) { $err = 'Amount must be 0 or more.'; }
  }
  if (!$err) {
    $stmt = db()->prepare("INSERT INTO coupons (code, description, discount_type, value, active, min_total, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $expParam = ($exp==='') ? null : $exp;
    $stmt->bind_param('sssdiis', $code, $desc, $type, $val, $active, $min, $expParam);
    $stmt->execute();
    $msg = 'Coupon saved';
  }
}
$rows = db()->query("SELECT * FROM coupons ORDER BY id DESC");
?>
<?php if ($msg): ?><p class="badge" style="background:#264;"><?php echo h($msg); ?></p><?php endif; ?>
<?php if ($err): ?><p class="badge" style="background:#633;"><?php echo h($err); ?></p><?php endif; ?>

<div class="card"><div class="card-body">
  <form method="post" class="grid grid-2">
    <label>Code
      <input name="code" required maxlength="40" pattern="^[A-Za-z0-9-]{3,40}$" placeholder="WELCOME10">
      <small class="muted">A–Z, 0–9 and dashes only.</small>
    </label>
    <label>Description
      <input name="description" required placeholder="e.g., 10% off for new users">
    </label>
    <label>Type
      <select name="discount_type" id="ctype">
        <option value="percent">percent</option>
        <option value="amount">amount</option>
      </select>
    </label>
    <label>Value
      <input type="number" name="value" id="cvalue" step="0.01" min="0" placeholder="e.g., 10 or 5.00" required>
      <small class="muted">Percent: 1–100. Amount: ≥ 0.</small>
    </label>
    <label>Min Total
      <input type="number" name="min_total" step="0.01" min="0" value="0">
    </label>
    <label>Expires
      <input type="date" name="expires_at">
    </label>
    <label><input type="checkbox" name="active" checked> Active</label>
    <button class="btn" type="submit">Save</button>
  </form>
</div></div>

<h3>Existing Coupons</h3>
<div class="table-wrap">
  <table class="table">
    <thead>
      <tr><th>Code</th><th>Type</th><th>Value</th><th>Min</th><th>Expires</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php while($c = $rows->fetch_assoc()): ?>
        <tr>
          <td><?php echo h($c['code']); ?></td>
          <td><?php echo h($c['discount_type']); ?></td>
          <td><?php echo ($c['discount_type']==='percent' ? h($c['value']).'%' : '$'.number_format((float)$c['value'],2)); ?></td>
          <td>$<?php echo number_format((float)$c['min_total'],2); ?></td>
          <td><?php echo h($c['expires_at'] ?: '—'); ?></td>
          <td><span class="badge"><?php echo $c['active'] ? 'active' : 'inactive'; ?></span></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const type = document.getElementById('ctype');
  const val = document.getElementById('cvalue');
  function update() {
    if (type.value === 'percent') {
      val.min = '1'; val.max = '100'; val.step = '1';
    } else { val.min = '0'; val.removeAttribute('max'); val.step = '0.01'; }
  }
  type.addEventListener('change', update); update();
});
</script>

<?php if (!$embedded) { include __DIR__ . '/../../inc/footer.php'; } ?>
