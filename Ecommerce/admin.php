<?php
session_start();
include('db.php');

// Simple admin session check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch dashboard data
$product_count = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'] ?? 0;
$order_count = $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'] ?? 0;
$user_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$sales = $conn->query("SELECT SUM(total_amount) AS total FROM orders")->fetch_assoc()['total'] ?? 0;

// ===============================
// ADD PRODUCT
// ===============================
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image = $_FILES['image']['name'];
    $target = "images/" . basename($image);

    if (!empty($name) && !empty($price) && move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $name, $price, $image);
        $stmt->execute();
        echo "<script>alert('Product added successfully!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Failed to upload image or missing fields!');</script>";
    }
}

// ===============================
// DELETE PRODUCT
// ===============================
if (isset($_GET['delete'])) {
    $pid = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id=$pid");
    echo "<script>alert('Product deleted!'); window.location='admin.php';</script>";
}

// ===============================
// EDIT PRODUCT FETCH
// ===============================
$edit_mode = false;
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $pid = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM products WHERE id=$pid");
    $edit_product = $result->fetch_assoc();
}

// ===============================
// UPDATE PRODUCT
// ===============================
if (isset($_POST['update_product'])) {
    $pid = intval($_POST['product_id']);
    $name = $_POST['name'];
    $price = $_POST['price'];

    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target = "images/" . basename($image);
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, image=? WHERE id=?");
        $stmt->bind_param("sdsi", $name, $price, $image, $pid);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name=?, price=? WHERE id=?");
        $stmt->bind_param("sdi", $name, $price, $pid);
    }
    $stmt->execute();
    echo "<script>alert('Product updated successfully!'); window.location='admin.php';</script>";
}

// ===============================
// BANNER UPLOAD
// ===============================
if (isset($_POST['upload_banner'])) {
    $banner = $_FILES['banner']['name'];
    $target = "images/" . basename($banner);

    if (!empty($banner) && move_uploaded_file($_FILES['banner']['tmp_name'], $target)) {
        // Insert into banners table
        $conn->query("INSERT INTO banners (image) VALUES ('$banner')");
        echo "<script>alert('Banner uploaded successfully!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Failed to upload banner!');</script>";
    }
}

// ===============================
// ACTIVATE BANNER
// ===============================
if (isset($_POST['activate_banner'])) {
    $banner_id = intval($_POST['banner_id']);
    $conn->query("UPDATE banners SET is_active=0");
    $conn->query("UPDATE banners SET is_active=1 WHERE id=$banner_id");
    echo "<script>alert('Banner activated!'); window.location='admin.php';</script>";
}

// Fetch products & orders
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
$orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");

