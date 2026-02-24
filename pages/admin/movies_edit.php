<?php
$embedded = $embedded ?? false;

if (!$embedded) {
  $page_title = 'Edit Existing Movie';
  include __DIR__ . '/../../inc/header.php';
  if (!auth_is_admin()) {
    echo '<p>Admins only.</p>';
    include __DIR__ . '/../../inc/footer.php';
    exit;
  }
}
?>

<?php if (!$embedded): ?>
<h1>Edit Existing Movie</h1>
<?php else: ?>
<h2>Edit Existing Movie</h2>
<?php endif; ?>

<?php
$msg=''; $err='';
// Handle updates
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'update_movie') {
    $id = (int)$_POST['id'];
    $dur = max(1, (int)$_POST['duration_min']);
    $stmt = db()->prepare("UPDATE movies SET rating=?, genre=?, duration_min=?, status=?, synopsis=?, subs=?, poster_path=? WHERE id=?");
    $stmt->bind_param('ssissssi', $_POST['rating'], $_POST['genre'], $dur, $_POST['status'], $_POST['synopsis'], $_POST['subs'], $_POST['poster_path'], $id);
    $stmt->execute();
    $msg = 'Movie updated.';
  } elseif ($action === 'add_slot') {
    $id = (int)$_POST['id'];
    $date_in = $_POST['date'];
    $time_in = $_POST['time'];
    $price = max(0, (float)$_POST['price']);
    $screen_id = (int)$_POST['screen_id'];
    $start_at = null;
    if ($date_in && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_in)) {
      $date_sql = $date_in;
      if ($time_in && preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $time_in, $tm)) {
        $hh = $tm[1]; $mm = $tm[2]; $ss = isset($tm[3]) && $tm[3] !== '' ? $tm[3] : '00';
        $time_sql = "$hh:$mm:$ss";
        $start_at = $date_sql . ' ' . $time_sql;
      }
    }
    if ($start_at) {
      $tomorrow = new DateTime('tomorrow 00:00');
      if (new DateTime($start_at) >= $tomorrow) {
        $stmt = db()->prepare("INSERT INTO shows (movie_id, screen_id, start_at, base_price) VALUES (?,?,?,?)");
        $stmt->bind_param('iisd', $id, $screen_id, $start_at, $price);
        $stmt->execute();
        $msg = 'Timeslot added.';
      } else {
        $err = 'Timeslot must be tomorrow or later.';
      }
    } else { $err = 'Invalid date/time.'; }
  } elseif ($action === 'uploadposter') {
    $id = (int)$_POST['id'];
    if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
      $dir = __DIR__ . '/../../assets/posters'; if (!is_dir($dir)) @mkdir($dir, 0775, true);
      $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $fn = 'poster_' . $id . '_' . time() . '.' . $ext; $dest = $dir . '/' . $fn;
        if (@move_uploaded_file($_FILES['poster']['tmp_name'], $dest)) {
          $web = '/cinema-test/assets/posters/' . $fn;
          $stmt = db()->prepare("UPDATE movies SET poster_path=? WHERE id=?"); $stmt->bind_param('si', $web, $id); $stmt->execute();
          $msg = 'Poster updated.';
        }
      }
    }
  }
}

$res = db()->query("SELECT * FROM movies ORDER BY title ASC");
$movies = [];
while ($row = $res->fetch_assoc()) $movies[] = $row;
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : ((count($movies)>0)? (int)$movies[0]['id'] : 0);
$current = null; foreach ($movies as $row) if ((int)$row['id']===$movie_id) { $current = $row; break; }

$required = ['Screen 1', 'Screen 2', 'IMAX 1'];
$stmtScr = db()->prepare("SELECT MIN(id) AS id, name FROM screens WHERE name IN ('Screen 1','Screen 2','IMAX 1') GROUP BY name ORDER BY FIELD(name,'Screen 1','Screen 2','IMAX 1')");
$screens = fetch_all($stmtScr);
$have = array_column($screens, 'id', 'name');
if (count($have) < count($required)) {
  $cinema_id = 1;
  foreach ($required as $r) {
    if (!isset($have[$r])) {
      $cap = ($r === 'IMAX 1') ? 300 : 120;
      $stmtI = db()->prepare("INSERT INTO screens (cinema_id, name, capacity) VALUES (?,?,?)");
      $stmtI->bind_param('isi', $cinema_id, $r, $cap);
      $stmtI->execute();
    }
  }
  $stmtScr = db()->prepare("SELECT MIN(id) AS id, name FROM screens WHERE name IN ('Screen 1','Screen 2','IMAX 1') GROUP BY name ORDER BY FIELD(name,'Screen 1','Screen 2','IMAX 1')");
  $screens = fetch_all($stmtScr);
}
?>

<?php if ($msg): ?><p class="badge" style="background:#264;"><?php echo h($msg); ?></p><?php endif; ?>
<?php if ($err): ?><p class="badge" style="background:#633;"><?php echo h($err); ?></p><?php endif; ?>

