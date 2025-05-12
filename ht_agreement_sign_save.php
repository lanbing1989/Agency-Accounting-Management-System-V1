<?php
require 'db.php';

$id = intval($_GET['id'] ?? 0);

// 查询是否已签署
$row = $db->query("SELECT sign_image, sign_date FROM contracts_agreement WHERE id=$id")->fetchArray(SQLITE3_ASSOC);
if (!$row) {
    echo json_encode(['ok'=>false, 'msg'=>'合同不存在']);
    exit;
}
if (!empty($row['sign_image']) && file_exists(__DIR__ . '/' . $row['sign_image'])) {
    echo json_encode(['ok'=>false, 'msg'=>'该合同已签署，不能重复签署！']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$imgBase64 = $data['signature'] ?? '';

if (!$id || !$imgBase64 || strpos($imgBase64, 'data:image/png;base64,') !== 0) {
    echo json_encode(['ok'=>false, 'msg'=>'参数错误']);
    exit;
}

// 保存为本地图片文件
$imgData = base64_decode(str_replace('data:image/png;base64,','',$imgBase64));
$saveDir = __DIR__.'/signatures/';
if (!is_dir($saveDir)) mkdir($saveDir,0777,true);
$filename = $saveDir . 'sign_' . $id . '_' . time() . '.png';
file_put_contents($filename, $imgData);
// 更新合同，增加签署日期
$relativePath = 'signatures/' . basename($filename);
$sign_date = date('Y-m-d');
$db->exec("UPDATE contracts_agreement SET sign_image='$relativePath', sign_date='$sign_date' WHERE id=$id");
echo json_encode(['ok'=>true]);
?>