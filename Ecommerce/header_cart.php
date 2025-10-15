<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('db.php');

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// Initialize guest cart if needed
if (!$isLoggedIn && !isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

$cart_items = [];
$total_price = 0;

// 🛒 Fetch cart items
if ($isLoggedIn) {
    $stmt = $conn->prepare("
        SELECT c.product_id, c.quantity, p.name, p.image, p.price 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($item = $res->fetch_assoc()) {
        if ($item['quantity'] > 0) {
            $cart_items[] = $item;
            $total_price += $item['price'] * $item['quantity'];
        }
    }
} else {
    foreach ($_SESSION['guest_cart'] as $item) {
        $product_id_var = $item['product_id'] ?? $item['id'];
        $stmt = $conn->prepare("SELECT id, name, image, price FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id_var);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if ($product) {
            $quantity = $item['quantity'] ?? $item['qty'] ?? 1;
            if ($quantity > 0) {
                $product['quantity'] = $quantity;
                $cart_items[] = $product;
                $total_price += $product['price'] * $quantity;
            }
        }
    }
}

// ✅ Count unique product entries
$unique_products = [];
foreach ($cart_items as $item) {
    $pid = $item['product_id'] ?? $item['id'];
    $unique_products[$pid] = true;
}
$count = count($unique_products);

$_SESSION['cart_count_display'] = $count;

$home_link = $isLoggedIn ? 'home.php' : 'index.php';
$order_history_link = $isLoggedIn ? 'order_history.php' : 'login.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.main-header{
  background:#fff;
  border-bottom:1px solid #eee;
  width:100%;
  position:fixed;
  top:0;
  left:0;
  z-index:1000;
  transition:all .3s ease;
}
.header-container{
  display:flex;
  justify-content:center;
  align-items:center;
  padding:10px 6%;
  position:relative;
}
.header-logo img{
  height:80px;
  cursor:pointer;
}
.header-icons{
  position:absolute;
  right:5%;
  top:50%;
  transform:translateY(-50%);
  display:flex;
  align-items:center;
  gap:18px;
}
.header-icons a,.header-icons button{
  color:#000;
  font-size:21px;
  background:none;
  border:none;
  cursor:pointer;
  text-decoration:none;
  position:relative;
}
.cart-badge{
  position:absolute;
  top:-8px;
  right:-6px;
  background:#a56b7a;
  color:#fff;
  font-size:12px;
  padding:2px 6px;
  border-radius:50%;
}
.main-header.shrink{
  background:rgba(255,255,255,.97);
  box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.main-header.shrink .header-logo img{height:55px;}
.cart-dropdown{
  display:none;
  flex-direction:column;
  position:absolute;
  right:0;
  top:60px;
  width:320px;
  background:#fff;
  border:1px solid #ddd;
  border-radius:8px;
  padding:15px;
  box-shadow:0 6px 18px rgba(0,0,0,0.12);
  z-index:200;
}
.cart-item-card{
  display:flex;
  align-items:center;
  gap:12px;
  border-bottom:1px solid #eee;
  padding-bottom:12px;
  margin-bottom:12px;
  position:relative;
}
.cart-item-card img{
  width:60px;
  height:60px;
  object-fit:cover;
  border-radius:8px;
}
.cart-item-details h4{
  font-size:14px;
  margin:0 0 5px;
}
.cart-item-details p{
  font-size:13px;
  color:#666;
  margin:0;
}
.remove-item{
  position:absolute;
  right:0;
  top:0;
  background:none;
  border:none;
  font-size:16px;
  color:#a56b7a;
  cursor:pointer;
}
.quantity-controls{
  display:flex;
  align-items:center;
  gap:4px;
  margin-top:4px;
}
.quantity-controls button{
  width:22px;
  height:22px;
  background:#a56b7a;
  color:#fff;
  border:none;
  border-radius:4px;
  cursor:pointer;
  font-size:14px;
}
.quantity-controls input{
  width:30px;
  text-align:center;
  border:1px solid #ccc;
  border-radius:4px;
  font-size:13px;
  padding:2px;
}
.cart-total{
  font-weight:600;
  text-align:right;
  font-size:14px;
  margin-top:10px;
}
.cart-buttons{
  display:flex;
  justify-content:space-between;
  margin-top:15px;
}
.cart-buttons a{
  flex:1;
  text-align:center;
  background:#a56b7a;
  color:#fff;
  padding:10px 14px;
  border-radius:6px;
  font-size:13px;
  font-weight:500;
  text-decoration:none;
  transition:.3s;
}
.cart-buttons a:first-child{margin-right:8px;}
.cart-buttons a:hover{background:#8c5b68;}
.empty-cart{
  text-align:center;
  color:#555;
  font-size:14px;
  padding:25px 0;
}
.main-nav{
  background:#f9f9f9;
  border-top:1px solid #eee;
}
.main-nav ul{
  display:flex;
  justify-content:center;
  gap:30px;
  list-style:none;
  padding:10px 0;
  margin:0;
}
.main-nav ul li a{
  text-decoration:none;
  color:#333;
  font-size:14px;
  font-weight:500;
}
.main-nav ul li a:hover{color:#a56b7a;}
@media(max-width:768px){
  .header-container{
    flex-direction:column;
    align-items:flex-start;
    gap:10px;
  }
  .main-header.shrink .header-logo img{height:45px;}
  .cart-dropdown{
    width:90%;
    right:10px;
  }
}
</style>

<header class="main-header">
  <div class="header-container">
    <div class="header-logo">
      <a href="<?= $home_link ?>"><img src="images/Logo.jpg" alt="Pocket Garden"></a>
    </div>

    <div class="header-icons">
      <?php if($isLoggedIn): ?>
        <span style="font-size:14px;">Welcome, <strong><?= htmlspecialchars($_SESSION['name']); ?></strong></span>
      <?php endif; ?>

      <a href="#" id="cart-icon" title="Cart">
        <i class="fa-solid fa-bag-shopping"></i>
        <span class="cart-badge" id="cart-count" <?= $count === 0 ? 'style="display:none;"' : '' ?>><?= $count ?></span>
      </a>

      <?php if($isLoggedIn): ?>
        <form action="logout.php" method="POST" style="display:inline;">
          <button type="submit" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></button>
        </form>
      <?php else: ?>
        <a href="login.php" title="Login"><i class="fa-regular fa-user"></i></a>
      <?php endif; ?>

      <div class="cart-dropdown" id="cart-dropdown">
        <?php if($count > 0): ?>
          <?php foreach($cart_items as $item): ?>
            <div class="cart-item-card" data-id="<?= $item['product_id'] ?? $item['id'] ?>">
              <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <div class="cart-item-details">
                <h4><?= htmlspecialchars($item['name']) ?></h4>
                <p><?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></p>
                <div class="quantity-controls">
                  <button class="decrease">−</button>
                  <input type="text" class="quantity" value="<?= $item['quantity'] ?>" readonly>
                  <button class="increase">+</button>
                </div>
              </div>
              <button class="remove-item">&times;</button>
            </div>
          <?php endforeach; ?>
          <div class="cart-total">SUBTOTAL: ₱<span id="cart-total"><?= number_format($total_price, 2) ?></span></div>
          <div class="cart-buttons">
            <a href="cart.php">View Cart</a>
            <a href="<?= $isLoggedIn ? 'checkout.php' : 'login.php' ?>">Checkout</a>
          </div>
        <?php else: ?>
          <div class="empty-cart">Your cart is empty.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <nav class="main-nav">
    <ul>
      <li><a href="<?= $home_link ?>">Home</a></li>
      <li><a href="shop.php">Shop Plants</a></li>
      <li><a href="about_us.php">About Us</a></li>
      <li><a href="<?= $order_history_link ?>">Order History</a></li>
    </ul>
  </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const cartIcon = document.getElementById('cart-icon');
  const dropdown = document.getElementById('cart-dropdown');
  const badge = document.getElementById('cart-count');

  cartIcon.addEventListener('click', e => {
    e.preventDefault();
    dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
  });

  document.addEventListener('click', e => {
    if (!cartIcon.contains(e.target) && !dropdown.contains(e.target))
      dropdown.style.display = 'none';
  });

  function updateBadge(count) {
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = 'inline-block';
    } else {
      badge.textContent = '';
      badge.style.display = 'none';
    }
  }

  function handleRemove(btn) {
    const card = btn.closest('.cart-item-card');
    const pid = card.dataset.id;
    fetch('update_cart.php', {
      method: 'POST',
      body: new URLSearchParams({action: 'remove', product_id: pid})
    }).then(r => r.json()).then(data => { if(data.success) updateCartDropdown(data); });
  }

  function handleQuantity(pid, qty) {
    fetch('update_cart.php', {
      method: 'POST',
      body: new URLSearchParams({action: 'update', product_id: pid, quantity: qty})
    }).then(r => r.json()).then(data => { if(data.success) updateCartDropdown(data); });
  }

  function attachEvents() {
    dropdown.querySelectorAll('.remove-item').forEach(btn => {
      btn.onclick = e => { e.stopPropagation(); handleRemove(btn); };
    });
    dropdown.querySelectorAll('.increase').forEach(btn => {
      btn.onclick = () => {
        const card = btn.closest('.cart-item-card');
        let qty = parseInt(card.querySelector('.quantity').value) + 1;
        handleQuantity(card.dataset.id, qty);
      };
    });
    dropdown.querySelectorAll('.decrease').forEach(btn => {
      btn.onclick = () => {
        const card = btn.closest('.cart-item-card');
        let qty = parseInt(card.querySelector('.quantity').value) - 1;
        if (qty < 1) qty = 1;
        handleQuantity(card.dataset.id, qty);
      };
    });
  }
  attachEvents();

  function updateCartDropdown(data) {
    const items = data.items;
    const count = data.count;
    const total = data.total_price;
    updateBadge(count);

    if (!<?= json_encode($isLoggedIn) ?>)
      localStorage.setItem('guest_cart', JSON.stringify(items));

    if (items.length > 0) {
      let html = '';
      items.forEach(item => {
        html += `
        <div class="cart-item-card" data-id="${item.product_id ?? item.id}">
          <img src="images/${item.image}" alt="${item.name}">
          <div class="cart-item-details">
            <h4>${item.name}</h4>
            <p>${item.quantity} × ₱${parseFloat(item.price).toFixed(2)}</p>
            <div class="quantity-controls">
              <button class="decrease">−</button>
              <input type="text" class="quantity" value="${item.quantity}" readonly>
              <button class="increase">+</button>
            </div>
          </div>
          <button class="remove-item">&times;</button>
        </div>`;
      });
      html += `
      <div class="cart-total">SUBTOTAL: ₱<span id="cart-total">${parseFloat(total).toFixed(2)}</span></div>
      <div class="cart-buttons">
        <a href="cart.php">View Cart</a>
        <a href="<?= $isLoggedIn ? 'checkout.php' : 'login.php' ?>">Checkout</a>
      </div>`;
      dropdown.innerHTML = html;
      attachEvents();
    } else {
      dropdown.innerHTML = '<div class="empty-cart">Your cart is empty.</div>';
      badge.style.display = 'none';
    }
  }

  window.addEventListener("scroll", function(){
    const header=document.querySelector(".main-header");
    if(window.scrollY>50){header.classList.add("shrink");}
    else{header.classList.remove("shrink");}
  });
});
</script>
