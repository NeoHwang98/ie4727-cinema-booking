<?php
$embedded = $embedded ?? false;

if (!$embedded) {
  $page_title = 'Edit Movies';
  include __DIR__ . '/../../inc/header.php';
  if (!auth_is_admin()) {
    echo '<p>Admins only.</p>';
    include __DIR__ . '/../../inc/footer.php';
    exit;
  }
}
?>

<?php if (!$embedded): ?>
<h1>Edit Movies</h1>
<?php else: ?>
<h2>Edit Movies (Inline)</h2>
<?php endif; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $stmt = db()->prepare("INSERT INTO movies (title, rating, duration_min, genre, subs, release_date, synopsis, status, poster_path) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssissssss', $_POST['title'], $_POST['rating'], $_POST['duration_min'], $_POST['genre'], $_POST['subs'], $_POST['release_date'], $_POST['synopsis'], $_POST['status'], $_POST['poster_path']);
    $stmt->execute();
  } elseif ($action === 'delete') {
    $id = (int)$_POST['id'];
    db()->query("DELETE FROM movies WHERE id = $id");
  } elseif ($action === 'uploadposter') {
    $id = (int)$_POST['id'];
    if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
      $dir = __DIR__ . '/../../assets/posters';
      if (!is_dir($dir)) @mkdir($dir, 0775, true);
      $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) { /* ignore invalid */ }
      else {
        $fn = 'poster_' . $id . '_' . time() . '.' . $ext;
        $dest = $dir . '/' . $fn;
        if (@move_uploaded_file($_FILES['poster']['tmp_name'], $dest)) {
          $web = '/cinema-test/assets/posters/' . $fn;
          $stmt = db()->prepare("UPDATE movies SET poster_path = ? WHERE id = ?");
          $stmt->bind_param('si', $web, $id);
          $stmt->execute();
        }
      }
    }
  } elseif ($action === 'update') {
    $id = (int)$_POST['id'];
    $title = $_POST['title'];
    $rating = $_POST['rating'];
    $duration = (int)$_POST['duration_min'];
    $genre = $_POST['genre'];
    $subs = $_POST['subs'];
    $release = $_POST['release_date'] ?: null;
    $status = $_POST['status'];
    $synopsis = $_POST['synopsis'];
    $poster_path = $_POST['poster_path'] ?: null;
    $stmt = db()->prepare("UPDATE movies SET title=?, rating=?, duration_min=?, genre=?, subs=?, release_date=?, synopsis=?, status=?, poster_path=? WHERE id=?");
    $stmt->bind_param('ssissssssi', $title, $rating, $duration, $genre, $subs, $release, $synopsis, $status, $poster_path, $id);
    $stmt->execute();
  } elseif ($action === 'add_show') {
    $movie_id = (int)$_POST['movie_id'];
    $screen_id = (int)$_POST['screen_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $price = (float)$_POST['price'];
    $start_at = $date . ' ' . $time . ':00';
    $stmt = db()->prepare("INSERT INTO shows (movie_id, screen_id, start_at, base_price) VALUES (?,?,?,?)");
    $stmt->bind_param('iisd', $movie_id, $screen_id, $start_at, $price);
    $stmt->execute();
  } elseif ($action === 'del_show') {
    $show_id = (int)$_POST['show_id'];
    db()->query("DELETE FROM shows WHERE id = $show_id");
  } elseif ($action === 'update_show') {
    $show_id = (int)$_POST['show_id'];
    $price = (float)$_POST['price'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $start_at = $date . ' ' . $time . ':00';
    $stmt = db()->prepare("UPDATE shows SET start_at=?, base_price=? WHERE id=?");
    $stmt->bind_param('sdi', $start_at, $price, $show_id);
    $stmt->execute();
  }
}
$rows = db()->query("SELECT * FROM movies ORDER BY id DESC");
$stmtScr = db()->prepare("SELECT id, name FROM screens ORDER BY id ASC");
$screens = fetch_all($stmtScr);
?>

<div class="card"><div class="card-body">
  <h3>Add Movie</h3>
  <form method="post" class="grid grid-2">
    <input type="hidden" name="action" value="add">
    <label>Title<input type="text" name="title" required></label>
    <label>Rating
      <select name="rating">
        <option>G</option><option>PG</option><option>PG-13</option><option>M</option><option>MA15+</option><option>R</option>
      </select>
    </label>
    <label>Duration (min)<input type="number" name="duration_min" required></label>
    <label>Genre
      <select name="genre">
        <option>Action</option><option>Adventure</option><option>Animation</option><option>Comedy</option><option>Drama</option><option>Fantasy</option><option>Horror</option><option>Musical</option><option>Sci-Fi</option><option>Thriller</option>
      </select>
    </label>
    <label>Subs<input type="text" name="subs" value="ENG"></label>
    <label>Release<input type="date" name="release_date"></label>
    <label>Status<select name="status"><option value="now">now</option><option value="soon">soon</option></select></label>
    <label>Poster Path (optional)<input type="text" name="poster_path" placeholder="/cinema-test/assets/posters/xyz.jpg"></label>
    <label class="col">Synopsis<input type="text" name="synopsis" required></label>
    <button class="btn" type="submit">Add</button>
  </form>
</div></div>

<h3>All Movies</h3>
<div class="table-wrap"><table class="table"><thead><tr><th>ID</th><th>Title</th><th>Genre</th><th>Status</th><th>Poster</th><th>Actions</th></tr></thead><tbody>
<?php while ($m = $rows->fetch_assoc()): ?>
  <tr>
    <td><?php echo (int)$m['id']; ?></td>
    <td><?php echo h($m['title']); ?></td>
    <td><?php echo h($m['genre']); ?></td>
    <td><?php echo h($m['status']); ?></td>
    <td>
      <?php if (!empty($m['poster_path'])): ?><img src="<?php echo h($m['poster_path']); ?>" alt="poster" style="height:48px;border-radius:6px;border:1px solid var(--border);"><?php endif; ?>
      <form method="post" enctype="multipart/form-data" style="margin-top:6px;">
        <input type="hidden" name="action" value="uploadposter">
        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
        <input type="file" name="poster" accept="image/*">
        <button class="btn btn-sm" type="submit">Upload</button>
      </form>
    </td>
    <td>
      <button class="btn btn-sm" type="button" onclick="document.getElementById('edit-<?php echo (int)$m['id']; ?>').classList.toggle('open')">Edit</button>
      <form method="post" onsubmit="return confirm('Delete movie?');" style="display:inline-block"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>"><button class="btn btn-danger btn-sm">Delete</button></form>
    </td>
  </tr>
  <tr id="edit-<?php echo (int)$m['id']; ?>" class="edit-row">
    <td colspan="6">
      <div class="edit-card">
        <form method="post" class="grid grid-2">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
          <label>Title<input type="text" name="title" value="<?php echo h($m['title']); ?>" required></label>
          <label>Rating
            <select name="rating">
              <?php foreach (['G','PG-13','PG','M','MA15+','R'] as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo ($m['rating']===$r)?'selected':''; ?>><?php echo $r; ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Duration (min)<input type="number" name="duration_min" value="<?php echo (int)$m['duration_min']; ?>" required></label>
          <label>Genre
            <select name="genre">
              <?php foreach (['Action','Adventure','Animation','Comedy','Drama','Fantasy','Horror','Musical','Sci-Fi','Thriller'] as $g): ?>
                <option value="<?php echo $g; ?>" <?php echo ($m['genre']===$g)?'selected':''; ?>><?php echo $g; ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Subs<input type="text" name="subs" value="<?php echo h($m['subs']); ?>"></label>
          <label>Release<input type="date" name="release_date" value="<?php echo h($m['release_date']); ?>"></label>
          <label>Status
            <select name="status">
              <option value="now" <?php echo $m['status']==='now'?'selected':''; ?>>now</option>
              <option value="soon" <?php echo $m['status']==='soon'?'selected':''; ?>>soon</option>
            </select>
          </label>
          <label>Poster Path<input type="text" name="poster_path" value="<?php echo h($m['poster_path']); ?>" placeholder="/cinema-test/assets/posters/xyz.jpg"></label>
          <label class="col">Synopsis<input type="text" name="synopsis" value="<?php echo h($m['synopsis']); ?>"></label>
          <button class="btn" type="submit">Save</button>
        </form>

        <div class="shows">
          <h4>Timeslots</h4>
          <form method="post" class="row gap align-center" style="flex-wrap:wrap">
            <input type="hidden" name="action" value="add_show">
            <input type="hidden" name="movie_id" value="<?php echo (int)$m['id']; ?>">
            <label>Date<input type="date" name="date" required></label>
            <label>Time<input type="time" name="time" required step="1800"></label>
            <label>Screen
              <select name="screen_id">
                <?php foreach ($screens as $sc): ?>
                  <option value="<?php echo (int)$sc['id']; ?>"><?php echo h($sc['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Price<input type="number" name="price" step="0.01" value="18.00"></label>
            <button class="btn btn-sm" type="submit">Add Slot</button>
          </form>

          <?php
            $stmt = db()->prepare("SELECT s.id, s.start_at, s.base_price, sc.name AS screen_name FROM shows s JOIN screens sc ON sc.id=s.screen_id WHERE s.movie_id=? ORDER BY s.start_at ASC");
            $stmt->bind_param('i', $m['id']);
            $mslots = fetch_all($stmt);
          ?>
          <div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Time</th><th>Screen</th><th>Price</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($mslots as $slot): $dt = new DateTime($slot['start_at']); $fid = 'fslot-' . (int)$slot['id']; ?>
              <tr>
                <td><input type="date" name="date" value="<?php echo $dt->format('Y-m-d'); ?>" form="<?php echo $fid; ?>"></td>
                <td><input type="time" name="time" value="<?php echo $dt->format('H:i'); ?>" step="1800" form="<?php echo $fid; ?>"></td>
                <td><?php echo h($slot['screen_name']); ?></td>
                <td><input type="number" name="price" step="0.01" value="<?php echo number_format((float)$slot['base_price'],2,'.',''); ?>" style="width:110px" form="<?php echo $fid; ?>"></td>
                <td class="nowrap">
                  <form id="<?php echo $fid; ?>" method="post" style="display:inline-block">
                    <input type="hidden" name="action" value="update_show">
                    <input type="hidden" name="show_id" value="<?php echo (int)$slot['id']; ?>">
                    <button class="btn btn-sm" type="submit">Save</button>
                  </form>
                  <form method="post" style="display:inline-block" onsubmit="return confirm('Delete timeslot?');">
                    <input type="hidden" name="action" value="del_show">
                    <input type="hidden" name="show_id" value="<?php echo (int)$slot['id']; ?>">
                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody></table></div>
        </div>
      </div>
    </td>
  </tr>
<?php endwhile; ?>
</tbody></table></div>

<?php if (!$embedded) { include __DIR__ . '/../../inc/footer.php'; } ?>

<style>
.edit-row { display: none; }
.edit-row.open { display: table-row; }
.edit-card { background: var(--card); border:1px solid var(--border); border-radius:12px; padding:12px; }
.shows h4 { margin: 12px 0 6px; }
.nowrap { white-space: nowrap; }
.table td, .table th { vertical-align: top; }
</style>
