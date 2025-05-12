<?php
require 'db.php';
$id = intval($_GET['id']);
// 删除 payments
$db->exec("DELETE FROM payments WHERE contract_id=$id");
// 删除 service_segments
$periods = $db->query("SELECT id FROM service_periods WHERE contract_id=$id");
while($p = $periods->fetchArray(SQLITE3_ASSOC)) {
    $pid = $p['id'];
    $db->exec("DELETE FROM service_segments WHERE service_period_id=$pid");
}
// 删除 service_periods
$db->exec("DELETE FROM service_periods WHERE contract_id=$id");
// 删除 contract
$db->exec("DELETE FROM contracts WHERE id=$id");
header('Location: index.php');
exit;
?>