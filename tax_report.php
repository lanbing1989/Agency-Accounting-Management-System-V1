<?php
require 'auth.php';
require 'db.php';

$operator = $_SESSION['username'] ?? '未知';

// 默认“纳税所属期”为上个月
$now = new DateTime();
$now->modify('-1 month');
$default_period = $now->format('Y-m');
$period = $_GET['period'] ?? $default_period;
if (!preg_match('/^\d{4}-\d{2}$/', $period)) $period = $default_period;

$period_start = $period . '-01';
$period_end = date('Y-m-t', strtotime($period_start));

$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 50;
$offset = ($page - 1) * $page_size;

// 处理申报登记、反标记、备注
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['contract_id'])) {
    $cid = intval($_POST['contract_id']);
    $action = $_POST['action'];
    $remark = trim($_POST['remark'] ?? '');
    $stmt = $db->prepare("SELECT * FROM tax_declare_records WHERE contract_id=:cid AND declare_period=:period");
    $stmt->bindValue(':cid', $cid);
    $stmt->bindValue(':period', $period);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$row) {
        $db->exec("INSERT INTO tax_declare_records (contract_id, declare_period, operator) VALUES ($cid, '$period', '$operator')");
        $stmt = $db->prepare("SELECT * FROM tax_declare_records WHERE contract_id=:cid AND declare_period=:period");
        $stmt->bindValue(':cid', $cid);
        $stmt->bindValue(':period', $period);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }
    $id = $row ? $row['id'] : $db->lastInsertRowID();

    if ($action === 'ele_tax') {
        $stmt = $db->prepare("UPDATE tax_declare_records SET ele_tax_reported_at=:dt, operator=:op WHERE id=:id");
        $stmt->bindValue(':dt', date('Y-m-d H:i:s'));
        $stmt->bindValue(':op', $operator);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    } elseif ($action === 'personal_tax') {
        $stmt = $db->prepare("UPDATE tax_declare_records SET personal_tax_reported_at=:dt, operator=:op WHERE id=:id");
        $stmt->bindValue(':dt', date('Y-m-d H:i:s'));
        $stmt->bindValue(':op', $operator);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    } elseif ($action === 'ele_tax_unmark') {
        $stmt = $db->prepare("UPDATE tax_declare_records SET ele_tax_reported_at=NULL, operator=:op WHERE id=:id");
        $stmt->bindValue(':op', $operator);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    } elseif ($action === 'personal_tax_unmark') {
        $stmt = $db->prepare("UPDATE tax_declare_records SET personal_tax_reported_at=NULL, operator=:op WHERE id=:id");
        $stmt->bindValue(':op', $operator);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    } elseif ($action === 'remark') {
        $stmt = $db->prepare("UPDATE tax_declare_records SET remark=:remark, operator=:op WHERE id=:id");
        $stmt->bindValue(':remark', $remark);
        $stmt->bindValue(':op', $operator);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }
    header("Location: tax_report.php?period=$period&page=$page");
    exit;
}

$count_sql = "SELECT COUNT(DISTINCT c.id) FROM contracts c
    JOIN service_periods sp ON sp.contract_id=c.id
    WHERE sp.service_start <= :period_end AND sp.service_end >= :period_start";
$stmt = $db->prepare($count_sql);
$stmt->bindValue(':period_start', $period_start);
$stmt->bindValue(':period_end', $period_end);
$total = $stmt->execute()->fetchArray(SQLITE3_NUM)[0];
$total_pages = max(1, ceil($total / $page_size));

$sql = "SELECT c.*, MIN(sp.service_start) as min_service_start, MAX(sp.service_end) as max_service_end
    FROM contracts c
    JOIN service_periods sp ON sp.contract_id=c.id
    WHERE sp.service_start <= :period_end AND sp.service_end >= :period_start
    GROUP BY c.id
    ORDER BY c.id ASC
    LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
$stmt->bindValue(':period_start', $period_start);
$stmt->bindValue(':period_end', $period_end);
$stmt->bindValue(':limit', $page_size, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$res = $stmt->execute();

$clients = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $stmt2 = $db->prepare("SELECT * FROM tax_declare_records WHERE contract_id=:cid AND declare_period=:period");
    $stmt2->bindValue(':cid', $row['id']);
    $stmt2->bindValue(':period', $period);
    $record = $stmt2->execute()->fetchArray(SQLITE3_ASSOC);
    $row['tax_record'] = $record;
    $clients[] = $row;
}

