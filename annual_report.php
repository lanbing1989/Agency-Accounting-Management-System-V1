<?php
require 'auth.php';
require 'db.php';

// 1. 当前应申报年度
$now = new DateTime();
$report_year = $now->format('Y') - 1;
$deadline = new DateTime(($report_year + 1) . '-06-30');
$days_left = (int)$now->diff($deadline)->format('%r%a');

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 50;
$offset = ($page - 1) * $page_size;

// 2. 标记已申报操作
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contract_id'])) {
    $cid = intval($_POST['contract_id']);
    // 查重
    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM annual_reports WHERE contract_id = :cid AND year = :year');
    $stmt->bindValue(':cid', $cid);
    $stmt->bindValue(':year', $report_year);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$row['cnt']) {
        $stmt = $db->prepare('INSERT INTO annual_reports (contract_id, year, reported_at) VALUES (:cid, :year, :at)');
        $stmt->bindValue(':cid', $cid);
        $stmt->bindValue(':year', $report_year);
        $stmt->bindValue(':at', date('Y-m-d H:i:s'));
        $stmt->execute();
    }
    header("Location: annual_report.php?page=$page");
    exit;
}

// 3. 统计总数
$count_query = "
    SELECT COUNT(DISTINCT c.id) AS total
    FROM contracts c
    JOIN service_periods sp ON sp.contract_id = c.id
    WHERE sp.service_end >= DATE('now')
";
$total = $db->querySingle($count_query);
$total_pages = max(1, ceil($total / $page_size));

// 4. 查询分页客户
$clients = [];
$q = "
    SELECT c.*, MAX(sp.service_end) as service_end
    FROM contracts c
    JOIN service_periods sp ON sp.contract_id = c.id
    WHERE sp.service_end >= DATE('now')
    GROUP BY c.id
    ORDER BY service_end DESC, c.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($q);
$stmt->bindValue(':limit', $page_size, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$r = $stmt->execute();

while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    // 检查是否已申报
    $stmt2 = $db->prepare('SELECT * FROM annual_reports WHERE contract_id = :cid AND year = :year');
    $stmt2->bindValue(':cid', $row['id']);
    $stmt2->bindValue(':year', $report_year);
    $ar = $stmt2->execute()->fetchArray(SQLITE3_ASSOC);
    $row['annual_report'] = $ar;
    $clients[] = $row;
}

// 5. 判断是否到6月，给重点色
$month = intval($now->format('m'));
$is_last_month = ($month == 6);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>工商年报登记</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    .deadline-alert {font-weight: bold;}
    .deadline-critical {color: #fff; background: #d9534f; padding: 0.3em 0.7em; border-radius: 6px;}
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">工商年报登记</h2>
    <div class="mb-3">
        <span class="deadline-alert <?=$is_last_month?'deadline-critical':'text-danger'?>">
            申报年度：<?=$report_year?> 年度，截止日期：<?=$deadline->format('Y年m月d日')?>
            ，剩余
            <?php if ($days_left >= 0): ?>
                <b><?=$days_left?></b> 天
                <?php if ($is_last_month): ?>
                     <span class="ms-2">（重点提醒：本月为最后申报月！）</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-danger">已过截止日！</span>
            <?php endif; ?>
        </span>
    </div>
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>客户名称</th>
            <th>联系人</th>
            <th>联系电话</th>
            <th>服务期截止</th>
            <th><?=$report_year?>年度年报</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($clients as $row): ?>
        <tr>
            <td><?=$row['id']?></td>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=htmlspecialchars($row['contact_person'])?></td>
            <td><?=htmlspecialchars($row['contact_phone'])?></td>
            <td><?=$row['service_end']?></td>
            <td>
                <?php if ($row['annual_report']): ?>
                    <span class="text-success">已申报（<?=$row['annual_report']['reported_at']?>）</span>
                <?php elseif ($days_left < 0): ?>
                    <span class="text-danger">已过截止日未申报</span>
                <?php else: ?>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="contract_id" value="<?=$row['id']?>">
                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('确定要标记为已申报吗？')">标记已申报</button>
                    </form>
                <?php endif; ?>
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