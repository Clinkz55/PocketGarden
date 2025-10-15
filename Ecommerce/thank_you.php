<?php
session_start();
include('db.php');

// ✅ Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Redirect if no order_id provided
if (!isset($_GET['order_id'])) {
    header("Location: home.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// ✅ Fetch order info
$order_sql = "SELECT * FROM orders WHERE id=? AND user_id=?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    die("Order not found.");
}
$order = $order_result->fetch_assoc();

// ✅ Fetch order items
$items_sql = "SELECT oi.*, p.name, p.image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id=?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);

// ✅ Include your dynamic header
include('header_cart.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Thank You | Pocket Garden</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<!-- ✅ Auto redirect to home after 5 seconds -->
<meta http-equiv="refresh" content="5;url=home.php">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#fff;color:#333;}
.thank-you-container{
    max-width:800px;
    margin:120px auto 80px auto;
    padding:20px;
    background:#fff;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
    text-align:center;
}
.thank-you-container h1{
    font-size:28px;
    color:#3b7a57;
    margin-bottom:15px;
}
.thank-you-container p{
    font-size:16px;
    margin-bottom:30px;
}
.order-summary{
    border:1px solid #eee;
    padding:20px;
    border-radius:8px;
    text-align:left;
}
.order-summary h2{
    text-align:center;
    color:#444;
    margin-bottom:15px;
}
.order-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-bottom:1px solid #eee;
    padding:10px 0;
    gap:10px;
}
.order-item img{
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:6px;
}
.order-item-details{
    flex:1;
    display:flex;
    flex-direction:column;
}
.order-item-details h4{
    font-size:15px;
    margin-bottom:5px;
}
.order-item-details p{
    color:#3b7a57;
    font-weight:600;
}
.summary-row{
    display:flex;
    justify-content:space-between;
    margin:8px 0;
    font-weight:500;
}
.grand-total{
    font-size:18px;
    font-weight:700;
    margin-top:10px;
    text-align:right;
}
.continue-btn{
    display:inline-block;
    margin:25px auto 0 auto;
    padding:12px 20px;
    background:#3b7a57;
    color:#fff;
    border:none;
    border-radius:6px;
    font-weight:600;
    text-decoration:none;
    width:fit-content;
    transition:all .3s ease;
}
.continue-btn:hover{
    background:#2e5d42;
}
.redirect-note{
    margin-top:10px;
    color:#777;
    font-size:14px;
}
</style>
</head>
<body>

<div class="thank-you-container">
    <h1>Thank You for Your Order!</h1>
    <p>Your order has been successfully placed.<br>Order ID: <strong>#<?= $order_id ?></strong></p>

    <div class="order-summary">
        <h2>Order Summary</h2>
        <?php foreach($order_items as $item): ?>
        <div class="order-item">
            <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
            <div class="order-item-details">
                <h4><?= htmlspecialchars($item['name']) ?></h4>
                <p>₱<?= number_format($item['price'],2) ?> × <?= $item['quantity'] ?></p>
            </div>
            <div>₱<?= number_format($item['subtotal'],2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="summary-row">
            <span>Total Amount:</span>
            <span>₱<?= number_format($order['total_amount'],2) ?></span>
        </div>
    </div>

    <a href="home.php" class="continue-btn">Continue Shopping</a>
    <p class="redirect-note">Redirecting to Home in 5 seconds...</p>
</div>

<?php include('footer.php'); ?>
</body>
</html>
