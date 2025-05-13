<?php
require 'auth.php';
require 'db.php';

if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $db->exec("DELETE FROM contract_templates WHERE id=$id");
    header("Location: ht_contract_templates.php");
    exit;
}

$res = $db->query("SELECT * FROM contract_templates ORDER BY id DESC");
$templates = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $templates[] = $row;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同模板管理</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include('navbar.php');?>
<div class="container mt-4">
    <h4>合同模板管理</h4>
    <a class="btn btn-success mb-3" href="ht_contract_template_edit.php">新建模板</a>
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>ID</th>
                <th>模板名称</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($templates as $tpl): ?>
            <tr>
                <td><?= $tpl['id'] ?></td>
                <td><?= htmlspecialchars($tpl['name']) ?></td>
                <td><?= $tpl['created_at'] ?></td>
                <td>
                    <a class="btn btn-sm btn-primary" href="ht_contract_template_edit.php?id=<?= $tpl['id'] ?>">编辑</a>
                    <a class="btn btn-sm btn-danger" href="ht_contract_templates.php?del=<?= $tpl['id'] ?>" onclick="return confirm('确定要删除该模板吗？')">删除</a>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
</div>
</body>
</html>