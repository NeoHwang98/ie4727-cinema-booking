<?php $page_title = 'Booking Confirmation'; include __DIR__ . '/../inc/header.php'; ?>
<?php
ensure_post();

// Items (respect selection when provided)
$items = cart_detailed();
$selPost = isset($_POST['selected']) ? array_map('intval', (array)$_POST['selected']) : [];
$selSess = (isset($_SESSION['cart_selection']) && is_array($_SESSION['cart_selection'])) ? array_map('intval', $_SESSION['cart_selection']) : [];

// Filter to the selected indices when available; otherwise use full cart
$indices = !empty($selPost) ? $selPost : (!empty($selSess) ? $selSess : null);
if ($indices) {
  $items = array_values(array_filter($items, function($it) use ($indices){
    return in_array((int)$it['index'], $indices, true);
  }));
  if (!$items) {
    echo '<h1>No items were checked</h1><p>Please select items again.</p><p><a class="btn" href="/cinema-test/pages/cart.php">Back to cart</a></p>';
    include __DIR__ . '/../inc/footer.php';
    exit;
  }
}

// Customer details
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone_sg = trim($_POST['phone_sg'] ?? '');
$notes = '';

// Validate
$errors = [];
if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter your full name.';
if (!valid_email($email)) $errors[] = 'Please enter a valid email address.';
if (!preg_match('/^[89][0-9]{7}$/', $phone_sg)) $errors[] = 'Please enter a valid Singapore mobile (8 digits, starts with 8 or 9).';

// Payment
$card = preg_replace('/\s+/', '', $_POST['card'] ?? '');
$card_name = trim($_POST['card_name'] ?? '');
$expiry_month = trim($_POST['expiry_month'] ?? '');
$cvv = trim($_POST['cvv'] ?? '');
if (!preg_match('/^[0-9]{16}$/', $card)) $errors[] = 'Invalid card number.';
if (strlen($card_name) < 2) $errors[] = 'Invalid card name.';
if (!preg_match('/^\d{4}-\d{2}$/', $expiry_month)) $errors[] = 'Invalid expiry month.';
if (!preg_match('/^[0-9]{3}$/', $cvv)) $errors[] = 'Invalid CVV.';

if ($errors) {
  echo '<h1>Fix the following:</h1><ul>';
  foreach ($errors as $e) echo '<li>' . h($e) . '</li>';
  echo '</ul><p><a class="btn" href="/cinema-test/pages/checkout.php">Back to checkout</a></p>';
  include __DIR__ . '/../inc/footer.php';
  exit;
}

// Coupon (optional; if user logged in)
$coupon_code = trim($_POST['coupon'] ?? '');
$coupon_id = null; $discount_amount = 0.0;
if ($coupon_code !== '' && auth_user()) {
  $coupon = find_coupon($coupon_code);
  if ($coupon) {
    $totAmt = 0.0; foreach ($items as $it) { $totAmt += (float)$it['subtotal']; }
    $discount_amount = apply_coupon_row($coupon, $totAmt);
    if ($discount_amount > 0) { $coupon_id = (int)$coupon['id']; }
  }
}

// Seat conflict checks
foreach ($items as $it) {
  if (!empty($it['seats'])) {
    $show_id = (int)$it['show_id'];
    $stmt = db()->prepare("SELECT seat_labels FROM booking_items WHERE show_id = ? AND seat_labels IS NOT NULL AND seat_labels <> ''");
    $stmt->bind_param('i', $show_id);
    $takenRows = fetch_all($stmt);
    $taken = [];
    foreach ($takenRows as $r) { foreach (explode(',', $r['seat_labels']) as $s) { $s=trim($s); if ($s!=='') $taken[$s]=true; } }
    foreach ($it['seats'] as $s) {
      if (isset($taken[$s])) {
        echo '<h1>Seat Unavailable</h1>';
        echo '<p>One or more selected seats are no longer available. Please choose different seats.</p>';
        echo '<p><a class="btn" href="/cinema-test/pages/details.php?id='.$show_id.'">Back to seat selection</a></p>';
        include __DIR__ . '/../inc/footer.php';
        exit;
      }
    }
  }
}

// Create booking
$result = create_booking($name, $email, $phone_sg, $notes, $items, $coupon_id, $discount_amount);
if (!$result['ok']) {
  echo '<h1>Could not complete booking</h1>';
  echo '<p>' . h($result['error']) . '</p>';
  echo '<p><a class="btn" href="/cinema-test/pages/cart.php">Return to shortlist</a></p>';
  include __DIR__ . '/../inc/footer.php';
  exit;
}

$booking_id = (int)$result['booking_id'];
$totals = $result['totals'];

// Email
$subject = 'Your NECT Booking #' . $booking_id;
$body = "Thank you, $name! Your booking details: \n\n";
foreach ($items as $it) {
  $s = $it['show'];
  $seats = !empty($it['seats']) ? (' seats: '.implode(',', $it['seats'])) : '';
  $body .= '- ' . $s['movie_title'] . ' — ' . date('D, d M Y H:i', strtotime($s['start_at'])) . ' — ' . $s['screen_name'] . $seats . "\n";
}
$body .= "\nTotal tickets: " . $totals['tickets'] . "\nTotal amount: $" . number_format((float)$totals['amount'] - $discount_amount, 2) . "\n\n";
$body .= "Reference: #$booking_id\n";

$full_phone = $phone_sg;
require_once __DIR__ . '/../inc/mailer.php';

