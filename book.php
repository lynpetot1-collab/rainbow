<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

if (!csrf_validate($_POST['token'] ?? '')) {
    die("<script>alert('Invalid request. Please try again.'); window.location.href='index.php';</script>");
}

$name   = clean_input($_POST['name'] ?? '');
$email  = clean_input($_POST['email'] ?? '');
$phone  = clean_input($_POST['phone'] ?? '');
$guests = (int)($_POST['guests'] ?? 1);

// (OPTIONAL) if you have these fields in your form:
$checkin  = clean_input($_POST['checkin_date'] ?? '');
$checkout = clean_input($_POST['checkout_date'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $guests < 1 || $guests > 10) {
    die("<script>alert('Please fill all fields correctly.'); window.location.href='index.php';</script>");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("<script>alert('Invalid email.'); window.location.href='index.php';</script>");
}

// If your form doesn't have dates, you can auto-generate:
if ($checkin === '' || $checkout === '') {
    $checkin  = date('Y-m-d', strtotime('+1 day'));
    $checkout = date('Y-m-d', strtotime('+4 days'));
}

$conn->begin_transaction();

try {
    // Insert booking with temporary reference
    $tempRef = 'TEMP';

    $stmt = $conn->prepare("INSERT INTO bookings 
        (reference, guest_name, guest_email, guest_phone, checkin_date, checkout_date, guests, booking_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");

    $stmt->bind_param("ssssssi", $tempRef, $name, $email, $phone, $checkin, $checkout, $guests);

    if (!$stmt->execute()) {
        throw new Exception("Insert failed");
    }

    $bookingId = $conn->insert_id;

    // Create safer reference from ID (unique + predictable format)
    $reference = 'RNBW' . str_pad((string)$bookingId, 5, '0', STR_PAD_LEFT);

    $stmt2 = $conn->prepare("UPDATE bookings SET reference=? WHERE id=?");
    $stmt2->bind_param("si", $reference, $bookingId);

    if (!$stmt2->execute()) {
        throw new Exception("Update reference failed");
    }

    $conn->commit();

    header("Location: booking_success.php?ref=" . urlencode($reference));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Error processing booking. Please try again.'); window.location.href='index.php';</script>";
}
