<?php require_once __DIR__ . '/init.php'; ?>
<?php
$page_title = $page_title ?? 'Cinema Portal';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h($page_title); ?></title>
  <link rel="stylesheet" href="/cinema-test/assets/css/style.css">
  <script defer src="/cinema-test/assets/js/validation.js"></script>
  <script defer src="/cinema-test/assets/js/ux.js"></script>
  <script defer src="/cinema-test/assets/js/admin-validators.js"></script>
  <link rel="icon" href="/cinema-test/assets/img/placeholder.svg">
  <meta name="description" content="Book tickets for movies and shows.">
</head>
<body>
  <div class="app-frame">
    <aside class="sidebar">
      <div class="sidebar-inner">
        <a class="brand" href="/cinema-test/index.php" aria-label="NECT home">
          <img class="logo" src="/cinema-test/assets/img/nect-logo.svg" alt="NECT logo">
        </a>
        <nav class="side-nav">
          <a href="/cinema-test/pages/movies.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/movies')!==false?'active':''; ?>">Movies</a>
          <a href="/cinema-test/pages/schedule.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/schedule')!==false?'active':''; ?>">Schedule</a>
          <a href="/cinema-test/pages/bookings.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/bookings')!==false?'active':''; ?>">My Booking</a>
          <?php $cartCount = function_exists('cart_detailed') ? count(cart_detailed()) : count(cart_get()); ?>
          <a href="/cinema-test/pages/cart.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/cart')!==false?'active':''; ?>">Cart (<?php echo (int)$cartCount; ?>)</a>
          <?php if (auth_is_admin()): ?>
            <a href="/cinema-test/pages/admin/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/pages/admin')!==false?'active':''; ?>">Admin</a>
          <?php endif; ?>
        </nav>
      </div>
    </aside>
    <div class="main-col">
      <header class="topbar">
        <div class="account">
          <?php if (auth_user()): ?>
            <span class="user-name"><?php $u=auth_user(); echo h($u['name'] ?? $u['email']); ?></span>
            <a class="btn btn-sm" href="/cinema-test/pages/logout.php">Log out</a>
          <?php else: ?>
            <a class="login-link" href="/cinema-test/pages/login.php"><span class="login-icon">👤</span> <span>Log In</span></a>
          <?php endif; ?>
        </div>
      </header>
      <main class="content container">
