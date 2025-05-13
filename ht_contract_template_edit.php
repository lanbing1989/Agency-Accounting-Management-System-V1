<?php
require 'auth.php';
require 'db.php';

$id = intval($_GET['id'] ?? 0);
$isEdit = $id > 0;
$template = ['name'=>'', 'content'=>''];

if ($isEdit) {
    $row = $db->query("SELECT * FROM contract_templates WHERE id=$id")->fetchArray(SQLITE3_ASSOC);
    if ($row) $template = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $content = trim($_POST['content']);
    if ($isEdit) {
        $stmt = $db->prepare("UPDATE contract_templates SET name=:n, content=:c WHERE id=:id");
        $stmt->bindValue(':n', $name);
        $stmt->bindValue(':c', $content);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("INSERT INTO contract_templates (name, content, created_at) VALUES (:n, :c, :t)");
        $stmt->bindValue(':n', $name);
        $stmt->bindValue(':c', $content);
        $stmt->bindValue(':t', date('Y-m-d H:i:s'));
        $stmt->execute();
    }
    header("Location: ht_contract_templates.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $isEdit ? '编辑合同模板' : '新建合同模板' ?></title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
        textarea.form-control {
            font-family: 'Consolas', 'monospace';
            min-height: 480px;
            font-size: 16px;
            width: 100%;
            resize: vertical;
        }
        .field-table th, .field-table td { font-size: 14px; }
    </style>
</head>
<body>
<?php include('navbar.php');?>
<div class="container mt-4" style="max-width:1000px;">
    <h4><?= $isEdit ? '编辑合同模板' : '新建合同模板' ?></h4>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">模板名称</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($template['name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">模板内容（可用变量见下方）</label>
            <textarea name="content" class="form-control" required spellcheck="false"><?= htmlspecialchars($template['content']) ?></textarea>
        </div>
        <button class="btn btn-success"><?= $isEdit ? '保存修改' : '创建模板' ?></button>
        <a href="ht_contract_templates.php" class="btn btn-link">返回</a>
    </form>
    <div class="mt-4 mb-4">
        <h6>可用变量说明：</h6>
        <table class="table table-bordered field-table align-middle">
            <thead>
                <tr>
                    <th>变量名</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>{client_name}</code></td><td>客户名称</td></tr>
                <tr><td><code>{contact_person}</code></td><td>客户联系人</td></tr>
                <tr><td><code>{contact_phone}</code></td><td>客户电话</td></tr>
                <tr><td><code>{contact_email}</code></td><td>客户邮箱</td></tr>
                <tr><td><code>{remark}</code></td><td>客户备注</td></tr>
                <tr><td><code>{service_start}</code></td><td>服务期开始日期</td></tr>
                <tr><td><code>{service_end}</code></td><td>服务期结束日期</td></tr>
                <tr><td><code>{month_count}</code></td><td>服务月数</td></tr>
                <tr><td><code>{package_type}</code></td><td>套餐类型</td></tr>
                <tr><td><code>{price_per_year}</code></td><td>每年价格（如有分段则为分段价格）</td></tr>
                <tr><td><code>{segment_fee}</code></td><td>分段费用（如有分段时有效）</td></tr>
                <tr><td><code>{seal}</code></td><td>甲方盖章（自动插入签章图片）</td></tr>
                <tr><td><code>{signature}</code></td><td>甲方手写签名图片/在线签字</td></tr>
                <tr><td><code>{sign_date}</code></td><td>签署时间</td></tr>
            </tbody>
        </table>
        <div class="text-muted" style="font-size:13px;">
            说明：模板变量请用花括号包裹（如 <code>{client_name}</code> ），
            合同生成时会自动替换为对应内容。<br>
            <code>{seal}</code> 与 <code>{signature}</code> 为图片变量，可插入到相应位置（如签章/签字处）。<br>
            <code>{agreement_code}</code> 为合同编号，<code>{sign_date}</code> 为实际签署时间。
        </div>
    </div>
</div>
</body>
</html>