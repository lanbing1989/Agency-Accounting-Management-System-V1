<?php
require 'auth.php';
require 'db.php';

$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$today = date('Y-m-d');
$deadline = date('Y-m-d', strtotime("+$days days"));

// 只提醒未被续期（即该服务期后无新服务期）的服务期
$sql = "
SELECT c.client_name, c.id as contract_id, sp.*, 
    (SELECT IFNULL(SUM(segment_fee),0) FROM service_segments WHERE service_period_id=sp.id) as contract_amount
FROM contracts c
JOIN service_periods sp ON sp.contract_id = c.id
WHERE sp.service_end <= :deadline
AND NOT EXISTS (
    SELECT 1 FROM service_periods sp2
    WHERE sp2.contract_id = sp.contract_id
    AND sp2.service_start > sp.service_end
)
ORDER BY sp.service_end ASC
";
$stmt = $db->prepare($sql);
$stmt->bindValue(':deadline', $deadline);
$result = $stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>服务期到期提醒</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">服务期到期提醒</h2>
    <form method="get" class="mb-3">
        <label>提前提醒天数：</label>
        <input type="number" name="days" value="<?=$days?>" min="1" style="width:80px;">
        <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
        <tr>
            <th>客户</th>
            <th>服务期</th>
            <th>截止日期</th>
            <th>合同金额</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)):
            $is_expired = strtotime($row['service_end']) < strtotime($today);
        ?>
        <tr>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=date('Y.m.d',strtotime($row['service_start']))?> - <?=date('Y.m.d',strtotime($row['service_end']))?></td>
            <td><?=$row['service_end']?></td>
            <td><?=number_format($row['contract_amount'],2)?></td>
            <td>
                <?php if ($is_expired): ?>
                    <span class="badge bg-danger">已到期</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">即将到期</span>
                <?php endif; ?>
            </td>
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