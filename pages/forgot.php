<?php $page_title = 'Forgot Password'; include __DIR__ . '/../inc/header.php'; ?>
<?php
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  if (valid_email($email)) {
    $subject = 'Password reset request';
    $body = "If you requested a reset, please contact admin to set a new password.";
    $headers = "From: no-reply@localhost\r\n";
    $sent = @mail($email, $subject, $body, $headers);
    log_email($email, $subject, $body, $sent ? 'sent' : 'queued');
    $msg = 'If the email exists, a message has been sent.';
  } else {
    $msg = 'Enter a valid email.';
  }
}
?>

<h1>Forgot Password</h1>
<?php if ($msg): ?><p class="badge" style="background:#264;"><?php echo h($msg); ?></p><?php endif; ?>
<form method="post">
  <label>Email
    <input type="email" name="email" required pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" placeholder="you@example.com">
  </label>
  <button class="btn" type="submit">Send Reset</button>
</form>

<?php include __DIR__ . '/../inc/footer.php'; ?>

