<?php
require 'auth.php';
require 'db.php';
$id = intval($_GET['id']);
$period_id = intval($_GET['period_id']);
$db->exec("DELETE FROM payments WHERE id=$id");
header('Location: payment_list.php?period_id='.$period_id);
exit;
?>