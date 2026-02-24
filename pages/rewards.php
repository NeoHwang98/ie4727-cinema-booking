<?php $page_title = 'Rewards'; include __DIR__ . '/../inc/header.php'; ?>
<h1>Rewards</h1>
<p class="muted">Registered users can apply coupons during checkout.</p>
<?php
$stmt = db()->prepare("SELECT code, description, discount_type, value, min_total, expires_at FROM coupons WHERE active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY id DESC");
$coupons = fetch_all($stmt);
?>
<style>
.coupon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
.coupon-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 14px; }
.coupon-code { font-weight: 800; letter-spacing: .5px; font-size: 18px; margin: 0 0 6px; }
.coupon-desc { margin: 4px 0 8px; }
.coupon-meta { color: var(--muted); font-size: 12px; }
</style>

<div class="coupon-grid">
  <?php foreach ($coupons as $c): ?>
    <div class="coupon-card">
      <div class="coupon-code"><?php echo h($c['code']); ?></div>
      <div class="coupon-desc"><?php echo h($c['description']); ?></div>
      <?php if ((float)$c['min_total']>0): ?><div class="coupon-meta">Min spend: $<?php echo number_format((float)$c['min_total'],2); ?></div><?php endif; ?>
      <?php if (!empty($c['expires_at'])): ?><div class="coupon-meta">Expires: <?php echo h($c['expires_at']); ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
