<?php
session_start();
header('Content-Type: application/json');
include('db.php');

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$count = 0;

if ($isLoggedIn) {
    // Logged-in user: sum quantities from database
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $count = intval($data['total'] ?? 0);
    $stmt->close();
} else {
    // Guest user: sum quantities from session
    if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
    $count = array_reduce($_SESSION['guest_cart'], function ($carry, $item) {
        return $carry + (isset($item['quantity']) ? intval($item['quantity']) : 0);
    }, 0);
}

// If empty, return null so badge can hide
echo json_encode([
    'success' => true,
    'count' => $count > 0 ? $count : null
]);
?>
