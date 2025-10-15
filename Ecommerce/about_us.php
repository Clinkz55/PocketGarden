<?php
session_start();
include('db.php');

// ✅ Check login
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// ✅ Initialize guest cart
if (!$isLoggedIn && !isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

// ✅ Cart count (unique items only)
$cart_count = 0;
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) AS count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $cart_count = $res['count'] ?? 0;
} else {
    $cart_count = isset($_SESSION['guest_cart']) ? count($_SESSION['guest_cart']) : 0;
}
$_SESSION['cart_count_display'] = $cart_count;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>About Us | Pocket Garden</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {font-family:'Poppins',sans-serif;margin:0;padding:0;color:#333;background:#fff;}
a {text-decoration:none;color:inherit;}
.about-section {max-width:1000px;margin:60px auto;padding:80px 20px;}
.about-section h2 {font-size:2rem;color:#3b7a57;margin-bottom:20px;text-align:center;}
.about-section p {font-size:16px;line-height:1.8;color:#555;margin-bottom:20px;text-align:justify;}
.about-section img {width:100%;border-radius:10px;margin-top:30px;}
@media(max-width:768px){
  .header-container {flex-direction:column;align-items:flex-start;gap:10px;}
  .main-nav ul {flex-direction:column;gap:10px;}
}
</style>
</head>
<body>

<!-- ✅ Include Header with Cart -->
<?php include('header_cart.php'); ?>


<!-- About Section -->
<section class="about-section">
    <h2>Our Story</h2>
    <p>Welcome to <strong>Pocket Garden</strong>, your go-to destination for premium indoor and outdoor plants. 
    Founded with a passion for greenery and sustainable living, Pocket Garden aims to bring the beauty of nature 
    right into your home, office, or garden space. Our carefully curated selection of plants caters to both beginners 
    and plant enthusiasts, offering everything from low-maintenance succulents to lush foliage plants.</p>

    <p>At Pocket Garden, we believe that plants are more than just décor—they’re living companions that improve your 
    environment, purify the air, and elevate your mood. Every plant we sell is hand-picked and potted with love, 
    ensuring it thrives in your care. Whether you’re looking to brighten up your workspace, start a home garden, 
    or find the perfect gift for a loved one, we’ve got you covered.</p>

    <p>Our mission is simple: to make nature accessible, enjoyable, and sustainable. Join our community of plant lovers 
    and bring a touch of green into your everyday life.</p>
</section>

<!-- ✅ Include Footer -->
<?php include('footer.php'); ?>

<!-- ✅ JS for Live Cart Update (same as in shop.php) -->
<script>
document.addEventListener('cartUpdated', e => {
    const data = e.detail;
    const uniqueCount = data.items ? data.items.length : data.count;
    data.count = uniqueCount;

    // Update cart badge if it exists
    const badge = document.querySelector('.cart-count');
    if (badge) badge.textContent = uniqueCount;
});
</script>

</body>
</html>
