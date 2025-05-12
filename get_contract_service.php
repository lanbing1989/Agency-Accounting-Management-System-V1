<?php
require 'db.php';
$client_id = intval($_GET['client_id'] ?? 0);

// 假设 contracts 表有服务相关字段，如果不是请根据你的实际表结构调整
$row = $db->query("SELECT service_start, service_end, package_type, price_per_year, segment_fee FROM contracts WHERE id=$client_id")->fetchArray(SQLITE3_ASSOC);

if ($row) {
    echo json_encode(['ok'=>true, 'data'=>$row]);
} else {
    echo json_encode(['ok'=>false, 'msg'=>'未找到客户信息']);
}