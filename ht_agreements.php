<?php
require 'auth.php';
require 'db.php';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 50;
$offset = ($page - 1) * $page_size;

// 支持客户名称搜索
$where = '';
$params = [];
if (!empty($_GET['client_name'])) {
    $where .= ' AND c.client_name LIKE :client_name';
    $params[':client_name'] = '%' . $_GET['client_name'] . '%';
}

// 统计总数
$count_query = "SELECT COUNT(*)
    FROM contracts_agreement a
    LEFT JOIN contracts c ON a.client_id = c.id
    LEFT JOIN contract_templates t ON a.template_id = t.id
    WHERE 1 $where";
$count_stmt = $db->prepare($count_query);
foreach ($params as $k => $v) $count_stmt->bindValue($k, $v);
$total = $count_stmt->execute()->fetchArray()[0];
$total_pages = max(1, ceil($total / $page_size));

// 查询合同数据
$query = "SELECT a.*, c.client_name, t.name AS template_name
    FROM contracts_agreement a
    LEFT JOIN contracts c ON a.client_id = c.id
    LEFT JOIN contract_templates t ON a.template_id = t.id
    WHERE 1 $where
    ORDER BY a.id DESC
    LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $page_size, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$res = $stmt->execute();

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
    <form class="row g-2 mb-3" method="get">
        <div class="col-auto">
            <input type="text" class="form-control" name="client_name" placeholder="客户名称" value="<?=htmlspecialchars($_GET['client_name']??'')?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">搜索</button>
        </div>
        <div class="col-auto">
            <a class="btn btn-success" href="ht_agreement_add.php">新建合同</a>
        </div>
    </form>
    <div class="table-responsive">
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
                    <a class="btn btn-sm btn-primary" href="ht_agreement_detail.php?uuid=<?= urlencode($a['uuid']) ?>">查看</a>
                    <a class="btn btn-sm btn-success" href="ht_agreement_sign.php?uuid=<?= urlencode($a['uuid']) ?>" target="_blank">在线签署</a>
                    <a class="btn btn-sm btn-info" href="javascript:void(0);" onclick="copySignLink('<?= $a['uuid'] ?>')">复制签署链接</a>
                    <a class="btn btn-sm btn-danger" href="ht_agreement_delete.php?uuid=<?= urlencode($a['uuid']) ?>"
                       onclick="return confirm('确定要删除该合同吗？此操作不可恢复！');">删除</a>
                    <a class="btn btn-sm btn-warning" href="ht_agreement_pdf.php?uuid=<?= urlencode($a['uuid']) ?>" target="_blank">下载PDF</a>
                </td>
            </tr>
        <?php endforeach;?>
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