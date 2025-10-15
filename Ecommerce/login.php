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

// ✅ Login logic
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ✅ Check if admin account
    if ($email === 'pocketgarden@gmail.com' && $password === '123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_email'] = $email;
        header("Location: admin.php");
        exit();
    }

    // ✅ Regular user login
    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user['name'];
                header("Location: home.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with that email.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Pocket Garden</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#fff;color:#333;}
.login-section{min-height:80vh;display:flex;justify-content:center;align-items:center;padding:60px 20px;background:#f9f9f9;}
.login-container{
  background:#fff;
  border:5px solid #eee;
  border-radius:10px;
  padding:20px 30px;
  width:100%;
  max-width:600px;
  box-shadow:0 6px 18px rgba(0,0,0,0.1);
  margin-top: 150px; /* adjust value as needed */
}
.login-container h2{text-align:center;margin-bottom:30px;color:#333;}
.login-container form{display:flex;flex-direction:column;}
.login-container label{font-weight:600;margin-bottom:5px;color:#333;}
.input-group{position:relative;margin-bottom:20px;}
.input-group input{width:100%;padding:12px 45px 12px 12px;border:1px solid #ccc;border-radius:6px;font-size:15px;}
.input-group i{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#888;cursor:pointer;}
.login-container button{
  background:#4a6b57;
  color:#fff;
  border:none;
  padding:14px;
  border-radius:6px;
  cursor:pointer;
  font-weight:600;
  letter-spacing:1px;
  font-size:15px;
  transition:.3s;
}
.login-container button:hover{background:#3b5948;}
.login-container .error{color:#b20000;font-size:13px;margin-bottom:10px;text-align:center;}
.login-container p{text-align:center;font-size:14px;color:#555;margin-top:15px;}
.login-container a{color:#4a6b57;text-decoration:none;font-weight:500;}
.login-container a:hover{text-decoration:underline;}

@media(max-width:768px){
  .login-container{padding:40px 25px;max-width:95%;}
}
</style>
</head>
<body>

<!-- ✅ Include Header with Cart -->
<?php include('header_cart.php'); ?>

<!-- Login Section -->
<section class="login-section">
  <div class="login-container">
    <h2>Login to Your Account</h2>
    <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label for="email">Email</label>
      <div class="input-group">
        <input type="email" id="email" name="email" placeholder="Enter your email" required>
      </div>

      <label for="password">Password</label>
      <div class="input-group">
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
        <i class="fa-regular fa-eye" id="togglePassword"></i>
      </div>

      <button type="submit">Login</button>
    </form>
    <p>Don’t have an account? <a href="register.php">Register here</a></p>
  </div>
</section>

<!-- ✅ Include Footer -->
<?php include('footer.php'); ?>

<script>
const togglePassword = document.querySelector("#togglePassword");
const passwordField = document.querySelector("#password");
togglePassword.addEventListener("click", function() {
  const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
  passwordField.setAttribute("type", type);
  this.classList.toggle("fa-eye-slash");
});
</script>

</body>
</html>
