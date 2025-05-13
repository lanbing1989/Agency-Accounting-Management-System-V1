<?php
require 'auth.php';
require 'db.php';
$period_id = intval($_GET['period_id']);

// 查询服务期和所属合同
$period = $db->query("SELECT * FROM service_periods WHERE id=$period_id")->fetchArray(SQLITE3_ASSOC);
$contract = $db->query("SELECT * FROM contracts WHERE id=" . intval($period['contract_id']))->fetchArray(SQLITE3_ASSOC);

// 查询所有分段
$segments = [];
$res = $db->query("SELECT * FROM service_segments WHERE service_period_id=$period_id");
while($row = $res->fetchArray(SQLITE3_ASSOC)) $segments[$row['id']] = $row;

// 查询所有收费记录
$payments = $db->query("
    SELECT p.*, s.start_date, s.end_date 
    FROM payments p 
    LEFT JOIN service_segments s ON p.service_segment_id=s.id 
    WHERE p.service_segment_id IN (SELECT id FROM service_segments WHERE service_period_id=$period_id)
    ORDER BY p.pay_date DESC, p.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>收费记录</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h4>客户：<?=htmlspecialchars($contract['client_name'])?></h4>
    <div class="mb-2">
        <b>服务期：</b><?=date('Y.m.d',strtotime($period['service_start']))?> - <?=date('Y.m.d',strtotime($period['service_end']))?>
    </div>
    <a href="payment_add.php?period_id=<?=$period_id?>" class="btn btn-success btn-sm mb-2">新增收费</a>
    <a href="contract_detail.php?id=<?=$period['contract_id']?>" class="btn btn-secondary btn-sm mb-2">返回</a>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>分段期间</th>
                <th>收费日期</th>
                <th>金额</th>
                <th>备注</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php while($p = $payments->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <td><?=$p['id']?></td>
                <td>
                    <?php
                    if ($p['start_date'] && $p['end_date']) {
                        echo date('Y.m.d', strtotime($p['start_date'])) . " - " . date('Y.m.d', strtotime($p['end_date']));
                    } else {
                        echo "-";
                    }
                    ?>
                </td>
                <td><?=htmlspecialchars($p['pay_date'])?></td>
                <td><?=$p['amount']?></td>
                <td><?=htmlspecialchars($p['remark'])?></td>
                <td>
                    <a href="payment_delete.php?id=<?=$p['id']?>&period_id=<?=$period_id?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除此收费记录？')">删除</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>