<?php
require 'auth.php';
require 'db.php';

$sql = "
SELECT c.client_name, c.id as contract_id, sp.*, 
    (SELECT IFNULL(SUM(segment_fee),0) FROM service_segments WHERE service_period_id=sp.id) as contract_amount,
    (SELECT IFNULL(SUM(amount),0) FROM payments WHERE is_temp=0 AND service_segment_id IN (SELECT id FROM service_segments WHERE service_period_id=sp.id)) as paid_amount
FROM contracts c
JOIN service_periods sp ON sp.contract_id = c.id
ORDER BY sp.service_end ASC
";
$result = $db->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>催收提醒</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">催收提醒（所有未收款服务期）</h2>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
        <tr>
            <th>客户</th>
            <th>服务期</th>
            <th>截止日期</th>
            <th>合同金额</th>
            <th>已收金额</th>
            <th>待收金额</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)):
            $wait = $row['contract_amount'] - $row['paid_amount'];
            if($wait <= 0) continue; // 已收全不提醒
        ?>
        <tr>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=date('Y.m.d',strtotime($row['service_start']))?> - <?=date('Y.m.d',strtotime($row['service_end']))?></td>
            <td><?=$row['service_end']?></td>
            <td><?=number_format($row['contract_amount'],2)?></td>
            <td><?=number_format($row['paid_amount'],2)?></td>
            <td class="text-danger"><?=number_format($wait,2)?></td>
            <td>
                <a href="contract_detail.php?id=<?=$row['contract_id']?>" class="btn btn-sm btn-outline-secondary">客户详情</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>