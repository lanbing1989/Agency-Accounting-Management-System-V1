<?php
require 'auth.php';
require 'db.php';

// 【修改1】用uuid参数，不用id
$uuid = $_GET['uuid'] ?? '';
if (!$uuid) die('参数错误');

// 查询合同，获取签名图片路径
$stmt = $db->prepare("SELECT id, sign_image FROM contracts_agreement WHERE uuid = :uuid");
$stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
if (!$row) die('合同不存在');

// 删除签名图片
if (!empty($row['sign_image']) && file_exists($row['sign_image'])) {
    unlink($row['sign_image']);
}

// 删除合同记录
$stmt = $db->prepare("DELETE FROM contracts_agreement WHERE uuid = :uuid");
$stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
$stmt->execute();

header("Location: ht_agreements.php");
exit;
?>