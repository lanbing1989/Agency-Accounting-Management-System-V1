<?php
require_once('tcpdf_min/tcpdf.php');
require 'db.php';

$uuid = $_GET['uuid'] ?? '';

$stmt = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.seal_id
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid
");
$stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
$agreement = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$agreement) die('合同不存在');

// 查询服务期表
$period = null;
if (!empty($agreement['service_period_id'])) {
    $period = $db->query("SELECT * FROM service_periods WHERE id={$agreement['service_period_id']}")->fetchArray(SQLITE3_ASSOC);
}
// 查询分段表
$segment = null;
if (!empty($agreement['service_segment_id'])) {
    $segment = $db->query("SELECT * FROM service_segments WHERE id={$agreement['service_segment_id']}")->fetchArray(SQLITE3_ASSOC);
}

// 获取盖章图片
$seal_img = '';
if ($agreement['seal_id']) {
    $seal = $db->query("SELECT image_path FROM seal_templates WHERE id={$agreement['seal_id']}")->fetchArray(SQLITE3_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 获取签名图片
$signature_img = '';
if (!empty($agreement['sign_image']) && file_exists($agreement['sign_image'])) {
    $signature_img = $agreement['sign_image'];
}

// 处理签署日期
if (!empty($agreement['sign_date'])) {
    $sign_date = $agreement['sign_date'];
    $sign_year = date('Y', strtotime($sign_date));
    $sign_month = date('m', strtotime($sign_date));
    $sign_day = date('d', strtotime($sign_date));
} else {
    $sign_date = date('Y-m-d');
    $sign_year = date('Y');
    $sign_month = date('m');
    $sign_day = date('d');
}

$vars = [
    'client_name'    => $agreement['client_name'] ?? '',
    'contact_person' => $agreement['contact_person'] ?? '',
    'contact_phone'  => $agreement['contact_phone'] ?? '',
    'contact_email'  => $agreement['contact_email'] ?? '',
    'remark'         => $agreement['remark'] ?? '',
    'service_start'  => $period['service_start'] ?? '',
    'service_end'    => $period['service_end'] ?? '',
    'month_count'    => $period['month_count'] ?? '',
    'package_type'   => $period['package_type'] ?? '',
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    'today'          => $sign_date,
    'year'           => $sign_year,
    'month'          => $sign_month,
    'day'            => $sign_day,
    'sign_date'      => $sign_date,
    'sign_year'      => $sign_year,
    'sign_month'     => $sign_month,
    'sign_day'       => $sign_day,
];

// ========== 关键：优先使用快照内容 ==========
if (!empty($agreement['content_snapshot'])) {
    $content = $agreement['content_snapshot'];
    // 统一各种换行符和tab为<br>
    $content = str_replace(["\r\n", "\r", "\n", "\t"], '<br>', $content);
} else {
    $content = $agreement['template_content'];
    if ($seal_img) {
        $content = str_replace('{seal}', '<img src="' . $seal_img . '" style="width:42mm; height:42mm;">', $content);
    } else {
        $content = str_replace('{seal}', '', $content);
    }
    if ($signature_img) {
        $content = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:20mm;">', $content);
    } else {
        $content = str_replace('{signature}', '', $content);
    }
    foreach ($vars as $k => $v) {
        $content = str_replace('{' . $k . '}', htmlspecialchars($v), $content);
    }
    $content = nl2br($content);
}

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('stsongstdlight', '', 13, '', false); // 中文支持
$pdf->writeHTML($content, true, false, true, false, '');

$pdf->Output('agreement_'.$uuid.'.pdf', 'I');
?>