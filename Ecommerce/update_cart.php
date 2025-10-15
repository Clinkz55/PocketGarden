<?php
session_start();
include('db.php');

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

$product_id = intval($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';
$quantity = intval($_POST['quantity'] ?? 1);

// Validate product ID
if ($product_id <= 0 && $action !== '') {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $items = [];

    // =============== LOGGED-IN USER CART ===============
    if ($isLoggedIn) {
        if ($action === 'remove') {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        }

        if ($action === 'update') {
            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?");
                $stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?");
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
            }
        }

        // Fetch updated cart
        $stmt = $conn->prepare("
            SELECT c.product_id, c.quantity, p.name, p.image, p.price 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $items[] = $row;

    // =============== GUEST CART ===============
    } else {
        if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

        // Remove item
        if ($action === 'remove') {
            unset($_SESSION['guest_cart'][$product_id]);
        }

        // Update quantity
        if ($action === 'update') {
            if ($quantity > 0) {
                if (isset($_SESSION['guest_cart'][$product_id])) {
                    $_SESSION['guest_cart'][$product_id]['quantity'] = $quantity;
                }
            } else {
                unset($_SESSION['guest_cart'][$product_id]);
            }
        }

        // 🔁 Refresh guest cart with accurate product data from DB
        foreach ($_SESSION['guest_cart'] as $pid => $item) {
            $stmt = $conn->prepare("SELECT id, name, image, price FROM products WHERE id=? LIMIT 1");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) {
                $items[] = [
                    'product_id' => $res['id'],
                    'name' => $res['name'],
                    'image' => $res['image'],
                    'price' => floatval($res['price']),
                    'quantity' => intval($item['quantity'] ?? 1)
                ];
            }
        }
    }

    // Compute totals
    $count = count($items);
    $total_price = 0;
    foreach ($items as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    // Save cart count in session for consistency across pages
    $_SESSION['cart_count_display'] = $count;

    // Send JSON response
    echo json_encode([
        'success' => true,
        'count' => $count,
        'items' => $items,
        'total_price' => number_format($total_price, 2)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>


