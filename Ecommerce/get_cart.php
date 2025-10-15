<?php
include('db.php');
session_start();
$user_id=$_SESSION['user_id'] ?? 1;

$items=[];
$res=$conn->query("SELECT c.product_id,c.quantity,c.price,p.name,p.image 
                   FROM cart c 
                   JOIN products p ON c.product_id=p.id 
                   WHERE c.user_id=$user_id");
while($row=$res->fetch_assoc()) $items[]=$row;

$count=$conn->query("SELECT SUM(quantity) AS c FROM cart WHERE user_id=$user_id")->fetch_assoc()['c'];

echo json_encode(['success'=>true,'count'=>$count,'items'=>$items]);
?>