<form method="get" class="filters">
  <?php if ($embedded): ?>
    <input type="hidden" name="tab" value="movies_edit">
  <?php endif; ?>
  <label>Choose Movie
    <select name="id" onchange="this.form.submit()">
      <?php foreach ($movies as $mv): ?>
        <option value="<?php echo (int)$mv['id']; ?>" <?php echo $movie_id===(int)$mv['id']?'selected':''; ?>>
          <?php echo h($mv['title']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
</form>

<?php if ($current): ?>
<div class="card"><div class="card-body">
  <h3><?php echo h($current['title']); ?></h3>
  <form method="post" class="grid grid-2">
    <input type="hidden" name="action" value="update_movie">
    <input type="hidden" name="id" value="<?php echo (int)$current['id']; ?>">
    <label>Rating
      <select name="rating">
        <?php foreach (['G','PG','PG-13','M','MA15+','R'] as $r): ?>
          <option <?php echo $current['rating']===$r?'selected':''; ?>><?php echo $r; ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Genre
      <select name="genre">
        <?php foreach (['Action','Adventure','Animation','Comedy','Drama','Fantasy','Horror','Musical','Sci-Fi','Thriller'] as $g): ?>
          <option <?php echo $current['genre']===$g?'selected':''; ?>><?php echo $g; ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Duration (min)
      <input class="int-pos" type="text" name="duration_min" pattern="^[1-9][0-9]*$" value="<?php echo (int)$current['duration_min']; ?>">
    </label>
    <label>Status
      <select name="status"><option value="now" <?php echo $current['status']==='now'?'selected':''; ?>>now</option><option value="soon" <?php echo $current['status']==='soon'?'selected':''; ?>>soon</option></select>
    </label>
    <label>Subs
      <select name="subs">
        <?php foreach (['ENG','CHI','IND','JPN','KOR'] as $s): ?>
          <option <?php echo $current['subs']===$s?'selected':''; ?>><?php echo $s; ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Poster Path<input type="text" name="poster_path" value="<?php echo h($current['poster_path']); ?>" placeholder="/cinema-test/assets/posters/xyz.jpg"></label>
    <label class="col">Synopsis<input type="text" name="synopsis" value="<?php echo h($current['synopsis']); ?>"></label>
    <button class="btn btn-fixed" type="submit">Save Changes</button>
  </form>
  <form method="post" enctype="multipart/form-data" style="margin-top:10px">
    <input type="hidden" name="action" value="uploadposter"><input type="hidden" name="id" value="<?php echo (int)$current['id']; ?>">
    <input type="file" name="poster" accept="image/*"><button class="btn btn-sm" type="submit">Upload Poster</button>
  </form>
</div></div>

<div class="card"><div class="card-body">
  <h3>Add Timeslot</h3>
  <?php $minDate = (new DateTime('tomorrow'))->format('Y-m-d'); ?>
  <form method="post" class="row gap" style="flex-wrap:wrap">
    <input type="hidden" name="action" value="add_slot">
    <input type="hidden" name="id" value="<?php echo (int)$current['id']; ?>">
    <label>Date<input type="date" name="date" min="<?php echo $minDate; ?>" required></label>
    <label>Time<input type="time" name="time" step="60" required></label>
    <label>Screen
      <select name="screen_id">
        <?php foreach ($screens as $sc): ?>
          <option value="<?php echo (int)$sc['id']; ?>"><?php echo h($sc['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Price
      <input class="money-pos" type="text" name="price" pattern="^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$" value="18.00" required>
    </label>
    <button class="btn btn-fixed" type="submit">Add Timeslot</button>
  </form>

  <?php
    $stmt = db()->prepare("SELECT s.id, s.start_at, s.base_price, sc.name AS screen_name FROM shows s JOIN screens sc ON sc.id=s.screen_id WHERE s.movie_id=? ORDER BY s.start_at ASC");
    $stmt->bind_param('i', $current['id']);
    $slots = fetch_all($stmt);
  ?>
  <div class="table-wrap"><table class="table timeslots-table"><thead><tr><th>Date</th><th>Time</th><th>Screen</th><th>Price</th></tr></thead><tbody>
    <?php foreach ($slots as $slot): $dt = new DateTime($slot['start_at']); ?>
      <tr>
        <td><?php echo $dt->format('d/m/Y'); ?></td>
        <td><?php echo $dt->format('H:i'); ?></td>
        <td><?php echo h($slot['screen_name']); ?></td>
        <td>$<?php echo number_format((float)$slot['base_price'], 2); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
</div></div>
<?php endif; ?>

<?php if (!$embedded) { include __DIR__ . '/../../inc/footer.php'; } ?>

<style>
.timeslots-table thead th { border-bottom: 1px solid var(--border); }
.timeslots-table tbody tr:first-child td { padding-top: 16px; }
</style>
