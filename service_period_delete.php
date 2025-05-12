<?php
require 'auth.php';
require 'db.php';
$id = intval($_GET['id']);
$contract_id = intval($_GET['contract_id']);

// 删除分段和对应收费
$segs = $db->query("SELECT id FROM service_segments WHERE service_period_id=$id");
while($seg = $segs->fetchArray(SQLITE3_ASSOC)) {
    $segid = $seg['id'];
    $db->exec("DELETE FROM payments WHERE service_segment_id=$segid");
    $db->exec("DELETE FROM service_segments WHERE id=$segid");
}
// 删除服务期
$db->exec("DELETE FROM service_periods WHERE id=$id");

header('Location: contract_detail.php?id='.$contract_id);
exit;
?>