<?php
session_start();
include('db.php');
include('header_cart.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all orders + their items
$sql = "
SELECT 
    o.id AS order_id, 
    o.order_date, 
    o.total_amount, 
    o.payment_method, 
    o.status,
    oi.quantity, 
    oi.price, 
    p.name AS product_name, 
    p.image
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
WHERE o.user_id = ?
ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[$row['order_id']]['details'][] = $row;
    $orders[$row['order_id']]['info'] = [
        'date' => $row['order_date'],
        'payment' => $row['payment_method'],
        'total' => $row['total_amount'],
        'status' => $row['status']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Order History | Pocket Garden</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: #fafafa;
}
.container {
    max-width: 1100px;
    margin: 50px auto;
    padding: 20px;
}
h2 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
}
.order-card {
    background: #fff;
    margin-bottom: 25px;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
.order-header h3 {
    margin: 0;
    color: #333;
}
.order-header span {
    font-size: 14px;
    color: #666;
}
.order-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 8px;
}
.item img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 10px;
}
.item-info {
    flex: 1;
}
.item-info h4 {
    margin: 0;
    font-size: 15px;
    color: #333;
}
.item-info p {
    margin: 3px 0;
    color: #777;
    font-size: 14px;
}
.total {
    text-align: right;
    font-weight: 600;
    color: #3b7a57;
    font-size: 16px;
    margin-top: 10px;
}
.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    color: #fff;
}
.status.Pending { background: #ffb347; }
.status.Completed { background: #4CAF50; }
.status.Cancelled { background: #e74c3c; }
.empty {
    text-align: center;
    color: #888;
    font-size: 16px;
    padding: 60px;
}
@media(max-width:768px){
    .item img{width:60px;height:60px;}
}
</style>
</head>
<body>

<div class="container">
    <h2>My Order History</h2>

    <?php if(empty($orders)): ?>
        <div class="empty">You have no orders yet.</div>
    <?php else: ?>
        <?php foreach($orders as $order_id => $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <h3>Order #<?= $order_id ?></h3>
                    <span><?= date("F j, Y, g:i a", strtotime($order['info']['date'])) ?></span>
                </div>

                <div class="order-items">
                    <?php foreach($order['details'] as $item): ?>
                        <div class="item">
                            <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                                <p>₱<?= number_format($item['price'],2) ?> × <?= $item['quantity'] ?></p>
                            </div>
                            <div>₱<?= number_format($item['price'] * $item['quantity'],2) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total">
                    <strong>Total:</strong> ₱<?= number_format($order['info']['total'],2) ?> 
                    <br>
                    <span>Payment: <?= ucfirst($order['info']['payment']) ?></span>
                    <br>
                    <span class="status <?= ucfirst($order['info']['status']) ?>">
                        <?= htmlspecialchars($order['info']['status']) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
</body>
</html>
