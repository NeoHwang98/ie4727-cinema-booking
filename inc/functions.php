<?php
require_once __DIR__ . '/db.php';

function db(): mysqli { return get_db(); }

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function fetch_all(mysqli_stmt $stmt): array {
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        $res->free();
    }
    $stmt->close();
    return $rows;
}

function fetch_one(mysqli_stmt $stmt): ?array {
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if ($res) $res->free();
    $stmt->close();
    return $row ?: null;
}

// Data access helpers
function get_movies(?string $status = null, ?string $query = null, ?string $genre = null): array {
    $sql = "SELECT * FROM movies";
    $conds = [];
    $params = [];
    $types = '';
    if ($status !== null) { $conds[] = "status = ?"; $params[] = $status; $types .= 's'; }
    if ($query !== null && $query !== '') { $conds[] = "title LIKE ?"; $params[] = "%$query%"; $types .= 's'; }
    if ($genre !== null && $genre !== '') { $conds[] = "genre = ?"; $params[] = $genre; $types .= 's'; }
    if ($conds) { $sql .= ' WHERE ' . implode(' AND ', $conds); }
    $sql .= ' ORDER BY title ASC';
    $stmt = db()->prepare($sql);
    if ($params) { $stmt->bind_param($types, ...$params); }
    return fetch_all($stmt);
}

function get_movie(int $id): ?array {
    $stmt = db()->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param('i', $id);
    return fetch_one($stmt);
}

function get_cinemas(): array {
    $stmt = db()->prepare("SELECT * FROM cinemas ORDER BY name ASC");
    return fetch_all($stmt);
}

function get_shows($cinema_id = null, ?string $date = null, ?int $movie_id = null): array {
    // Single-location mode: no cinemas table required
    $sql = "SELECT s.*, m.title AS movie_title, sc.name AS screen_name,
                   sc.capacity, (sc.capacity - s.seats_sold) AS seats_left,
                   '' AS cinema_name, '' AS suburb
            FROM shows s
            JOIN movies m ON m.id = s.movie_id
            JOIN screens sc ON sc.id = s.screen_id";
    $conds = [];
    $params = [];
    $types = '';
    // cinema_id filter ignored in single-location mode
    if ($movie_id !== null) { $conds[] = 'm.id = ?'; $params[] = $movie_id; $types .= 'i'; }
    if ($date !== null && $date !== '') {
        $conds[] = 'DATE(s.start_at) = ?'; $params[] = $date; $types .= 's';
    } else {
        $conds[] = 's.start_at >= NOW()';
    }
    if ($conds) { $sql .= ' WHERE ' . implode(' AND ', $conds); }
    $sql .= ' ORDER BY s.start_at ASC';
    $stmt = db()->prepare($sql);
    if ($params) { $stmt->bind_param($types, ...$params); }
    return fetch_all($stmt);
}

function get_show(int $id): ?array {
    // Single-location mode: no cinemas table required
    $stmt = db()->prepare("SELECT s.*, m.title AS movie_title, m.duration_min, sc.name AS screen_name,
                                  sc.capacity, (sc.capacity - s.seats_sold) AS seats_left,
                                  '' AS cinema_name, '' AS suburb
                           FROM shows s
                           JOIN movies m ON m.id = s.movie_id
                           JOIN screens sc ON sc.id = s.screen_id
                           WHERE s.id = ?");
    $stmt->bind_param('i', $id);
    return fetch_one($stmt);
}

// Cart (shortlist) stored in session as ordered array of items
function cart_init(): void { if (!isset($_SESSION['cart'])) $_SESSION['cart'] = []; }

function cart_get(): array { cart_init(); return $_SESSION['cart']; }

function cart_add(int $show_id, int $tickets, array $seats = [], float $unit_price = 0.0): void {
    cart_init();
    $tickets = max(1, min(10, $tickets));
    $item = ['show_id' => $show_id, 'tickets' => $tickets];
    if (!empty($seats)) { $item['seats'] = $seats; }
    if ($unit_price > 0) { $item['unit_price'] = $unit_price; }
    $_SESSION['cart'][] = $item;
}

function cart_remove(int $index): void {
    cart_init();
    if (isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
    }
}

function cart_move(int $index, string $direction): void {
    cart_init();
    $n = count($_SESSION['cart']);
    if (!isset($_SESSION['cart'][$index])) return;
    if ($direction === 'up' && $index > 0) {
        $tmp = $_SESSION['cart'][$index-1];
        $_SESSION['cart'][$index-1] = $_SESSION['cart'][$index];
        $_SESSION['cart'][$index] = $tmp;
    } elseif ($direction === 'down' && $index < $n-1) {
        $tmp = $_SESSION['cart'][$index+1];
        $_SESSION['cart'][$index+1] = $_SESSION['cart'][$index];
        $_SESSION['cart'][$index] = $tmp;
    }
}

function cart_clear(): void { $_SESSION['cart'] = []; }

function cart_remove_indices(array $indices): void {
    cart_init();
    // Remove by descending order to keep indices valid
    rsort($indices);
    foreach ($indices as $i) {
        $i = (int)$i;
        if (isset($_SESSION['cart'][$i])) {
            array_splice($_SESSION['cart'], $i, 1);
        }
    }
}

function cart_detailed(): array {
    $items = cart_get();
    $out = [];
    foreach ($items as $i => $it) {
        $show = get_show((int)$it['show_id']);
        if ($show) {
            $unit = isset($it['unit_price']) ? (float)$it['unit_price'] : (float)$show['base_price'];
            $tickets = (int)$it['tickets'];
            $seats = $it['seats'] ?? [];
            $out[] = [
                'index' => $i,
                'show_id' => (int)$it['show_id'],
                'tickets' => $tickets,
                'show' => $show,
                'price_each' => $unit,
                'seats' => $seats,
                'subtotal' => $unit * $tickets,
            ];
        }
    }
    return $out;
}

function cart_totals(): array {
    $items = cart_detailed();
    $total_tix = 0; $total_amt = 0.0;
    foreach ($items as $it) { $total_tix += $it['tickets']; $total_amt += $it['subtotal']; }
    return ['tickets' => $total_tix, 'amount' => $total_amt];
}

function valid_email(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function valid_phone(string $phone): bool { return (bool)preg_match('/^[0-9+\-()\s]{7,20}$/', $phone); }
function valid_phone_local(string $phone): bool { return (bool)preg_match('/^0[0-9]{8,9}$/', $phone); }
function valid_phone_international(string $ccode, string $number): bool {
    if (!preg_match('/^\+[0-9]{1,4}$/', $ccode)) return false;
    if (!preg_match('/^[0-9]{7,12}$/', $number)) return false;
    return true;
}

function ensure_post(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); } }

