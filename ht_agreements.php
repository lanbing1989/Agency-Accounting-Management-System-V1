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
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
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
                    <!-- 修改为uuid跳转 -->
                    <a class="btn btn-sm btn-primary" href="ht_agreement_detail.php?uuid=<?= urlencode($a['uuid']) ?>">查看</a>
                    <a class="btn btn-sm btn-success" href="ht_agreement_sign.php?uuid=<?= urlencode($a['uuid']) ?>" target="_blank">在线签署</a>
                    <a class="btn btn-sm btn-info" href="javascript:void(0);" onclick="copySignLink('<?= $a['uuid'] ?>')">复制签署链接</a>
                    <a class="btn btn-sm btn-danger" href="ht_agreement_delete.php?uuid=<?= urlencode($a['uuid']) ?>"
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
function copySignLink(uuid) {
    var origin = window.location.origin || (window.location.protocol + "//" + window.location.host);
    var link = origin + '/ht_agreement_sign.php?uuid=' + encodeURIComponent(uuid);
    var tips = "您好，以下是您的合同在线签署链接，请在电脑或微信/浏览器中打开，按页面提示完成签署：\n" + link;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(tips).then(function() {
            alert("已复制签署链接，可粘贴发给客户：\n\n" + tips);
        }, function() {
            window.prompt("复制失败，请手动复制：", tips);
        });
    } else {
        window.prompt("请手动复制签署链接：", tips);
    }
}
</script>
</body>
</html>