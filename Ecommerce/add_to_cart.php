<?php
include('db.php');
session_start();

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

// ✅ Ensure minimum quantity of 1
if ($quantity < 1) $quantity = 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

// ✅ Fetch product details
$stmt = $conn->prepare("SELECT id, name, image, price FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$cart_items = [];
$total_price = 0;

if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];

    // ✅ Check if product already exists in user's cart
    $res = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $res->bind_param("ii", $user_id, $product_id);
    $res->execute();
    $existing = $res->get_result()->fetch_assoc();

    if ($existing) {
        // Increase quantity and make sure it’s at least 1
        $new_qty = max(1, $existing['quantity'] + $quantity);
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $existing['id']);
        $stmt->execute();
    } else {
        // Insert as new product with quantity >= 1
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $product['price']);
        $stmt->execute();
    }

    // ✅ Fetch updated cart (skip any invalid data)
    $cart_res = $conn->prepare("
        SELECT c.product_id, c.quantity, p.name, p.image, c.price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.quantity > 0
    ");
    $cart_res->bind_param("i", $user_id);
    $cart_res->execute();
    $result = $cart_res->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }

    // ✅ Count unique products
    $count = count($cart_items);

} else {
    // ✅ Guest cart
    if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

    if (isset($_SESSION['guest_cart'][$product_id])) {
        $_SESSION['guest_cart'][$product_id]['quantity'] = max(
            1,
            $_SESSION['guest_cart'][$product_id]['quantity'] + $quantity
        );
    } else {
        $_SESSION['guest_cart'][$product_id] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'image' => $product['image'],
            'price' => $product['price'],
            'quantity' => max(1, $quantity)
        ];
    }

    foreach ($_SESSION['guest_cart'] as $pid => $item) {
        if ($item['quantity'] > 0) {
            $cart_items[] = $item;
            $total_price += $item['price'] * $item['quantity'];
        }
    }

    // ✅ Count unique guest items
    $count = count($_SESSION['guest_cart']);
}

// ✅ Return JSON response
echo json_encode([
    'success' => true,
    'count' => $count,
    'items' => $cart_items,
    'total_price' => $total_price
]);

$conn->close();
?>
