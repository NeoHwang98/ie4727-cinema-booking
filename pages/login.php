<?php
// login.php — combined Log In / Sign Up / Forgot on a single page
$page_title = 'Account';
include __DIR__ . '/../inc/header.php';

$mode = $_GET['mode'] ?? ($_POST['mode'] ?? 'login'); // which panel is active: login|signup|forgot

$next = isset($_GET['next'])
  ? $_GET['next']
  : (isset($_POST['next']) ? $_POST['next'] : '/cinema-test/index.php');

$login_error  = '';
$signup_error = '';
$signup_info  = '';
$forgot_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mode = $_POST['mode'] ?? 'login';

  if ($mode === 'login') {
    // --- LOGIN HANDLER (from old login.php) ---
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!valid_email($email)) {
      $login_error = 'Enter a valid email.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
      $login_error = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
    } elseif (!auth_login($email, $password)) {
      $login_error = 'Invalid email or password.';
    } else {
      // protect against open redirect
      $redir = (strpos($next, '/cinema-test/') === 0) ? $next : '/cinema-test/index.php';
      header('Location: ' . $redir);
      exit;
    }

  } elseif ($mode === 'signup') {
    // --- SIGN-UP HANDLER (from old signup.php) ---
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($name) < 2) {
      $signup_error = 'Enter your full name';
    } elseif (!valid_email($email)) {
      $signup_error = 'Enter a valid email';
    } else {
      $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
      $stmt->bind_param('s', $email);
      if (fetch_one($stmt)) {
        $signup_error = 'Email already registered';
      }
    }

    if (!$signup_error && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
      $signup_error = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
    }

    if (!$signup_error) {
      $role = 'user';
      $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, SHA2(?,256), ?)');
      $stmt->bind_param('ssss', $name, $email, $password, $role);
      $stmt->execute();

      // Auto-login new user
      auth_login($email, $password);

      // Redirect back to target page within site only
      $redir = (strpos($next, '/cinema-test/') === 0) ? $next : '/cinema-test/index.php';
      header('Location: ' . $redir);
      exit;
    }

  } elseif ($mode === 'forgot') {
    // --- FORGOT-PASSWORD HANDLER (from old forgot.php) ---
    $email = trim($_POST['email'] ?? '');
    if (valid_email($email)) {
      $subject = 'Password reset request';
      $body    = "If you requested a reset, please contact admin to set a new password.";
      $headers = "From: no-reply@localhost\r\n";
      $sent    = @mail($email, $subject, $body, $headers);
      log_email($email, $subject, $body, $sent ? 'sent' : 'queued');
      $forgot_msg = 'If the email exists, a message has been sent.';
    } else {
      $forgot_msg = 'Enter a valid email.';
    }
  }
}
?>

<h1>Account</h1>

<?php $tabs_class = $mode === 'login' ? 'auth-tabs auth-tabs--login' : 'auth-tabs'; ?>
<nav class="<?php echo $tabs_class; ?>">
  <a href="?mode=login"  class="<?php echo $mode === 'login'  ? 'active' : ''; ?>">Log In</a>
  <a href="?mode=signup" class="optional <?php echo $mode === 'signup' ? 'active' : ''; ?>">Sign Up</a>
  <a href="?mode=forgot" class="optional <?php echo $mode === 'forgot' ? 'active' : ''; ?>">Forgot Password</a>
</nav>

<?php if ($mode === 'login'): ?>
  <?php if ($login_error): ?><p class="badge" style="background:#663;"><?php echo h($login_error); ?></p><?php endif; ?>

  <form method="post" class="auth-form login-form" novalidate>
    <input type="hidden" name="mode" value="login">
    <input type="hidden" name="next" value="<?php echo h($next); ?>">

    <label>Email
      <input id="login-email" type="email" name="email" placeholder="you@example.com" required>
    </label>

    <label>Password
      <input type="password" name="password"
             required
             pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$"
             placeholder="Min 8, upper, lower, number, special">
    </label>

    <button class="btn btn-fixed" type="submit">Sign In</button>
    <p class="small">
      <a href="?mode=forgot">Forgot password?</a> ·
      <a href="?mode=signup">Sign up</a>
    </p>
  </form>

<?php elseif ($mode === 'signup'): ?>
  <?php if ($signup_info): ?><p class="badge" style="background:#264;"><?php echo h($signup_info); ?></p><?php endif; ?>
  <?php if ($signup_error): ?><p class="badge" style="background:#633;"><?php echo h($signup_error); ?></p><?php endif; ?>

  <form method="post" class="auth-form" novalidate>
    <input type="hidden" name="mode" value="signup">
    <input type="hidden" name="next" value="<?php echo h($next); ?>">

    <label>Full Name
      <input type="text" name="name" required minlength="2" maxlength="150" placeholder="Jane Doe">
    </label>

    <label>Email
      <input type="email" name="email"
             required
             pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$"
             placeholder="you@example.com">
    </label>

    <label>Password
      <input type="password" name="password"
             required
             pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$"
             placeholder="Min 8, upper, lower, number, special">
    </label>

    <button class="btn" type="submit">Create Account</button>
    <p class="small">
      Already have an account? <a href="?mode=login">Log in</a>
    </p>
  </form>

<?php elseif ($mode === 'forgot'): ?>
  <?php if ($forgot_msg): ?><p class="badge" style="background:#264;"><?php echo h($forgot_msg); ?></p><?php endif; ?>

  <form method="post" class="auth-form" novalidate>
    <input type="hidden" name="mode" value="forgot">
    <input type="hidden" name="next" value="<?php echo h($next); ?>">

    <label>Email
      <input type="email" name="email"
             required
             pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$"
             placeholder="you@example.com">
    </label>

    <button class="btn" type="submit">Send Reset</button>
    <p class="small">
      Remembered? <a href="?mode=login">Log in</a> ·
      <a href="?mode=signup">Sign up</a>
    </p>
  </form>
<?php endif; ?>

<style>
.auth-tabs {
  margin-bottom: 1.5rem;
  display: flex;
  gap: 1rem;
}
.auth-tabs a {
  padding: .35rem .8rem;
  border-radius: 999px;
  text-decoration: none;
  font-size: 0.9rem;
  border: 1px solid #ccc;
}
.auth-tabs a.active {
  background: #333;
  color: #fff;
  border-color: #333;
}
.auth-tabs--login .optional {
  display: none;
}
.auth-form {
  max-width: 420px;
}
.login-form .btn-fixed {
  width: 110px;
}
</style>

<?php include __DIR__ . '/../inc/footer.php'; ?>
