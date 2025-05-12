<?php
require 'auth.php';
require 'db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) die('参数错误');

// 查询合同，获取签名图片路径
$row = $db->query("SELECT sign_image FROM contracts_agreement WHERE id = $id")->fetchArray(SQLITE3_ASSOC);
if (!$row) die('合同不存在');

// 删除签名图片
if (!empty($row['sign_image']) && file_exists($row['sign_image'])) {
    unlink($row['sign_image']);
}

// 删除合同记录
$db->exec("DELETE FROM contracts_agreement WHERE id = $id");

header("Location: ht_agreements.php");
exit;
?>