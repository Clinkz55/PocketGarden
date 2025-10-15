<?php
session_start();
include('db.php');
include('header_cart.php');

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

if ($isLoggedIn) {
    $cart_query = $conn->prepare("
        SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $cart_query->bind_param("i", $user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart | Pocket Garden</title>
<style>
body{background-color:#faf9f7;font-family:'Poppins',sans-serif;}
.cart-container{width:90%;max-width:1100px;margin:120px auto 60px;background:#fff;padding:30px;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.05);}
h2{text-align:center;margin-bottom:25px;font-weight:600;color:#3d3d3d;}
table{width:100%;border-collapse:collapse;}
thead{border-bottom:2px solid #ddd;}
th,td{padding:15px;text-align:left;vertical-align:middle;}
th{color:#777;text-transform:uppercase;font-size:14px;}
td img{width:70px;border-radius:10px;}
.product-name{font-weight:600;color:#444;margin-left:10px;}
.quantity-control{display:inline-flex;align-items:center;border:1px solid #ccc;border-radius:6px;overflow:hidden;}
.quantity-control button{background:#c18f82;color:#fff;border:none;padding:5px 10px;cursor:pointer;font-weight:bold;}
.quantity-control input{width:40px;text-align:center;border:none;font-weight:600;}
.cart-summary{margin-top:30px;text-align:right;}
.cart-summary p{font-size:16px;color:#444;margin:6px 0;}
.cart-summary strong{font-size:18px;}
.checkout-btn{background:#c18f82;color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:8px;margin-top:15px;cursor:pointer;font-weight:600;transition:.3s;}
.checkout-btn:hover{background:#b57d73;}
.empty-cart{text-align:center;padding:40px;color:#777;font-size:18px;}
</style>
</head>
<body>

<div class="cart-container">
<h2>Your Cart</h2>
<?php
$total = 0; $item_count = 0; $cart_items = [];
if ($isLoggedIn) {
    $cart_query->execute();
    $result = $cart_query->get_result();
    if ($result->num_rows > 0) {
        echo '<table><thead><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Total</th></tr></thead><tbody>';
        while ($row = $result->fetch_assoc()) {
            $imagePath = 'images/' . $row['image'];
            $item_total = $row['price'] * $row['quantity'];
            $total += $item_total; $cart_items[] = $row; $item_count++;
            echo '<tr data-id="'.$row['cart_id'].'" data-price="'.$row['price'].'">
                    <td><img src="'.$imagePath.'" alt="'.$row['name'].'"><span class="product-name">'.$row['name'].'</span></td>
                    <td>₱'.number_format($row['price'],2).'</td>
                    <td>
                        <div class="quantity-control">
                            <button class="decrease" data-id="'.$row['cart_id'].'">−</button>
                            <input type="text" value="'.$row['quantity'].'" readonly>
                            <button class="increase" data-id="'.$row['cart_id'].'">+</button>
                        </div>
                    </td>
                    <td class="item-total">₱'.number_format($item_total,2).'</td>
                  </tr>';
        }
        echo '</tbody></table>';
    } else echo '<div class="empty-cart">Your cart is empty.</div>';
} else {
    if (!empty($_SESSION['guest_cart'])) {
        $cart_items = $_SESSION['guest_cart'];
        echo '<table><thead><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Total</th></tr></thead><tbody>';
        foreach ($cart_items as $item) {
            $imagePath = 'images/' . $item['image'];
            $item_total = $item['price'] * $item['quantity'];
            $total += $item_total; $item_count++;
            echo '<tr data-id="'.$item['product_id'].'" data-price="'.$item['price'].'">
                    <td><img src="'.$imagePath.'" alt="'.$item['name'].'"><span class="product-name">'.$item['name'].'</span></td>
                    <td>₱'.number_format($item['price'],2).'</td>
                    <td>
                        <div class="quantity-control">
                            <button class="decrease" data-id="'.$item['product_id'].'">−</button>
                            <input type="text" value="'.$item['quantity'].'" readonly>
                            <button class="increase" data-id="'.$item['product_id'].'">+</button>
                        </div>
                    </td>
                    <td class="item-total">₱'.number_format($item_total,2).'</td>
                  </tr>';
        }
        echo '</tbody></table>';
    } else echo '<div class="empty-cart">Your cart is empty.</div>';
}
$_SESSION['cart_count_display'] = $item_count;

if ($item_count > 0) {
    $shipping_fee = 50; // fixed shipping fee
    $grand_total = $total + $shipping_fee;
    echo '<div class="cart-summary">
            <p>Subtotal: ₱<span id="subtotal">'.number_format($total,2).'</span></p>
            <p>Shipping Fee: ₱<span id="shipping">'.number_format($shipping_fee,2).'</span></p>
            <p><strong>Grand Total: ₱<span id="grand">'.number_format($grand_total,2).'</span></strong></p>
            <button class="checkout-btn" onclick="window.location=\'checkout.php\'">Check Out</button>
          </div>';
}
?>
</div>

<?php include('footer.php'); ?>

<script>
document.addEventListener("DOMContentLoaded",()=>{
  const shippingFee = 50; // fixed shipping fee

  const updateTotals=()=>{
    let subtotal=0;
    document.querySelectorAll("tbody tr").forEach(row=>{
      const price=parseFloat(row.dataset.price);
      const qty=parseInt(row.querySelector("input").value);
      const total=price*qty;
      row.querySelector(".item-total").textContent="₱"+total.toFixed(2);
      subtotal+=total;
    });
    const grand=subtotal + shippingFee;
    document.getElementById("subtotal").textContent=subtotal.toFixed(2);
    document.getElementById("shipping").textContent=shippingFee.toFixed(2);
    document.getElementById("grand").textContent=grand.toFixed(2);
  };

  document.querySelectorAll(".increase,.decrease").forEach(btn=>{
    btn.addEventListener("click",()=>{
      const row=btn.closest("tr");
      const input=row.querySelector("input");
      let qty=parseInt(input.value);
      qty=btn.classList.contains("increase")?qty+1:Math.max(1,qty-1);
      input.value=qty;
      updateTotals();
      
      fetch("update_cart_quantity.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`cart_id=${btn.dataset.id}&quantity=${qty}`
      });
    });
  });
});
</script>

</body>
</html>
