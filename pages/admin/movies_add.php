<?php
$embedded = $embedded ?? false;

if (!$embedded) {
  $page_title = 'Add New Movie';
  include __DIR__ . '/../../inc/header.php';
  if (!auth_is_admin()) {
    echo '<p>Admins only.</p>';
    include __DIR__ . '/../../inc/footer.php';
    exit;
  }
}
?>

<?php if (!$embedded): ?>
<h1>Add New Movie</h1>
<?php else: ?>
<h2>Add New Movie</h2>
<?php endif; ?>

<?php
$msg=''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $title = trim($_POST['title'] ?? '');
  $rating = $_POST['rating'] ?? 'PG';
  $duration = max(1, (int)($_POST['duration_min'] ?? 0));
  $genre = $_POST['genre'] ?? 'Drama';
  $subs = $_POST['subs'] ?? 'ENG';
  $release = null;
  $status = $_POST['status'] ?? 'now';
  $synopsis = trim($_POST['synopsis'] ?? '');
  $poster_path = trim($_POST['poster_path'] ?? '');

  $date_in = trim($_POST['slot_date'] ?? '');
  $time_in = trim($_POST['slot_time'] ?? '');
  $price = max(0, (float)($_POST['slot_price'] ?? 18.0));
  $screen_id = (int)($_POST['screen_id'] ?? 1);
  $start_at = null;
  if ($date_in && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_in)) {
    $date_sql = $date_in;
    if ($time_in && preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $time_in, $tm)) {
      $hh = $tm[1]; $mm = $tm[2]; $ss = isset($tm[3]) && $tm[3] !== '' ? $tm[3] : '00';
      $time_sql = "$hh:$mm:$ss";
      $start_at = $date_sql . ' ' . $time_sql;
    }
  }
  $minSlot = ($status === 'now') ? new DateTime('today 00:00') : new DateTime('tomorrow 00:00');
  $okSlot = false;
  if ($start_at) {
    $sa = new DateTime($start_at);
    $okSlot = ($sa >= $minSlot);
  }
  if (!$okSlot) $err = ($status === 'now') ? 'Provide a timeslot today or later.' : 'Provide a timeslot on or after tomorrow for Coming Soon.';
  if (!$title || $duration <= 0 || !$synopsis) $err = 'Please complete title, duration and synopsis.';

  if (!$err) {
    $stmt = db()->prepare("INSERT INTO movies (title, rating, duration_min, genre, subs, release_date, synopsis, status, poster_path) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssissssss', $title, $rating, $duration, $genre, $subs, $release, $synopsis, $status, $poster_path);
    $stmt->execute();
    $movie_id = db()->insert_id;
    $stmt = db()->prepare("INSERT INTO shows (movie_id, screen_id, start_at, base_price) VALUES (?,?,?,?)");
    $stmt->bind_param('iisd', $movie_id, $screen_id, $start_at, $price);
    $stmt->execute();
    $msg = 'Movie created with first timeslot.';
  }
}

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

<?php $minDateNow = (new DateTime('today'))->format('Y-m-d'); $minDateSoon = (new DateTime('tomorrow'))->format('Y-m-d'); ?>
<div class="card"><div class="card-body">
  <form method="post" class="grid grid-2">
    <label>Title<input type="text" name="title" required></label>
    <label>Rating
      <select name="rating">
        <option>G</option><option>PG</option><option>PG-13</option><option>M</option><option>MA15+</option><option>R</option>
      </select>
    </label>
    <label>Duration (min)
      <input class="int-pos" type="text" name="duration_min" pattern="^[1-9][0-9]*$" required>
    </label>
    <label>Genre
      <select name="genre">
        <option>Action</option><option>Adventure</option><option>Animation</option><option>Comedy</option><option>Drama</option><option>Fantasy</option><option>Horror</option><option>Musical</option><option>Sci-Fi</option><option>Thriller</option>
      </select>
    </label>
    <label>Subs
      <select name="subs">
        <option>ENG</option><option>CHI</option><option>IND</option><option>JPN</option><option>KOR</option>
      </select>
    </label>
    <label>Status
      <select name="status" id="status-select"><option value="now">now</option><option value="soon">soon</option></select>
    </label>
    <label>Poster Path<input type="text" name="poster_path" placeholder="/cinema-test/assets/posters/xyz.jpg"></label>
    <label class="col">Synopsis<input type="text" name="synopsis" required></label>
    <div class="col"><hr><h3>Add First Timeslot</h3></div>
    <label>Date<input type="date" name="slot_date" id="slot-date" min="<?php echo $minDateNow; ?>" required></label>
    <label>Time<input type="time" name="slot_time" step="60" required></label>
    <label>Screen
      <select name="screen_id">
        <?php foreach ($screens as $sc): ?>
          <option value="<?php echo (int)$sc['id']; ?>"><?php echo h($sc['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Price
      <input class="money-pos" type="text" name="slot_price" pattern="^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$" value="18.00" required>
    </label>
    <button class="btn" type="submit">Create Movie</button>
  </form>
</div></div>

<script>
  (function(){
    const statusSel = document.getElementById('status-select');
    const dateInput = document.getElementById('slot-date');
    if (!statusSel || !dateInput) return;
    const minNow = '<?php echo $minDateNow; ?>';
    const minSoon = '<?php echo $minDateSoon; ?>';
    const applyMin = () => {
      dateInput.min = (statusSel.value === 'soon') ? minSoon : minNow;
      if (dateInput.value && dateInput.value < dateInput.min) dateInput.value = dateInput.min;
    };
    statusSel.addEventListener('change', applyMin);
    applyMin();
  })();
</script>

<?php if (!$embedded) { include __DIR__ . '/../../inc/footer.php'; } ?>