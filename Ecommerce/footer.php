<?php
if(session_status() == PHP_SESSION_NONE) session_start();
include('db.php');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// Only fetch cart if logged in
$cart_count = 0;
if($isLoggedIn){
    $cart_count = $conn->query("SELECT COUNT(*) as c FROM cart WHERE user_id=$user_id")->fetch_assoc()['c'];
}

// Detect current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.main-footer {
  border-top: 1px solid #eee;
  padding: 40px 10%;
  text-align: center;
  color: #333;
  background: #f9f9f9;
  font-family: 'Poppins', sans-serif;
}

.footer-links {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 20px;
  margin-bottom: 20px;
}

.footer-links a {
  color: #000;
  text-decoration: none;
  font-size: 14px;
  transition: 0.3s;
}

.footer-links a:hover {
  color: #a56b7a;
}

.social-icons {
  margin-bottom: 20px;
}

.social-icons a {
  color: #000;
  font-size: 20px;
  margin-right: 15px;
  text-decoration: none;
  transition: 0.3s;
}

.social-icons a:hover {
  color: #a56b7a;
}

.footer-bottom p {
  font-size: 13px;
  color: #555;
}

.cart-badge-footer {
  display: inline-block;
  margin-left: 5px;
  background:#a56b7a;
  color:#fff;
  font-size:12px;
  padding:2px 6px;
  border-radius:50%;
}

@media(max-width:768px){
  .footer-links { flex-direction: column; gap: 10px; }
  .social-icons a { margin-right: 10px; font-size:18px; }
}
</style>

<footer class="main-footer">

  <div class="social-icons">
    <a href="#"><i class="fa-brands fa-facebook"></i></a>
    <a href="#"><i class="fa-brands fa-instagram"></i></a>
    <a href="#"><i class="fa-brands fa-twitter"></i></a>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?= date('Y'); ?> Pocket Garden. All rights reserved.</p>
  </div>
</footer>
