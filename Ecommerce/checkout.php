<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Fetch cart items
$cart_sql = "SELECT c.*, p.name, p.image, p.price 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_items = $cart_result && $cart_result->num_rows > 0 ? $cart_result->fetch_all(MYSQLI_ASSOC) : [];

$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// Fixed shipping fee
$shipping_fee = 50; 
$grand_total = $total_price + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | Pocket Garden</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#fff;color:#333;}
.checkout-container{max-width:1100px;margin:80px auto;padding:0 20px;display:flex;gap:40px;flex-wrap:wrap;}
.cart-summary, .shipping-form{flex:1;min-width:300px;}
.cart-summary h2, .shipping-form h2{margin-bottom:15px;border-left:4px solid #a56b7a;padding-left:10px;}
.cart-item{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding:10px 0;gap:10px;}
.cart-item img{width:80px;height:80px;object-fit:cover;border-radius:8px;}
.cart-item-details{flex:1;display:flex;flex-direction:column;}
.cart-item-details h4{font-size:15px;margin-bottom:5px;}
.cart-item-details p{color:#3b7a57;font-weight:600;}
.summary-row{display:flex;justify-content:space-between;margin:8px 0;font-weight:500;}
.grand-total{font-size:18px;font-weight:700;margin-top:10px;}
.shipping-form form{display:flex;flex-direction:column;gap:12px;}
.shipping-form input, .shipping-form select{padding:10px;border:1px solid #ccc;border-radius:5px;width:100%;}
.shipping-form label{font-weight:500;}
.place-order{margin-top:15px;padding:12px;background:#3b7a57;color:#fff;border:none;border-radius:5px;font-weight:600;cursor:pointer;}
.place-order:hover{background:#2e5d42;}
.empty-cart{text-align:center;color:#888;font-size:16px;padding:50px 0;}
@media(max-width:768px){.checkout-container{flex-direction:column}}
</style>
</head>
<body>

<?php include('header_cart.php'); ?>

<!-- Checkout Content -->
<div class="checkout-container">
    <?php if (count($cart_items) > 0): ?>
    
    <!-- Cart Summary -->
    <div class="cart-summary">
        <h2>Order Summary</h2>
        <?php foreach ($cart_items as $item): 
            $subtotal = $item['price'] * $item['quantity'];
        ?>
        <div class="cart-item">
            <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
            <div class="cart-item-details">
                <h4><?= htmlspecialchars($item['name']) ?></h4>
                <p>₱<?= number_format($item['price'],2) ?> × <?= $item['quantity'] ?></p>
            </div>
            <div>₱<?= number_format($subtotal,2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="summary-row"><span>Subtotal:</span><span>₱<?= number_format($total_price,2) ?></span></div>
        <div class="summary-row"><span>Shipping Fee:</span><span>₱<?= number_format($shipping_fee,2) ?></span></div>
        <div class="grand-total"><span>Grand Total:</span><span>₱<?= number_format($grand_total,2) ?></span></div>
    </div>

    <!-- Shipping & Payment -->
    <div class="shipping-form">
        <h2>Shipping & Payment</h2>
        <form method="POST" action="process_order.php">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" required>
            
            <label for="mobile">Mobile Number</label>
            <input type="text" id="mobile" name="mobile" required>
            
            <label for="address">Delivery Address</label>
            <input type="text" id="address" name="address" required>
            
            <label for="payment">Payment Method</label>
            <select id="payment" name="payment" required>
                <option value="cod">Cash on Delivery</option>
                <option value="gcash">GCash</option>
                <option value="bank">Bank Transfer</option>
            </select>

            <input type="hidden" name="total_amount" value="<?= $grand_total ?>">
            <button type="submit" class="place-order">Place Order</button>
        </form>
    </div>

    <?php else: ?>
        <div class="empty-cart">Your cart is empty.</div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>

</body>
</html>
