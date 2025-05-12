<?php
require 'db.php';
function fix_date($d) {
    return preg_replace('/\./', '-', $d);
}

$package_types = [
    '小规模纳税人',
    '小规模纳税人零申报',
    '一般纳税人',
    '一般纳税人零申报'
];

$contract_id = intval($_GET['contract_id']);
$contract = $db->query("SELECT * FROM contracts WHERE id=$contract_id")->fetchArray(SQLITE3_ASSOC);

$renew_period = null;
$default_month = date('Y-m');
$default_package_type = $package_types[0];
if (!empty($_GET['renew'])) {
    $renew_id = intval($_GET['renew']);
    $renew_period = $db->query("SELECT * FROM service_periods WHERE id=$renew_id AND contract_id=$contract_id")->fetchArray(SQLITE3_ASSOC);
    if ($renew_period) {
        $default_month = date('Y-m', strtotime($renew_period['service_end'] . ' +1 day'));
        $default_package_type = $renew_period['package_type'] ?? $package_types[0];
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $month = $_POST['service_month'];
    $months = intval($_POST['month_count']);
    $start = $month . '-01';
    $end = date('Y-m-d', strtotime("+$months months -1 day", strtotime($start)));
    $package_type = $_POST['package_type'];

    // 检查是否有重叠服务期
    $sql = "SELECT * FROM service_periods WHERE contract_id=:contract_id AND (service_start <= :end AND service_end >= :start)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':contract_id', $contract_id);
    $stmt->bindValue(':start', $start);
    $stmt->bindValue(':end', $end);
    $overlap = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($overlap) {
        $error = '该客户本时间段已存在服务期，不可重复添加！';
    } else {
        $stmt = $db->prepare("INSERT INTO service_periods (contract_id, service_start, service_end, month_count, package_type) VALUES (:contract_id, :service_start, :service_end, :month_count, :package_type)");
        $stmt->bindValue(':contract_id', $contract_id);
        $stmt->bindValue(':service_start', $start);
        $stmt->bindValue(':service_end', $end);
        $stmt->bindValue(':month_count', $months);
        $stmt->bindValue(':package_type', $package_type);
        $stmt->execute();
        $period_id = $db->lastInsertRowID();

        // 自动生成默认分段
        $price_per_year = floatval($_POST['price_per_year']);
        $start_fixed = fix_date($start);
        $end_fixed = fix_date($end);
        $start_ts = strtotime($start_fixed);
        $end_ts = strtotime($end_fixed);
        if ($start_ts === false || $end_ts === false) die("日期格式错误!");
        $days = ($end_ts - $start_ts) / (60*60*24) + 1;
        $fee = round($price_per_year * $days / 365, 2);
        $stmt2 = $db->prepare("INSERT INTO service_segments (service_period_id, start_date, end_date, price_per_year, segment_fee, package_type, remark) VALUES (:service_period_id, :start_date, :end_date, :price_per_year, :segment_fee, :package_type, :remark)");
        $stmt2->bindValue(':service_period_id', $period_id);
        $stmt2->bindValue(':start_date', $start_fixed);
        $stmt2->bindValue(':end_date', $end_fixed);
        $stmt2->bindValue(':price_per_year', $price_per_year);
        $stmt2->bindValue(':segment_fee', $fee);
        $stmt2->bindValue(':package_type', $package_type);
        $stmt2->bindValue(':remark', '默认分段');
        $stmt2->execute();

        header('Location: contract_detail.php?id='.$contract_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>新增/续费服务期</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4"><?= isset($renew_period) ? '续期服务期' : '新增/续费服务期' ?></h2>
    <div class="mb-3"><b>客户：</b><?=htmlspecialchars($contract['client_name'])?></div>
    <?php if($error): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif;?>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">起始年月</label>
            <input type="month" class="form-control" name="service_month" value="<?=$default_month?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">服务月数</label>
            <input type="number" class="form-control" name="month_count" value="12" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">套餐类型</label>
            <select name="package_type" class="form-control" required>
                <?php foreach($package_types as $type): ?>
                    <option value="<?=$type?>" <?=($type==$default_package_type)?'selected':''?>><?=$type?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">年服务费（元/年）</label>
            <input type="number" step="0.01" class="form-control" name="price_per_year" required>
        </div>
        <button type="submit" class="btn btn-success">保存</button>
        <a href="contract_detail.php?id=<?=$contract_id?>" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>