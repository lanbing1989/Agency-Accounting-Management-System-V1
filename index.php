<?php
require 'db.php';

$where = '';
$params = [];
if (!empty($_GET['client_name'])) {
    $where .= ' AND c.client_name LIKE :client_name';
    $params[':client_name'] = '%' . $_GET['client_name'] . '%';
}

$query = "SELECT c.*, 
    (SELECT MAX(service_end) FROM service_periods sp WHERE sp.contract_id = c.id) AS latest_end
    FROM contracts c
    WHERE 1 $where
    ORDER BY latest_end DESC, c.id DESC";
$stmt = $db->prepare($query);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$result = $stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>客户列表</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container">
    <form class="row g-2 mb-3" method="get">
        <div class="col-auto">
            <input type="text" class="form-control" name="client_name" placeholder="客户名称" value="<?=htmlspecialchars($_GET['client_name']??'')?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">搜索</button>
        </div>
        <div class="col-auto">
            <a href="contract_add.php" class="btn btn-success">新增客户</a>
        </div>
    </form>
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>客户名称</th>
            <th>联系人</th>
            <th>联系电话</th>
            <th>联系邮箱</th>
            <th>最近服务期截止</th>
            <th>备注</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?=$row['id']?></td>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=htmlspecialchars($row['contact_person'])?></td>
            <td><?=htmlspecialchars($row['contact_phone'])?></td>
            <td><?=htmlspecialchars($row['contact_email'])?></td>
            <td><?=$row['latest_end'] ? $row['latest_end'] : '-'?></td>
            <td><?=htmlspecialchars($row['remark'])?></td>
            <td>
                <a href="contract_edit.php?id=<?=$row['id']?>" class="btn btn-sm btn-outline-primary">编辑</a>
                <a href="contract_detail.php?id=<?=$row['id']?>" class="btn btn-sm btn-outline-secondary">详情/服务期</a>
                <a href="contract_delete.php?id=<?=$row['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要彻底删除该客户及其所有服务期和记录吗？')">删除</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>