// Build HTML email with details, poster and QR
$safe = function($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
// Compose ticket rows and collect posters
$imgMap = [];
$rowsHtml = '';
foreach ($items as $idx => $it) {
  $s = $it['show'];
  $seats = !empty($it['seats']) ? implode(', ', $it['seats']) : '';
  $when = date('D, d M Y H:i', strtotime($s['start_at']));
  $posterPath = $s['poster_path'] ?? '';
  if (!$posterPath && !empty($s['movie_title'])) {
    // try lookup via movie id
    if (!empty($s['movie_id'])) { $mv = get_movie((int)$s['movie_id']); if ($mv && !empty($mv['poster_path'])) $posterPath = $mv['poster_path']; }
  }
  $cid = 'poster'.($idx+1);
  // map web path to filesystem
  $fs = '';
  if ($posterPath) {
    $rel = str_replace(['/cinema-test/','\\'], ['','/'], $posterPath);
    $try = dirname(__DIR__).'/'.$rel; if (is_file($try)) $fs = $try;
  }
  if ($fs && is_readable($fs)) {
    $imgMap[$cid] = ['data' => file_get_contents($fs), 'type' => (str_ends_with(strtolower($fs),'.jpg')||str_ends_with(strtolower($fs),'.jpeg'))?'image/jpeg':'image/png', 'name' => basename($fs)];
    $imgTag = '<td style="width:120px;padding:8px;"><img src="cid:'.$cid.'" alt="poster" style="width:120px;height:auto;border-radius:6px;display:block;"></td>';
  } else {
    $imgTag = '<td style="width:120px;padding:8px;"></td>';
  }
  $rowsHtml .= '<tr>'.$imgTag.'<td style="padding:8px 12px;vertical-align:top;">'
    .'<div style="font-weight:700;font-size:16px;">'.$safe($s['movie_title']).'</div>'
    .'<div style="color:#666;margin:2px 0 6px;">'.$safe($when).' - '.$safe($s['screen_name']).'</div>'
    .($seats!==''?'<div>Seats: '.$safe($seats).'</div>':'')
    .'<div style="color:#333;margin-top:4px;">'.$it['tickets'].' @ $'.number_format((float)$it['price_each'],2).' = $'.number_format((float)$it['subtotal'],2).'</div>'
    .'</td></tr>';
}

// Generate simple QR-like PNG for the booking reference
$qrData = 'NECT|B:'.$booking_id.'|T:'.$totals['tickets'].'|$'.number_format((float)$totals['amount'] - $discount_amount, 2);
$qrPng = function(string $text): string {
  if (!function_exists('imagecreatetruecolor')) return '';
  $hash = md5($text);
  $size = 21; $scale = 6; $margin = 2; $imgSize = ($size + 2*$margin) * $scale;
  $im = imagecreatetruecolor($imgSize, $imgSize);
  $white = imagecolorallocate($im, 255,255,255); $black = imagecolorallocate($im, 0,0,0);
  imagefilledrectangle($im, 0,0, $imgSize, $imgSize, $white);
  $bits = '';
  for ($i=0;$i<strlen($hash);$i++) { $bits .= str_pad(base_convert($hash[$i],16,2),4,'0',STR_PAD_LEFT); }
  $p=0; for($y=0;$y<$size;$y++){ for($x=0;$x<$size;$x++){ $b = ($bits[$p%strlen($bits)] === '1'); $p++; if ($b){
    imagefilledrectangle($im, ($x+$margin)*$scale, ($y+$margin)*$scale, ($x+$margin+1)*$scale-1, ($y+$margin+1)*$scale-1, $black);
  }}}
  ob_start(); imagepng($im); $png = ob_get_clean(); imagedestroy($im); return $png ?: '';
};
$qrBinary = $qrPng($qrData);
if ($qrBinary !== '') { $imgMap['qrcode'] = ['data'=>$qrBinary,'type'=>'image/png','name'=>'qr.png']; }

$html = '<div style="font-family:Segoe UI,Arial,sans-serif;color:#111;">'
  .'<h2 style="margin:0 0 8px;">Thank you, '.$safe($name).'!</h2>'
  .'<div style="margin-bottom:10px;">Booking reference: <strong>#'.$booking_id.'</strong></div>'
  .'<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:640px;border:1px solid #ddd;border-radius:8px;overflow:hidden;">'
  .$rowsHtml
  .'</table>'
  .($qrBinary!==''?'<div style="margin-top:12px;"><img src="cid:qrcode" alt="QR code" style="width:160px;height:160px;border:1px solid #ddd;padding:4px;border-radius:6px;"></div>':'')
  .'<div style="margin-top:12px;color:#333;">Total tickets: '.$totals['tickets'].'<br>Total amount: $'.number_format((float)$totals['amount'] - $discount_amount, 2).'</div>'
  .'</div>';

$sent = send_mail_html($email, $subject, $html, $imgMap);
log_email($email, $subject, 'HTML email sent', $sent ? 'sent' : 'failed');

// Remove only selected items if provided; otherwise clear all
$selected = isset($_POST['selected']) ? array_map('intval', (array)$_POST['selected']) : null;
if ($selected) { cart_remove_indices($selected); }
else { cart_clear(); }
// Clear temporary selection
if (isset($_SESSION['cart_selection'])) unset($_SESSION['cart_selection']);

header('Location: /cinema-test/pages/acknowledgement.php?id=' . $booking_id);
exit;
