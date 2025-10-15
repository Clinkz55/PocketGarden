<?php
session_start();
include('db.php');

// ✅ Check login
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] != 1;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// ✅ Initialize guest cart
if (!$isLoggedIn && !isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

// ✅ Handle sort option
$sort_option = $_GET['sort'] ?? '';
$order_by = "";
switch ($sort_option) {
    case 'price_asc':  $order_by = "ORDER BY price ASC"; break;
    case 'price_desc': $order_by = "ORDER BY price DESC"; break;
    case 'name_asc':   $order_by = "ORDER BY name ASC"; break;
    case 'name_desc':  $order_by = "ORDER BY name DESC"; break;
}

// ✅ Fetch products
$query = "SELECT * FROM products $order_by";
$result = $conn->query($query);
$products = $result && $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop Plants | Pocket Garden</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#fff;color:#333;}
a{text-decoration:none;color:inherit;}
.shop-title{text-align:center;padding:80px 20px 20px;font-size:22px;text-transform:uppercase;letter-spacing:1px;color:#111;}

/* Sort Bar */
.sort-bar{width:85%;margin:0 auto 30px;text-align:right;font-size:14px;}
.sort-bar label{margin-right:10px;font-weight:500;color:#333;}
.select-wrapper{position:relative;display:inline-block;}
.select-wrapper select{
    appearance:none;-webkit-appearance:none;-moz-appearance:none;
    padding:10px 40px 10px 15px;border-radius:12px;border:1px solid #ccc;
    background:#fff url('data:image/svg+xml;utf8,<svg fill="%23666" height="12" viewBox="0 0 24 24" width="12" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 12px center;
    font-size:14px;cursor:pointer;transition:all .3s ease;outline:none;
    box-shadow:0 2px 6px rgba(0,0,0,.08);
}
.select-wrapper select:hover{border-color:#a56b7a;box-shadow:0 4px 12px rgba(0,0,0,.12);}
.select-wrapper select:focus{border-color:#3b7a57;box-shadow:0 0 0 3px rgba(59,122,87,.2);}

/* Product Grid */
.product-grid{width:85%;margin:0 auto 100px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:40px;}
.product-card{border:1px solid #eee;border-radius:12px;padding:15px;text-align:center;transition:transform .3s ease,box-shadow .3s ease;}
.product-card:hover{transform:translateY(-5px);box-shadow:0 6px 16px rgba(0,0,0,.1);}
.product-card img{width:100%;height:230px;object-fit:contain;border-radius:8px;margin-bottom:10px;}
.product-card h3{font-size:15px;font-weight:500;margin:10px 0 5px;}
.product-card p{font-size:14px;color:#444;margin-bottom:10px;}
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

@media(max-width:768px){
    .sort-bar{text-align:center;margin-bottom:20px;}
}
</style>
</head>
<body>

<!-- Header -->
<?php include('header_cart.php'); ?>

<h2 class="shop-title">PLANTS IN POCKET GARDEN</h2>

<!-- Sort -->
<div class="sort-bar">
    <form method="GET">
        <label for="sort">Sort by:</label>
        <div class="select-wrapper">
            <select name="sort" id="sort" onchange="this.form.submit()">
                <option value="" <?= $sort_option==''?'selected':'' ?>>Default</option>
                <option value="price_asc" <?= $sort_option=='price_asc'?'selected':'' ?>>Price: Low → High</option>
                <option value="price_desc" <?= $sort_option=='price_desc'?'selected':'' ?>>Price: High → Low</option>
                <option value="name_asc" <?= $sort_option=='name_asc'?'selected':'' ?>>Name: A → Z</option>
                <option value="name_desc" <?= $sort_option=='name_desc'?'selected':'' ?>>Name: Z → A</option>
            </select>
        </div>
    </form>
</div>

<!-- Product List -->
<div class="product-grid">
<?php foreach ($products as $row): ?>
<div class="product-card" data-id="<?= $row['id'] ?>">
    <img src="images/<?= htmlspecialchars($row['image']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($row['name']) ?>">
    <h3><?= htmlspecialchars($row['name']) ?></h3>
    <div class="price">₱<?= number_format($row['price'],2) ?></div>

    <div class="qty-box">
        <button class="qty-btn minus">−</button>
        <input type="number" class="qty-input" value="1" min="1">
        <button class="qty-btn plus">+</button>
    </div>

    <div class="action-btns">
        <button class="add-btn" data-id="<?= $row['id'] ?>">Add to Cart</button>
        <button class="buy-btn" data-id="<?= $row['id'] ?>">Buy Now</button>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php include('footer.php'); ?>

<!-- ✅ Live Cart JS -->
<script>
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
                // ✅ Compute number of unique items only
                const uniqueCount = data.items ? data.items.length : data.count;
                data.count = uniqueCount;

                // Dispatch event for live update
                document.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
                alert('🛒 Added to cart!');
            } else alert(data.message || 'Error adding to cart.');
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
                const uniqueCount = data.items ? data.items.length : data.count;
                data.count = uniqueCount;
                document.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));
                window.location.href='checkout.php';
            } else alert(data.message || 'Failed to proceed.');
        });
    };
});
</script>

</body>
</html>
