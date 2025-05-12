<?php
require 'auth.php';
require 'db.php';

// 客户下拉
$clients = [];
$res = $db->query("SELECT id, client_name FROM contracts ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $clients[] = $row;

// 模板下拉
$templates = [];
$res = $db->query("SELECT id, name FROM contract_templates ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $templates[] = $row;

// 签章模板下拉
$seals = [];
$res = $db->query("SELECT id, name FROM seal_templates ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $seals[] = $row;

// 保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id']);
    $template_id = intval($_POST['template_id']);
    $service_period_id = intval($_POST['service_period_id']);
    $service_segment_id = intval($_POST['service_segment_id'] ?? 0);
    $seal_id = intval($_POST['seal_id'] ?? 0);

    $stmt = $db->prepare("INSERT INTO contracts_agreement (client_id, template_id, service_period_id, service_segment_id, seal_id) VALUES (:client_id, :template_id, :service_period_id, :service_segment_id, :seal_id)");
    $stmt->bindValue(':client_id', $client_id, SQLITE3_INTEGER);
    $stmt->bindValue(':template_id', $template_id, SQLITE3_INTEGER);
    $stmt->bindValue(':service_period_id', $service_period_id ? $service_period_id : null, SQLITE3_INTEGER);
    $stmt->bindValue(':service_segment_id', $service_segment_id ? $service_segment_id : null, SQLITE3_INTEGER);
    $stmt->bindValue(':seal_id', $seal_id ? $seal_id : null, SQLITE3_INTEGER);
    $stmt->execute();
    $id = $db->lastInsertRowID();
    header("Location: ht_agreement_detail.php?id=$id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>新建合同</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php');?>
<div class="container">
    <h4 class="mt-4 mb-3">新建合同</h4>
    <form method="post" class="bg-white p-4 rounded shadow-sm" id="agreementForm">
        <div class="mb-3">
            <label class="form-label">选择客户</label>
            <select name="client_id" class="form-select" id="clientSelect" required>
                <option value="">请选择客户</option>
                <?php foreach($clients as $c): ?>
                <option value="<?=$c['id']?>"><?=htmlspecialchars($c['client_name'])?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">选择合同模板</label>
            <select name="template_id" class="form-select" required>
                <option value="">请选择模板</option>
                <?php foreach($templates as $t): ?>
                <option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">选择服务期</label>
            <select name="service_period_id" class="form-select" id="periodSelect" required>
                <option value="">请先选择客户</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">选择分段（可选）</label>
            <select name="service_segment_id" class="form-select" id="segmentSelect">
                <option value="">不选择分段</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">签章模板</label>
            <select name="seal_id" class="form-select">
                <option value="">不盖章</option>
                <?php foreach($seals as $s): ?>
                    <option value="<?=$s['id']?>"><?=htmlspecialchars($s['name'])?></option>
                <?php endforeach;?>
            </select>
        </div>
        <button class="btn btn-success">生成合同</button>
        <a href="ht_agreements.php" class="btn btn-link">返回</a>
    </form>
</div>
<script>
$('#clientSelect').on('change', function() {
    let cid = $(this).val();
    $('#periodSelect').html('<option value="">加载中...</option>');
    $('#segmentSelect').html('<option value="">不选择分段</option>');
    if(cid){
        $.get('ajax_service_periods.php', {contract_id: cid}, function(res) {
            $('#periodSelect').html(res);
        });
    }else{
        $('#periodSelect').html('<option value="">请先选择客户</option>');
        $('#segmentSelect').html('<option value="">不选择分段</option>');
    }
});
$('#periodSelect').on('change', function() {
    let pid = $(this).val();
    $('#segmentSelect').html('<option value="">加载中...</option>');
    if(pid){
        $.get('ajax_service_segments.php', {service_period_id: pid}, function(res) {
            $('#segmentSelect').html(res);
        });
    }else{
        $('#segmentSelect').html('<option value="">不选择分段</option>');
    }
});
</script>
</body>
</html>