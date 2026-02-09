<?php
// api.php - Simple JSON API for Rainbow Hotel (secured with API key)
require_once 'config.php';

// Rate limit: 120 requests per 5 minutes per IP
if (!rate_limit('api', 120, 300)) {
    json_response(['ok' => false, 'error' => 'Too many requests'], 429);
}

api_require_key();

// /api.php?route=...
$route = $_GET['route'] ?? '';
$route = preg_replace('/[^a-zA-Z0-9_\-]/', '', $route);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// JSON body
$raw = file_get_contents('php://input');
$body = [];
if ($raw) {
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) $body = $parsed;
}

switch ($route) {
    case 'health':
        json_response(['ok' => true, 'service' => 'rainbow-hotel-api', 'time' => gmdate('c')]);
        break;

    case 'rooms':
        if ($method !== 'GET') json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
        $res = $conn->query("SELECT id, room_number, room_type, price_per_night, status FROM rooms WHERE status='available' ORDER BY price_per_night");
        $rooms = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $rooms[] = $row;
        }
        json_response(['ok' => true, 'rooms' => $rooms]);
        break;

    case 'create_booking':
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'Method not allowed'], 405);

        $name = clean_input($body['name'] ?? '');
        $email = clean_input($body['email'] ?? '');
        $phone = clean_input($body['phone'] ?? '');
        $guests = (int)($body['guests'] ?? 1);

        if ($name === '' || $email === '' || $phone === '' || $guests < 1 || $guests > 4) {
            json_response(['ok' => false, 'error' => 'Invalid input'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['ok' => false, 'error' => 'Invalid email'], 400);
        }

        // Generate reference (same logic as book.php)
        $result = $conn->query("SELECT MAX(id) as max_id FROM bookings");
        $row = $result ? $result->fetch_assoc() : null;
        $nextId = ((int)($row['max_id'] ?? 0)) + 1;
        $reference = 'RNBW' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);

        $checkin = date('Y-m-d', strtotime('+1 day'));
        $checkout = date('Y-m-d', strtotime('+4 days'));

        $stmt = $conn->prepare("INSERT INTO bookings (reference, guest_name, guest_email, guest_phone, checkin_date, checkout_date, guests, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssssi", $reference, $name, $email, $phone, $checkin, $checkout, $guests);

        if ($stmt->execute()) {
            $stmt->close();
            json_response(['ok' => true, 'reference' => $reference, 'status' => 'pending']);
        } else {
            $stmt->close();
            json_response(['ok' => false, 'error' => 'Database error'], 500);
        }
        break;

    case 'verify_booking':
        if ($method !== 'GET') json_response(['ok' => false, 'error' => 'Method not allowed'], 405);

        $reference = strtoupper(clean_input($_GET['reference'] ?? ''));
        if (!preg_match('/^RNBW\d{5}$/', $reference)) {
            json_response(['ok' => false, 'error' => 'Invalid reference'], 400);
        }

        $stmt = $conn->prepare("SELECT reference, guest_name, guest_email, guest_phone, checkin_date, checkout_date, guests, booking_status, created_at FROM bookings WHERE reference = ? LIMIT 1");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $stmt->close();
            json_response(['ok' => true, 'booking' => $row]);
        }
        $stmt->close();
        json_response(['ok' => false, 'error' => 'Not found'], 404);
        break;

    default:
        json_response(['ok' => false, 'error' => 'Unknown route'], 404);
}
?>