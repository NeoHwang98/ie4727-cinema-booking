<?php $page_title = 'Account'; include __DIR__ . '/../inc/header.php'; ?>
<?php if (!auth_user()): ?>
  <h1>Account</h1>
  <p>You are not signed in. <a class="btn" href="/cinema-test/pages/login.php">Log in</a> or <a href="/cinema-test/pages/signup.php">Sign up</a>.</p>
<?php else: $u = auth_user(); ?>
  <h1>Account</h1>
  <p><strong>Name:</strong> <?php echo h($u['name'] ?? ''); ?></p>
  <p><strong>Email:</strong> <?php echo h($u['email'] ?? ''); ?></p>
  <p><strong>Role:</strong> <?php echo h($u['role']); ?></p>
  <p><a class="btn" href="/cinema-test/pages/bookings.php">View My Bookings</a></p>
<?php endif; ?>
<?php include __DIR__ . '/../inc/footer.php'; ?>

