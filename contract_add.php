<?php
require 'auth.php';
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD']=='POST') {
    // 检查客户名称是否已存在
$checkStmt = $db->prepare("SELECT COUNT(*) as cnt FROM contracts WHERE client_name = :client_name");
$checkStmt->bindValue(':client_name', $_POST['client_name']);
$result = $checkStmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$exists = $row['cnt'];

    if ($exists) {
        $error = '客户名称已存在，请勿重复添加。';
    } else {
        $stmt = $db->prepare("INSERT INTO contracts (client_name, contact_person, contact_phone, contact_email, remark) VALUES (:client_name, :contact_person, :contact_phone, :contact_email, :remark)");
        $stmt->bindValue(':client_name', $_POST['client_name']);
        $stmt->bindValue(':contact_person', $_POST['contact_person']);
        $stmt->bindValue(':contact_phone', $_POST['contact_phone']);
        $stmt->bindValue(':contact_email', $_POST['contact_email']);
        $stmt->bindValue(':remark', $_POST['remark']);
        $stmt->execute();
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>新增客户</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">新增客户</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">客户名称</label>
            <input type="text" class="form-control" name="client_name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">联系人</label>
            <input type="text" class="form-control" name="contact_person">
        </div>
        <div class="mb-3">
            <label class="form-label">联系电话</label>
            <input type="text" class="form-control" name="contact_phone">
        </div>
        <div class="mb-3">
            <label class="form-label">联系邮箱</label>
            <input type="email" class="form-control" name="contact_email">
        </div>
        <div class="mb-3">
            <label class="form-label">备注</label>
            <input type="text" class="form-control" name="remark">
        </div>
        <button type="submit" class="btn btn-success">保存</button>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>