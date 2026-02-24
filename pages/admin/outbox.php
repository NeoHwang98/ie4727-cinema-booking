<?php
$embedded = $embedded ?? false;

if (!$embedded) {
  $page_title = 'Outbox';
  include __DIR__ . '/../../inc/header.php';
  // Check if admin
  if (function_exists('auth_is_admin') && !auth_is_admin()) {
    echo '<p>Admins only.</p>';
    include __DIR__ . '/../../inc/footer.php';
    exit;
  }
}
?>

<?php if (!$embedded): ?>
<h1>Email Outbox</h1>
<?php else: ?>
<h2>Email Outbox</h2>
<?php endif; ?>

<p class="muted">Server-side generated list of sent/queued emails for verification.</p>

<?php
$stmt = db()->prepare("SELECT * FROM emails ORDER BY id DESC LIMIT 100");
$emails = fetch_all($stmt);
?>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>When</th>
        <th>To</th>
        <th>Subject</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($emails as $e): ?>
        <tr>
          <td><?php echo (int)$e['id']; ?></td>
          <td><?php echo h($e['created_at']); ?></td>
          <td><?php echo h($e['to_email']); ?></td>
          <td><?php echo h($e['subject']); ?></td>
          <td><span class="badge"><?php echo h($e['status']); ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (!$embedded) { include __DIR__ . '/../../inc/footer.php'; } ?>
