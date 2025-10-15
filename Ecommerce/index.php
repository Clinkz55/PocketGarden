<?php 
session_start();
include('db.php');

// ✅ Check login
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// ✅ Initialize guest cart
if (!$isLoggedIn && !isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

// ✅ Fetch 3 products
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
<title>Pocket Garden</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#fff;color:#333;}
a{text-decoration:none;color:inherit;}

/* Hero Section */
.hero{background:center/cover no-repeat;height:500px;display:flex;align-items:center;justify-content:center;margin-top:100px;margin-bottom:15px;}
.hero-content{padding:40px;border-radius:12px;text-align:center;}
.hero h1{font-size:2.5rem;margin-bottom:15px;}
.hero p{font-size:1.1rem;margin-bottom:25px;color:#555;}
.btn-primary{background:#e68ca7;color:#fff;padding:12px 30px;border-radius:30px;text-decoration:none;font-weight:600;}
.btn-primary:hover{background:#d47190;}

/* Product Section */
.product-section{padding:80px 10%;text-align:center;}
.product-section h2{text-transform:uppercase;font-size:1rem;letter-spacing:1px;color:#333;margin-bottom:40px;}
.product-grid{width:100%;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:40px;justify-items:center;}
.product-card{border:1px solid #eee;border-radius:12px;padding:15px;text-align:center;transition:transform .3s ease,box-shadow .3s ease;}
.product-card:hover{transform:translateY(-5px);box-shadow:0 6px 16px rgba(0,0,0,.1);}
.product-card img{width:100%;height:230px;object-fit:contain;border-radius:8px;margin-bottom:10px;}
.product-card h3{font-size:15px;font-weight:500;margin:10px 0 5px;}
.price{font-weight:600;font-size:15px;margin-bottom:8px;color:#2d634c;}

/* Quantity & Buttons */
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

/* Testimonials */
.testimonial-section{background:#fff;padding:100px 10%;text-align:center;overflow:hidden;}
.testimonial-section h2{text-transform:uppercase;letter-spacing:2px;font-size:16px;color:#222;margin-bottom:60px;}
.testimonial-wrapper{position:relative;width:100%;max-width:900px;margin:0 auto;overflow:hidden;}
.testimonials{display:flex;transition:transform 0.6s ease;}
.testimonial{min-width:100%;color:#444;line-height:1.7;font-size:15px;padding:0 30px;box-sizing:border-box;}
.testimonial i{font-size:24px;color:#111;margin-bottom:15px;}
.testimonial p{margin-bottom:20px;}
.testimonial span{display:block;font-size:13px;color:#555;}
.dots{margin-top:25px;}
.dot{height:10px;width:10px;margin:0 5px;background:#ccc;border-radius:50%;display:inline-block;cursor:pointer;transition:0.3s;}
.dot.active{background:#000;transform:scale(1.2);}
.testimonial-img {width: 250px; height: 250px; object-fit: cover; border-radius: 50%; margin: 0 auto 15px; display: block; border: 2px solid #a56b7a;}

@media(max-width:768px){
  .product-card img{height:210px;}
}

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
</style>
</head>

<body>

<!-- Header -->
<?php include('header_cart.php'); ?>

<!-- Hero -->
<section class="hero" style="background-image: url('<?= $active_banner ? 'images/'.htmlspecialchars($active_banner['image']) : 'images/banner.jpg' ?>');">
</section>

<!-- Products -->
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

<!-- Testimonials -->
<section class="testimonial-section">
  <h2>WHAT OUR CUSTOMERS SAY ABOUT US</h2>
  <div class="testimonial-wrapper">
    <div class="testimonials">
      <div class="testimonial">
        <img src="images/jericho.jpg" alt="Paola R." class="testimonial-img">
        <i class="fa-solid fa-quote-left"></i>
        <p>The plant I received was even better than expected. Packaging and delivery were impressive!</p>
        <span>— Jericho P.</span>
      </div>
      <div class="testimonial">
        <img src="images/arvin.jpg" alt="Daniel C." class="testimonial-img">
        <i class="fa-solid fa-quote-left"></i>
        <p>My bonsai arrived healthy and thriving. Their care instructions were super helpful!</p>
        <span>— Arvin B.</span>
      </div>
      <div class="testimonial">
        <img src="images/pooh.jpg" alt="Mikaella G." class="testimonial-img">
        <i class="fa-solid fa-quote-left"></i>
        <p>Beautiful orchids! Fast delivery and the staff were very accommodating. Thank you!</p>
        <span>— Pooh G.</span>
      </div>
    </div>
    <div class="dots">
      <span class="dot active" data-index="0"></span>
      <span class="dot" data-index="1"></span>
      <span class="dot" data-index="2"></span>
    </div>
  </div>
</section>

<!-- Footer -->
<?php include('footer.php'); ?>

<!-- Center Pop-up -->
<div id="cart-popup">Added to cart!</div>

<script>
// Show popup
function showPopup(message){
    const popup = document.getElementById('cart-popup');
    popup.textContent = message;
    popup.style.display = 'block';
    popup.style.opacity = '1';
    setTimeout(()=>{
        popup.style.transition = 'opacity 0.5s';
        popup.style.opacity = '0';
        setTimeout(()=>{ popup.style.display = 'none'; popup.style.transition = ''; },500);
    },1200);
}

// Product Card Actions
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
                showPopup('🛒 Added to cart!');
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

// Testimonial Slider
const testimonials=document.querySelector('.testimonials');
const dots=document.querySelectorAll('.dot');
let currentIndex=0;
function updateSlider(i){
  testimonials.style.transform=`translateX(-${i*100}%)`;
  dots.forEach(d=>d.classList.remove('active'));
  dots[i].classList.add('active');
}
dots.forEach(dot=>dot.addEventListener('click',()=>{
  currentIndex=parseInt(dot.dataset.index);
  updateSlider(currentIndex);
}));
setInterval(()=>{ currentIndex=(currentIndex+1)%dots.length; updateSlider(currentIndex); },5000);
</script>

</body>
</html>
