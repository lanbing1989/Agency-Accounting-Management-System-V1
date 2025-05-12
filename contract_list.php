<?php
require 'db.php';

$where = '';
$params = [];
if (!empty($_GET['client_name'])) {
    $where .= ' AND client_name LIKE :client_name';
    $params[':client_name'] = '%' . $_GET['client_name'] . '%';
}
if (!empty($_GET['contract_no'])) {
    $where .= ' AND contract_no LIKE :contract_no';
    $params[':contract_no'] = '%' . $_GET['contract_no'] . '%';
}

$query = "SELECT * FROM contracts WHERE 1 $where ORDER BY id DESC";
$stmt = $db->prepare($query);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$result = $stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-4">合同管理系统</h2>
    <form class="row g-2 mb-3" method="get">
        <div class="col-auto">
            <input type="text" class="form-control" name="client_name" placeholder="客户名称" value="<?=htmlspecialchars($_GET['client_name']??'')?>">
        </div>
        <div class="col-auto">
            <input type="text" class="form-control" name="contract_no" placeholder="合同编号" value="<?=htmlspecialchars($_GET['contract_no']??'')?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">搜索</button>
        </div>
        <div class="col-auto">
            <a href="contract_add.php" class="btn btn-success">新增合同</a>
        </div>
        <div class="col-auto">
            <a href="remind_list.php" class="btn btn-warning">催收提醒</a>
        </div>
    </form>
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>客户名称</th>
            <th>合同编号</th>
            <th>服务期限</th>
            <th>总金额</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?=$row['id']?></td>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=htmlspecialchars($row['contract_no'])?></td>
            <td><?=htmlspecialchars($row['service_start'])?> ~ <?=htmlspecialchars($row['service_end'])?></td>
            <td><?=$row['total_fee']?></td>
            <td>
                <a href="contract_edit.php?id=<?=$row['id']?>" class="btn btn-sm btn-outline-primary">编辑</a>
                <a href="payment_list.php?contract_id=<?=$row['id']?>" class="btn btn-sm btn-outline-secondary">收费记录</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>