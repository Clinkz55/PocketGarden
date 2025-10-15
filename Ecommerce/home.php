<?php 
session_start();
include('db.php');

// ✅ Check login
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// ✅ Initialize guest cart
if (!$isLoggedIn && !isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

// ✅ Fetch 3 products for home display
$sql = "SELECT * FROM products LIMIT 3";
$result = $conn->query($sql);
$products = $result && $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ✅ Fetch active banner
$active_banner = $conn->query("SELECT * FROM banners WHERE is_active=1 LIMIT 1")->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pocket Garden - Home</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#fff;color:#333;}
a{text-decoration:none;color:inherit;}

/* Hero Section */
.hero{background:center/cover no-repeat;height:500px;display:flex;align-items:center;justify-content:center;margin-top:100px;}

/* Product Section */
.product-section{padding:80px 10%;text-align:center;}
.product-section h2{text-transform:uppercase;font-size:1rem;letter-spacing:1px;color:#333;margin-bottom:40px;}
.product-grid{width:100%;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:40px;justify-items:center;}
.product-card{border:1px solid #eee;border-radius:12px;padding:15px;text-align:center;transition:transform .3s ease,box-shadow .3s ease;}
.product-card:hover{transform:translateY(-5px);box-shadow:0 6px 16px rgba(0,0,0,.1);}
.product-card img{width:100%;height:230px;object-fit:contain;border-radius:8px;margin-bottom:10px;}
.product-card h3{font-size:15px;font-weight:500;margin:10px 0 5px;}
.price{font-weight:600;font-size:15px;margin-bottom:8px;color:#2d634c;}

/* Quantity + Buttons */
.qty-box{display:flex;justify-content:center;align-items:center;gap:8px;margin:8px 0;}
.qty-btn{background:#a56b7a;color:#fff;border:none;padding:3px 9px;border-radius:6px;cursor:pointer;font-size:14px;}
.qty-input{width:45px;text-align:center;border:1px solid #ccc;border-radius:6px;padding:4px;font-size:14px;}
.action-btns{display:flex;justify-content:center;gap:10px;margin-top:10px;}
.action-btns button{
    flex:1;padding:8px 10px;border:none;border-radius:8px;font-size:13px;cursor:pointer;transition:.3s;font-weight:500;
}
.add-btn{background:#2d634c;color:#fff;}
.add-btn:hover{background:#3b7a57;}
.buy-btn{background:#a56b7a;color:#fff;}
.buy-btn:hover{background:#8a5566;}

.view-all-btn{background:#b26f7a;color:#fff;padding:10px 40px;border-radius:4px;display:inline-block;margin-top:60px;text-decoration:none;letter-spacing:1px;text-transform:uppercase;font-size:14px;}
.view-all-btn:hover{background:#9d5963;}

/* Center Pop-up Message */
#cart-popup{
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    background:#2d634c;
    color:#fff;
    padding:20px 30px;
    border-radius:10px;
    font-weight:600;
    font-size:16px;
    text-align:center;
    display:none;
    z-index:9999;
    box-shadow:0 4px 15px rgba(0,0,0,0.2);
    animation:fadein 0.3s;
}
@keyframes fadein{from{opacity:0;}to{opacity:1;}}

@media(max-width:768px){
  .product-card img{height:210px;}
}
</style>
</head>

<body>

<!-- Header -->
<?php include('header_cart.php'); ?>

<!-- Hero Section -->
<section class="hero" style="background-image: url('<?= $active_banner ? 'images/'.htmlspecialchars($active_banner['image']) : 'images/banner.jpg' ?>');">

</section>

<!-- Products Section -->
<section class="product-section">
  <h2>NEW! PLANTS IN POCKET GARDEN</h2>
  <div class="product-grid">
    <?php foreach($products as $p): ?>
    <div class="product-card" data-id="<?= $p['id'] ?>">
      <img src="images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
      <h3><?= htmlspecialchars($p['name']) ?></h3>
      <div class="price">₱<?= number_format($p['price'],2) ?></div>

      <div class="qty-box">
        <button class="qty-btn minus">−</button>
        <input type="number" class="qty-input" value="1" min="1">
        <button class="qty-btn plus">+</button>
      </div>

      <div class="action-btns">
        <button class="add-btn">Add to Cart</button>
        <button class="buy-btn">Buy Now</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <a href="shop.php" class="view-all-btn">View All</a>
</section>

<!-- Center Pop-up -->
<div id="cart-popup">Added to cart!</div>

<!-- Footer -->
<?php include('footer.php'); ?>

<script>
const cartPopup = document.getElementById('cart-popup');

function showPopup(message){
    cartPopup.textContent = message;
    cartPopup.style.display = 'block';
    cartPopup.style.opacity = '1';
    setTimeout(() => {
        cartPopup.style.transition = 'opacity 0.5s';
        cartPopup.style.opacity = '0';
    }, 1500);
    setTimeout(() => {
        cartPopup.style.display = 'none';
        cartPopup.style.transition = '';
    }, 2000);
}

document.querySelectorAll('.product-card').forEach(card=>{
  const input = card.querySelector('.qty-input');
  card.querySelector('.minus').onclick = ()=>{ if(input.value>1) input.value--; };
  card.querySelector('.plus').onclick = ()=> input.value++;

  const id = card.dataset.id;

  card.querySelector('.add-btn').onclick = ()=>{
    fetch('add_to_cart.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`product_id=${id}&quantity=${input.value}`
    })
    .then(res=>res.json())
    .then(data=>{
      if(data.success){
        document.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
        showPopup('Added to cart!');
      } else showPopup(data.message || 'Error adding to cart.');
    });
  };

  card.querySelector('.buy-btn').onclick = ()=>{
    fetch('add_to_cart.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`product_id=${id}&quantity=${input.value}`
    })
    .then(res=>res.json())
    .then(data=>{
      if(data.success){
        document.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
        window.location.href='checkout.php';
      } else showPopup(data.message || 'Failed to proceed.');
    });
  };
});
</script>

</body>
</html>
