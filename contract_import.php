<?php
require 'auth.php';
require 'db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv'])) {
    $file = $_FILES['csv']['tmp_name'];
    if (($handle = fopen($file, 'r')) !== false) {
        $header = fgetcsv($handle);
        if (!$header || $header[0] != '客户名称') {
            $error = 'CSV格式错误，第一列必须为“客户名称”。';
        } else {
            $count = 0; $skip = 0;
            while (($data = fgetcsv($handle)) !== false) {
                $client_name = trim($data[0] ?? '');
                $contact_person = trim($data[1] ?? '');
                $contact_phone = trim($data[2] ?? '');
                $contact_email = trim($data[3] ?? '');

                if ($client_name == '') continue;

                // 判断唯一性
                $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM contracts WHERE client_name = :client_name");
                $stmt->bindValue(':client_name', $client_name);
                $res = $stmt->execute();
                $row = $res->fetchArray(SQLITE3_ASSOC);
                if ($row['cnt']) { $skip++; continue; }

                // 插入
                $stmt = $db->prepare("INSERT INTO contracts (client_name, contact_person, contact_phone, contact_email) VALUES (:client_name, :contact_person, :contact_phone, :contact_email)");
                $stmt->bindValue(':client_name', $client_name);
                $stmt->bindValue(':contact_person', $contact_person);
                $stmt->bindValue(':contact_phone', $contact_phone);
                $stmt->bindValue(':contact_email', $contact_email);
                $stmt->execute();
                $count++;
            }
            $success = "导入完成，成功导入 {$count} 条，跳过 {$skip} 条（因客户名称重复）。";
        }
        fclose($handle);
    } else {
        $error = '文件打开失败，请重试。';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>导入客户</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">导入客户</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=$success?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm mb-3">
        <div class="mb-3">
            <label class="form-label">选择CSV文件</label>
            <input type="file" name="csv" accept=".csv" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">导入</button>
        <a href="index.php" class="btn btn-secondary">返回</a>
        <a href="client_import_template.csv" class="btn btn-link">下载模板</a>
    </form>
    <div>
        <p>请上传CSV文件，第一行为表头，格式如下：</p>
        <pre>客户名称,联系人,联系电话,联系邮箱</pre>
        <p>如有Excel文件，请先用Excel另存为CSV格式再上传。</p>
    </div>
</div>
</body>
</html>