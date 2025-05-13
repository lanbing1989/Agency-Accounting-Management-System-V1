<?php
require 'db.php';

// 【修改1】用uuid参数，不用id
$uuid = $_GET['uuid'] ?? '';

// 查询合同及关键信息
$stmt = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid
");
$stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$row) {
    echo json_encode(['ok'=>false, 'msg'=>'合同不存在']);
    exit;
}
if (!empty($row['sign_image']) && file_exists(__DIR__ . '/' . $row['sign_image'])) {
    echo json_encode(['ok'=>false, 'msg'=>'该合同已签署，不能重复签署！']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$imgBase64 = $data['signature'] ?? '';

if (!$uuid || !$imgBase64 || strpos($imgBase64, 'data:image/png;base64,') !== 0) {
    echo json_encode(['ok'=>false, 'msg'=>'参数错误']);
    exit;
}

// 保存为本地图片文件
$imgData = base64_decode(str_replace('data:image/png;base64,','',$imgBase64));
$saveDir = __DIR__.'/signatures/';
if (!is_dir($saveDir)) mkdir($saveDir,0777,true);
$filename = $saveDir . 'sign_' . $row['id'] . '_' . time() . '.png';
file_put_contents($filename, $imgData);
$relativePath = 'signatures/' . basename($filename);
$sign_date = date('Y-m-d');

// ========== 关键：签后快照再生成一次 ==========
// 获取服务期、分段
$period = null;
if ($row['service_period_id']) {
    $period = $db->query("SELECT * FROM service_periods WHERE id={$row['service_period_id']}")->fetchArray(SQLITE3_ASSOC);
}
$segment = null;
if ($row['service_segment_id']) {
    $segment = $db->query("SELECT * FROM service_segments WHERE id={$row['service_segment_id']}")->fetchArray(SQLITE3_ASSOC);
}
// 盖章
$seal_img = '';
if ($row['seal_id']) {
    $seal = $db->query("SELECT image_path FROM seal_templates WHERE id={$row['seal_id']}")->fetchArray(SQLITE3_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 变量
$vars = [
    'client_name'    => $row['client_name'] ?? '',
    'contact_person' => $row['contact_person'] ?? '',
    'contact_phone'  => $row['contact_phone'] ?? '',
    'contact_email'  => $row['contact_email'] ?? '',
    'remark'         => $row['remark'] ?? '',
    'service_start'  => $period['service_start'] ?? '',
    'service_end'    => $period['service_end'] ?? '',
    'month_count'    => $period['month_count'] ?? '',
    'package_type'   => $period['package_type'] ?? '',
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    'sign_date'      => $sign_date,
    'sign_year'      => date('Y', strtotime($sign_date)),
    'sign_month'     => date('m', strtotime($sign_date)),
    'sign_day'       => date('d', strtotime($sign_date)),
];

// 渲染最终快照
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
$content_snapshot = render_contract_template($row['template_content'], $vars, $seal_img, $relativePath);

// 更新合同，增加签署日期和最终快照
$stmt2 = $db->prepare("UPDATE contracts_agreement SET sign_image=:sign_image, sign_date=:sign_date, content_snapshot=:content_snapshot WHERE uuid=:uuid");
$stmt2->bindValue(':sign_image', $relativePath, SQLITE3_TEXT);
$stmt2->bindValue(':sign_date', $sign_date, SQLITE3_TEXT);
$stmt2->bindValue(':content_snapshot', $content_snapshot, SQLITE3_TEXT);
$stmt2->bindValue(':uuid', $uuid, SQLITE3_TEXT);
$stmt2->execute();

echo json_encode(['ok'=>true]);
?>