// Fetch banners
$banners = $conn->query("SELECT * FROM banners ORDER BY id DESC");
$active_banner = $conn->query("SELECT * FROM banners WHERE is_active=1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pocket Garden Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background:#f7f7f7; margin:0; overflow-y:scroll; }
.admin-container { width:90%; margin:100px auto 50px; padding:20px; background:#fff; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1);}
header { background:#a56b7a; color:#fff; text-align:center; padding:20px 0; position:fixed; top:0; width:100%; z-index:1000; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
header h1 { margin:0; font-size:24px;}
.dashboard { display:flex; flex-wrap:wrap; justify-content:space-between; margin-top:20px;}
.card { flex:1 1 22%; background:#f9f9f9; border:1px solid #ddd; border-radius:8px; text-align:center; padding:20px; margin:10px; min-width:180px; transition:all .3s;}
.card:hover { transform:translateY(-3px); background:#fff; box-shadow:0 3px 8px rgba(0,0,0,0.08);}
.card h3 { margin:10px 0 0; font-size:18px; color:#333; }
.card p { font-size:28px; color:#a56b7a; margin:0; }

.section { margin-top:50px;}
.section h2 { color:#333; margin-bottom:15px; border-left:5px solid #a56b7a; padding-left:10px;}
table { width:100%; border-collapse:collapse; margin-bottom:30px;}
th, td { border:1px solid #ddd; padding:10px; text-align:left;}
th { background:#f0f0f0;}
img { width:60px; height:60px; border-radius:8px; object-fit:cover;}
button, input[type="submit"] { background:#a56b7a; border:none; color:#fff; padding:8px 14px; border-radius:6px; cursor:pointer; transition:.3s;}
button:hover, input[type="submit"]:hover { background:#8c5b68;}
.form-container { background:#fafafa; padding:20px; border:1px solid #eee; border-radius:8px; margin-bottom:20px;}
input[type="text"], input[type="number"], input[type="file"] { width:100%; padding:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:6px;}
.edit-img-preview { width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid #ccc; display:block; margin-bottom:10px;}
.banner-preview img { width:100%; max-height:300px; object-fit:cover; border-radius:10px; border:1px solid #ccc;}
@media(max-width:768px){ .dashboard{flex-direction:column;} .card{width:100%;}}
</style>
</head>
<body>

<header>
  <h1>🌿 Pocket Garden Admin Dashboard</h1>
</header>

<div class="admin-container">

  <!-- 📊 DASHBOARD CARDS -->
  <div class="dashboard">
    <div class="card"><h3>Total Products</h3><p><?= $product_count ?></p></div>
    <div class="card"><h3>Total Orders</h3><p><?= $order_count ?></p></div>
    <div class="card"><h3>Total Users</h3><p><?= $user_count ?></p></div>
    <div class="card"><h3>Total Sales</h3><p>₱<?= number_format($sales,2) ?></p></div>
  </div>

  <!-- 🪴 PRODUCT MANAGEMENT -->
  <div class="section">
    <h2><?= $edit_mode ? 'Edit Product' : 'Add Product' ?></h2>
    <div class="form-container">
      <form method="POST" enctype="multipart/form-data">
        <?php if ($edit_mode): ?>
          <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
        <?php endif; ?>

        <input type="text" name="name" placeholder="Product Name" 
               value="<?= $edit_mode ? htmlspecialchars($edit_product['name']) : '' ?>" required>
        <input type="number" name="price" placeholder="Price" step="0.01" 
               value="<?= $edit_mode ? htmlspecialchars($edit_product['price']) : '' ?>" required>
        <?php if ($edit_mode && !empty($edit_product['image'])): ?>
          <img src="images/<?= htmlspecialchars($edit_product['image']) ?>" class="edit-img-preview">
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">
        <input type="submit" 
               name="<?= $edit_mode ? 'update_product' : 'add_product' ?>" 
               value="<?= $edit_mode ? 'Save Product' : 'Add Product' ?>">
      </form>
    </div>

    <table>
      <tr>
        <th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Actions</th>
      </tr>
      <?php while($row = $products->fetch_assoc()): ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><img src="images/<?= htmlspecialchars($row['image']) ?>" alt=""></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td>₱<?= number_format($row['price'],2) ?></td>
        <td>
          <a href="?edit=<?= $row['id'] ?>">✏ Edit</a> |
          <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this product?');">🗑 Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <!-- 🧾 ORDER MANAGEMENT -->
  <div class="section">
    <h2>Order History</h2>
    <table>
      <tr>
        <th>Order ID</th><th>User ID</th><th>Total</th><th>Date Ordered</th>
      </tr>
      <?php while($o = $orders->fetch_assoc()): ?>
      <tr>
        <td><?= $o['id'] ?></td>
        <td><?= $o['user_id'] ?></td>
        <td>₱<?= number_format($o['total_amount'],2) ?></td>
        <td><?= $o['order_date'] ?></td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <!-- 🖼 BANNER MANAGEMENT -->
  <div class="section">
    <h2>Banner Management</h2>
    <div class="form-container">
      <form method="POST" enctype="multipart/form-data">
        <input type="file" name="banner" accept="image/*" required>
        <input type="submit" name="upload_banner" value="Upload Banner">
      </form>
    </div>

    <?php if($banners->num_rows > 0): ?>
      <table>
        <tr>
          <th>ID</th><th>Banner Image</th><th>Status</th><th>Action</th>
        </tr>
        <?php while($b = $banners->fetch_assoc()): ?>
        <tr>
          <td><?= $b['id'] ?></td>
          <td><img src="images/<?= htmlspecialchars($b['image']) ?>" style="width:150px;height:80px;object-fit:cover;"></td>
          <td><?= $b['is_active'] ? 'Active' : 'Inactive' ?></td>
          <td>
            <?php if(!$b['is_active']): ?>
            <form method="POST">
              <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
              <input type="submit" name="activate_banner" value="Activate">
            </form>
            <?php else: ?>
              <span>Currently Active</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p style="text-align:center;color:#777;">No banners uploaded yet.</p>
    <?php endif; ?>


  </div>

</div>
</body>
</html>