// Booking transaction: creates customer, booking, items and updates seats with atomic checks
function create_booking(string $name, string $email, string $phone, string $notes, array $cart_items, ?int $coupon_id = null, float $discount_amount = 0.0): array {
    $conn = db();
    $conn->begin_transaction();
    try {
        // Insert customer
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name, $email, $phone);
        $stmt->execute();
        $customer_id = $conn->insert_id;
        $stmt->close();

        // Insert booking
        $totals = ['tickets' => 0, 'amount' => 0.0];
        foreach ($cart_items as $it) { $totals['tickets'] += $it['tickets']; $totals['amount'] += ($it['price_each'] * $it['tickets']); }
        $stmt = $conn->prepare("INSERT INTO bookings (customer_id, coupon_id, discount_amount, total_tickets, total_amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iidid', $customer_id, $coupon_id, $discount_amount, $totals['tickets'], $totals['amount']);
        $stmt->execute();
        $booking_id = $conn->insert_id;
        $stmt->close();

        // For each item: enforce per-user 2-seat cap per show, check capacity and update seats atomically, then insert booking_items
        foreach ($cart_items as $it) {
            $tickets = (int)$it['tickets'];
            $show_id = (int)$it['show_id'];
            $price_each = (float)$it['price_each'];
            $seat_labels = '';
            if (!empty($it['seats']) && is_array($it['seats'])) {
                $seat_labels = implode(',', $it['seats']);
            }

            // If logged in, enforce max 2 seats per user per session
            $u = $_SESSION['user'] ?? null;
            if ($u && !empty($u['email'])) {
                $email_user = $u['email'];
                $stmt = $conn->prepare("SELECT COALESCE(SUM(bi.tickets),0) AS taken
                                         FROM booking_items bi
                                         JOIN bookings b ON b.id = bi.booking_id
                                         JOIN customers c ON c.id = b.customer_id
                                         WHERE bi.show_id = ? AND c.email = ?");
                $stmt->bind_param('is', $show_id, $email_user);
                $row = fetch_one($stmt);
                $already = (int)($row['taken'] ?? 0);
                if ($already + $tickets > 2) {
                    throw new Exception('Limit reached: You can only purchase up to 2 seats for this session with your account.');
                }
            }
            $stmt = $conn->prepare(
                "UPDATE shows s JOIN screens sc ON sc.id = s.screen_id
                 SET s.seats_sold = s.seats_sold + ?
                 WHERE s.id = ? AND s.seats_sold + ? <= sc.capacity"
            );
            $stmt->bind_param('iii', $tickets, $show_id, $tickets);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                $stmt->close();
                throw new Exception('Not enough seats available');
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO booking_items (booking_id, show_id, tickets, price_each, seat_labels) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('iiids', $booking_id, $show_id, $tickets, $price_each, $seat_labels);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        return ['ok' => true, 'booking_id' => $booking_id, 'customer_id' => $customer_id, 'totals' => $totals];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function log_email(string $to, string $subject, string $body, string $status): void {
    $stmt = db()->prepare("INSERT INTO emails (to_email, subject, body, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $to, $subject, $body, $status);
    $stmt->execute();
    $stmt->close();
}

// Auth helpers
function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}
function auth_is_admin(): bool { $u = auth_user(); return $u && $u['role'] === 'admin'; }
function auth_login(string $email, string $password): bool {
    $stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE email = ? AND password_hash = SHA2(?, 256)");
    $stmt->bind_param('ss', $email, $password);
    $user = fetch_one($stmt);
    if ($user) { $_SESSION['user'] = $user; return true; }
    return false;
}
function auth_logout(): void { unset($_SESSION['user']); }

// Coupons
function find_coupon(string $code): ?array {
    $stmt = db()->prepare("SELECT * FROM coupons WHERE code = ? AND active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())");
    $stmt->bind_param('s', $code);
    return fetch_one($stmt);
}
function apply_coupon_row(array $coupon, float $total): float {
    if ($total < (float)$coupon['min_total']) return 0.0;
    if ($coupon['discount_type'] === 'percent') return round($total * ((float)$coupon['value'] / 100.0), 2);
    return min($total, (float)$coupon['value']);
}

?>
