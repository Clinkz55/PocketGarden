<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $payment = trim($_POST['payment']);
    $total_amount = floatval($_POST['total_amount']);

    // Start MySQL transaction for safety
    $conn->begin_transaction();

    try {
        // 1️⃣ Insert order into `orders` table
        $order_sql = "INSERT INTO orders (user_id, fullname, mobile, address, payment_method, total_amount, status)
                      VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("issssd", $user_id, $fullname, $mobile, $address, $payment, $total_amount);
        $stmt->execute();
        $order_id = $stmt->insert_id;

        // 2️⃣ Fetch user's cart items
        $cart_sql = "SELECT c.product_id, c.quantity, p.price 
                     FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                     WHERE c.user_id = ?";
        $stmt = $conn->prepare($cart_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();

        // 3️⃣ Insert cart items into `order_items`
        $insert_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                            VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($insert_item_sql);

        while ($item = $cart_result->fetch_assoc()) {
            $stmt2->bind_param(
                "iiid",
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            );
            $stmt2->execute();
        }

        // 4️⃣ Clear user's cart
        $clear_sql = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($clear_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // 5️⃣ Commit all changes
        $conn->commit();

        // ✅ Redirect to thank you page
        header("Location: thank_you.php?order_id=$order_id");
        exit();
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        die("Error processing order: " . $e->getMessage());
    }
} else {
    // Direct access not allowed
    header("Location: checkout.php");
    exit();
}
?>
