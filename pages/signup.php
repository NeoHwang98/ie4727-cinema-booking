<?php $page_title = 'Sign Up'; include __DIR__ . '/../inc/header.php'; ?>
<?php
$info = '';$error='';
$next = isset($_GET['next']) ? $_GET['next'] : (isset($_POST['next']) ? $_POST['next'] : '/cinema-test/index.php');
$checkEmail = isset($_GET['check']) ? trim($_GET['check']) : '';
$emailExists = false;
if ($checkEmail !== '') {
  $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->bind_param('s', $checkEmail);
  $emailExists = (bool)fetch_one($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if (strlen($name) < 2) $error = 'Enter your full name';
  elseif (!valid_email($email)) $error = 'Enter a valid email';
  else {
    $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    if (fetch_one($stmt)) $error = 'Email already registered';
  }
  if (!$error && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
    $error = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
  }
  if (!$error) {
    $role = 'user';
    $stmt = db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, SHA2(?,256), ?)");
    $stmt->bind_param('ssss', $name, $email, $password, $role);
    $stmt->execute();
    // Auto-login new user
    auth_login($email, $password);
    // Redirect back to target page within site only
    $redir = (strpos($next, '/cinema-test/') === 0) ? $next : '/cinema-test/index.php';
    header('Location: ' . $redir);
    exit;
  }
}
?>

<h1>Sign Up</h1>
<?php if ($info): ?><p class="badge" style="background:#264;"><?php echo h($info); ?></p><?php endif; ?>
<?php if ($error): ?><p class="badge" style="background:#633;"><?php echo h($error); ?></p><?php endif; ?>

<form method="post" class="auth-form" novalidate>
  <input type="hidden" name="next" value="<?php echo h($next); ?>">
  <label>Full Name
    <input type="text" name="name" required minlength="2" maxlength="150" placeholder="Jane Doe">
  </label>
  <label>Email
    <input id="email" type="email" name="email" required pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" placeholder="you@example.com" value="<?php echo h($checkEmail); ?>">
    <small class="muted" id="email-hint"><?php echo $checkEmail !== '' ? ($emailExists ? 'Email already registered' : 'Email available') : 'Type your email'; ?></small>
  </label>
  <label>Password
    <input type="password" name="password" required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$" placeholder="Min 8, upper, lower, number, special">
  </label>
  <button class="btn" type="submit">Create Account</button>
</form>

<script>
// Near-live check without AJAX (reloads page with ?check=...)
document.addEventListener('DOMContentLoaded', () => {
  const email = document.getElementById('email');
  let t;
  email.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(() => {
      const val = email.value.trim();
      if (val) window.location.search = '?check=' + encodeURIComponent(val);
    }, 600);
  });
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