// 生成最近12个月（含本月）的“纳税所属期”选项
$month_options = [];
for ($i = 0; $i < 12; $i++) {
    $m = (new DateTime())->modify('-'.$i.' month'); // i=0为本月
    $val = $m->format('Y-m');
    $month_options[] = $val;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>税务申报提醒/登记</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    .small-remark { font-size: 0.95em; color: #666; }
    .reported { color: green; font-weight: bold; }
    .notyet { color: #c00; font-weight: bold; }
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>税务申报登记</h2>
        <form class="d-flex" method="get">
            <label class="me-2 align-self-center">纳税所属期</label>
            <select name="period" class="form-select me-2" onchange="this.form.submit()">
                <?php foreach ($month_options as $opt): ?>
                <option value="<?=$opt?>" <?=$opt==$period?'selected':''?>><?=$opt?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>客户名称</th>
            <th>联系人</th>
            <th>联系电话</th>
            <th>服务期</th>
            <th>电子税务局申报</th>
            <th>个税客户端申报</th>
            <th>备注</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($clients as $row):
            $rec = $row['tax_record'] ?: [];
        ?>
        <tr>
            <td><?=$row['id']?></td>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=htmlspecialchars($row['contact_person'])?></td>
            <td><?=htmlspecialchars($row['contact_phone'])?></td>
            <td><?=htmlspecialchars($row['min_service_start'])?> ~ <?=htmlspecialchars($row['max_service_end'])?></td>
            <td>
                <?php if (!empty($rec['ele_tax_reported_at'])): ?>
                    <span class="reported">已申报</span>
                    <div class="small-remark"><?=$rec['ele_tax_reported_at']?></div>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="contract_id" value="<?=$row['id']?>">
                        <input type="hidden" name="action" value="ele_tax_unmark">
                        <button type="submit" class="btn btn-sm btn-outline-danger mt-1">取消已申报</button>
                    </form>
                <?php else: ?>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="contract_id" value="<?=$row['id']?>">
                        <input type="hidden" name="action" value="ele_tax">
                        <button type="submit" class="btn btn-sm btn-primary">标记已申报</button>
                    </form>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($rec['personal_tax_reported_at'])): ?>
                    <span class="reported">已申报</span>
                    <div class="small-remark"><?=$rec['personal_tax_reported_at']?></div>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="contract_id" value="<?=$row['id']?>">
                        <input type="hidden" name="action" value="personal_tax_unmark">
                        <button type="submit" class="btn btn-sm btn-outline-danger mt-1">取消已申报</button>
                    </form>
                <?php else: ?>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="contract_id" value="<?=$row['id']?>">
                        <input type="hidden" name="action" value="personal_tax">
                        <button type="submit" class="btn btn-sm btn-primary">标记已申报</button>
                    </form>
                <?php endif; ?>
            </td>
            <td>
                <form method="post" class="d-flex" style="gap:4px">
                    <input type="hidden" name="contract_id" value="<?=$row['id']?>">
                    <input type="hidden" name="action" value="remark">
                    <input type="text" name="remark" class="form-control form-control-sm" value="<?=htmlspecialchars($rec['remark'] ?? '')?>" placeholder="备注">
                    <button type="submit" class="btn btn-sm btn-secondary">保存</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <!-- 分页导航 -->
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>1]))?>">首页</a>
            </li>
            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>max(1,$page-1)]))?>">&laquo;</a>
            </li>
            <li class="page-item disabled">
                <span class="page-link">第 <?=$page?> / <?=$total_pages?> 页</span>
            </li>
            <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>min($total_pages,$page+1)]))?>">&raquo;</a>
            </li>
            <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>$total_pages]))?>">尾页</a>
            </li>
        </ul>
    </nav>
    <div class="mb-3 text-center text-muted">
        共 <?=$total?> 条，每页 <?=$page_size?> 条
    </div>
</div>
</body>
</html>