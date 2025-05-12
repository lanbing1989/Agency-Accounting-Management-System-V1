<?php
require 'auth.php';
require 'db.php';
$service_period_id = intval($_GET['service_period_id']);
$options = '<option value="">不选择分段</option>';
$res = $db->query("SELECT * FROM service_segments WHERE service_period_id=$service_period_id ORDER BY id ASC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $txt = $row['start_date'].' ~ '.$row['end_date'].' / '.$row['package_type'].' / '.$row['price_per_year'].'元/年';
    $options .= '<option value="'.$row['id'].'">'.htmlspecialchars($txt).'</option>';
}
echo $options;