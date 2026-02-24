<?php
// /cinema-test/pages/admin/index.php

$page_title = 'Admin';
include __DIR__ . '/../../inc/header.php';

if (!auth_is_admin()) {
  echo '<p>Admins only.</p>';
  include __DIR__ . '/../../inc/footer.php';
  exit;
}

// Seperates the functions into different sections. Display content depending on user input
$tab = $_GET['tab'] ?? 'dashboard';
$validTabs = ['dashboard', 'movies_add', 'movies_edit', 'coupons', 'sales', 'outbox'];
if (!in_array($tab, $validTabs, true)) {
  $tab = 'dashboard';
}
?>

<h1>Admin</h1>

<nav class="row gap" style="margin-bottom:16px; flex-wrap:wrap;">
  <a class="btn <?php echo $tab==='dashboard'    ? 'btn-primary' : 'btn-secondary'; ?>" href="?tab=dashboard">Overview</a>
  <a class="btn <?php echo $tab==='movies_add'   ? 'btn-primary' : 'btn-secondary'; ?>" href="?tab=movies_add">Add Movie</a>
  <a class="btn <?php echo $tab==='movies_edit'  ? 'btn-primary' : 'btn-secondary'; ?>" href="?tab=movies_edit">Manage Movies</a>
  <a class="btn <?php echo $tab==='sales'        ? 'btn-primary' : 'btn-secondary'; ?>" href="?tab=sales">Sales Report</a>
  <a class="btn <?php echo $tab==='coupons'      ? 'btn-primary' : 'btn-secondary'; ?>" href="?tab=coupons">Coupons</a>
  <a class="btn <?php echo $tab==='outbox'       ? 'btn-primary' : 'btn-secondary'; ?>" href="?tab=outbox">Email Outbox</a>
</nav>

<div class="admin-panel">
<?php
// merge function files into the index.php file
$embedded = true;

switch ($tab) {
  case 'movies_add':
    include __DIR__ . '/movies_add.php';
    break;

  case 'movies_edit':
    include __DIR__ . '/movies_edit.php';
    echo '<hr class="admin-divider">';
    include __DIR__ . '/movies.php';
    break;

  case 'coupons':
    include __DIR__ . '/coupons.php';
    break;

  case 'sales':
    include __DIR__ . '/sales.php';
    break;

  case 'outbox':
    include __DIR__ . '/outbox.php';
    break;

  case 'dashboard':
  default:
    // Seperate the admin functions into different tabs
    ?>
    <div class="grid grid-2">
      <div class="card"><div class="card-body">
        <h3>Movies</h3>
        <p>
          <a class="btn" href="?tab=movies_add">Add New Movie</a>
          <a class="btn btn-secondary" href="?tab=movies_edit">Edit Existing Movie</a>
        </p>
        <p class="small muted">
          Create new titles and add first time slot; or update details and add time slots to existing titles.
        </p>
      </div></div>
      <div class="card"><div class="card-body">
        <h3>Utilities</h3>
        <p>
          <a class="btn btn-secondary" href="?tab=sales">Sales Report</a>
          <a class="btn btn-secondary" href="?tab=coupons">Add Coupon</a>
          <a class="btn btn-secondary" href="?tab=outbox">Email Outbox</a>
        </p>
      </div></div>
    </div>
    <?php
    break;
}
?>
</div>

<style>
.admin-divider {
  margin: 32px 0;
  border: 0;
  border-top: 1px solid var(--border);
}
</style>

<?php include __DIR__ . '/../../inc/footer.php'; ?>