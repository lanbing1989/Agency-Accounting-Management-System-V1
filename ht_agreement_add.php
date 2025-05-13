<?php
require 'auth.php';
require 'db.php';

// 客户下拉
$clients = [];
$res = $db->query("SELECT id, client_name FROM contracts ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $clients[] = $row;

// 模板下拉
$templates = [];
$res = $db->query("SELECT id, name, content FROM contract_templates ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $templates[] = $row;

// 签章模板下拉
$seals = [];
$res = $db->query("SELECT id, name, image_path FROM seal_templates ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $seals[] = $row;

// 保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id']);
    $template_id = intval($_POST['template_id']);
    $service_period_id = intval($_POST['service_period_id']);
    $service_segment_id = intval($_POST['service_segment_id'] ?? 0);
    $seal_id = intval($_POST['seal_id'] ?? 0);

    // 生成随机唯一uuid
    function generate_uuid($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }
    $uuid = generate_uuid(16); // 16字符长度

    // 获取合同模板内容
    $template = $db->query("SELECT * FROM contract_templates WHERE id=$template_id")->fetchArray(SQLITE3_ASSOC);
    $template_content = $template ? $template['content'] : '';

    // 获取客户信息
    $client = $db->query("SELECT * FROM contracts WHERE id=$client_id")->fetchArray(SQLITE3_ASSOC);

    // 服务期
    $period = null;
    if ($service_period_id) {
        $period = $db->query("SELECT * FROM service_periods WHERE id=$service_period_id")->fetchArray(SQLITE3_ASSOC);
    }

    // 分段
    $segment = null;
    if ($service_segment_id) {
        $segment = $db->query("SELECT * FROM service_segments WHERE id=$service_segment_id")->fetchArray(SQLITE3_ASSOC);
    }

    // 盖章图片
    $seal_img = '';
    if ($seal_id) {
        $seal = $db->query("SELECT image_path FROM seal_templates WHERE id=$seal_id")->fetchArray(SQLITE3_ASSOC);
        if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
    }
    $signature_img = '';

    // 合同变量
    $vars = [
        'client_name'    => $client['client_name'] ?? '',
        'contact_person' => $client['contact_person'] ?? '',
        'contact_phone'  => $client['contact_phone'] ?? '',
        'contact_email'  => $client['contact_email'] ?? '',
        'remark'         => $client['remark'] ?? '',
        'service_start'  => $period['service_start'] ?? '',
        'service_end'    => $period['service_end'] ?? '',
        'month_count'    => $period['month_count'] ?? '',
        'package_type'   => $period['package_type'] ?? '',
        'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
        'segment_fee'    => $segment['segment_fee'] ?? '',
        'sign_date'      => date('Y-m-d'),
        'sign_year'      => date('Y'),
        'sign_month'     => date('m'),
        'sign_day'       => date('d'),
    ];

    // 合同渲染函数
    function render_contract_template($tpl, $vars, $seal_img = '', $signature_img = '') {
        if ($seal_img && strpos($tpl, '{seal}') !== false) {
            $tpl = str_replace('{seal}', '<img src="' . $seal_img . '" style="height:60px;">', $tpl);
        }
        if ($signature_img && strpos($tpl, '{signature}') !== false) {
            $tpl = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:60px;">', $tpl);
        }
        foreach ($vars as $k => $v) $tpl = str_replace('{'.$k.'}', htmlspecialchars($v), $tpl);
        return $tpl;
    }
    // 生成快照
    $content_snapshot = render_contract_template($template_content, $vars, $seal_img, $signature_img);

    // 插入
    $stmt = $db->prepare("INSERT INTO contracts_agreement (client_id, template_id, service_period_id, service_segment_id, seal_id, uuid, content_snapshot) VALUES (:client_id, :template_id, :service_period_id, :service_segment_id, :seal_id, :uuid, :content_snapshot)");
    $stmt->bindValue(':client_id', $client_id, SQLITE3_INTEGER);
    $stmt->bindValue(':template_id', $template_id, SQLITE3_INTEGER);
    $stmt->bindValue(':service_period_id', $service_period_id ? $service_period_id : null, SQLITE3_INTEGER);
    $stmt->bindValue(':service_segment_id', $service_segment_id ? $service_segment_id : null, SQLITE3_INTEGER);
    $stmt->bindValue(':seal_id', $seal_id ? $seal_id : null, SQLITE3_INTEGER);
    $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
    $stmt->bindValue(':content_snapshot', $content_snapshot, SQLITE3_TEXT);
    $stmt->execute();

    header("Location: ht_agreement_detail.php?uuid=$uuid");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>新建合同</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/bootstrap/jquery.min.js"></script>
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