<?php
require 'auth.php';
require 'db.php';

$res = $db->query("
SELECT a.*, c.client_name, t.name AS template_name
FROM contracts_agreement a
LEFT JOIN contracts c ON a.client_id = c.id
LEFT JOIN contract_templates t ON a.template_id = t.id
ORDER BY a.id DESC
");

$agreements = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $agreements[] = $row;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同管理</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include('navbar.php');?>
<div class="container mt-4">
    <h4>合同管理</h4>
    <a class="btn btn-success mb-3" href="ht_agreement_add.php">新建合同</a>
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>ID</th>
                <th>客户名称</th>
                <th>模板</th>
                <th>状态</th>
                <th>签署时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($agreements as $a): ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['client_name']) ?></td>
                <td><?= htmlspecialchars($a['template_name']) ?></td>
                <td><?= (!empty($a['sign_image'])) ? '已签署' : '未签署' ?></td>
                <td><?= !empty($a['sign_date']) ? $a['sign_date'] : '未签署' ?></td>
                <td>
                    <a class="btn btn-sm btn-primary" href="ht_agreement_detail.php?id=<?= $a['id'] ?>">查看</a>
                    <a class="btn btn-sm btn-success" href="ht_agreement_sign.php?id=<?= $a['id'] ?>" target="_blank">在线签署</a>
                    <a class="btn btn-sm btn-info" href="javascript:void(0);" onclick="copySignLink(<?= $a['id'] ?>)">复制签署链接</a>
                    <a class="btn btn-sm btn-danger" href="ht_agreement_delete.php?id=<?= $a['id'] ?>"
                       onclick="return confirm('确定要删除该合同吗？此操作不可恢复！');">删除</a>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
    <div class="alert alert-info mt-4">
        点击“复制签署链接”后，将链接粘贴发送给客户，客户可通过该链接在电脑或手机浏览器在线签署合同。<br>
        如遇签名区域无法显示，请用微信/浏览器等打开。
    </div>
</div>
<script>
function copySignLink(id) {
    var origin = window.location.origin || (window.location.protocol + "//" + window.location.host);
    var url = origin + '/ht_agreement_sign.php?id=' + id;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(function() {
            alert("签署链接已复制，可粘贴发给客户：\n" + url + "\n\n提示：请让客户在电脑或微信/浏览器中打开此链接，在线签署合同。");
        }, function() {
            window.prompt("复制失败，请手动复制：", url);
        });
    } else {
        window.prompt("请手动复制签署链接：", url);
    }
}
</script>
</body>
</html>