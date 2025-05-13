<?php
require 'auth.php';
require 'db.php';
$id = intval($_GET['id']);
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $stmt = $db->prepare("UPDATE contracts SET client_name=:client_name, contact_person=:contact_person, contact_phone=:contact_phone, contact_email=:contact_email, remark=:remark WHERE id=:id");
    $stmt->bindValue(':client_name', $_POST['client_name']);
    $stmt->bindValue(':contact_person', $_POST['contact_person']);
    $stmt->bindValue(':contact_phone', $_POST['contact_phone']);
    $stmt->bindValue(':contact_email', $_POST['contact_email']);
    $stmt->bindValue(':remark', $_POST['remark']);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    header('Location: index.php');
    exit;
}
$row = $db->query("SELECT * FROM contracts WHERE id=$id")->fetchArray(SQLITE3_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>编辑客户</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">编辑客户</h2>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">客户名称</label>
            <input type="text" class="form-control" name="client_name" value="<?=htmlspecialchars($row['client_name'])?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">联系人</label>
            <input type="text" class="form-control" name="contact_person" value="<?=htmlspecialchars($row['contact_person'])?>">
        </div>
        <div class="mb-3">
            <label class="form-label">联系电话</label>
            <input type="text" class="form-control" name="contact_phone" value="<?=htmlspecialchars($row['contact_phone'])?>">
        </div>
        <div class="mb-3">
            <label class="form-label">联系邮箱</label>
            <input type="email" class="form-control" name="contact_email" value="<?=htmlspecialchars($row['contact_email'])?>">
        </div>
        <div class="mb-3">
            <label class="form-label">备注</label>
            <input type="text" class="form-control" name="remark" value="<?=htmlspecialchars($row['remark'])?>">
        </div>
        <button type="submit" class="btn btn-primary">保存</button>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>