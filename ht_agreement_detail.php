<?php
require 'auth.php';
require 'db.php';

// 通过 uuid 获取参数
$uuid = $_GET['uuid'] ?? '';
$agreement = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.seal_id
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid
");
$agreement->bindValue(':uuid', $uuid, SQLITE3_TEXT);
$agreement = $agreement->execute()->fetchArray(SQLITE3_ASSOC);

if (!$agreement) {
    die('合同不存在');
}

// 获取签章图片
$seal_img = '';
if ($agreement['seal_id']) {
    $seal = $db->query("SELECT image_path FROM seal_templates WHERE id={$agreement['seal_id']}")->fetchArray(SQLITE3_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 签名图片base64
$signature_img = $agreement['sign_image'] ?? '';

// 获取服务期详情
$period = null;
if ($agreement['service_period_id']) {
    $period = $db->query("SELECT * FROM service_periods WHERE id={$agreement['service_period_id']}")->fetchArray(SQLITE3_ASSOC);
}

// 获取分段详情
$segment = null;
if ($agreement['service_segment_id']) {
    $segment = $db->query("SELECT * FROM service_segments WHERE id={$agreement['service_segment_id']}")->fetchArray(SQLITE3_ASSOC);
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

// 组装变量
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
    // price_per_year 优先用分段，否则用服务期
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    // 签署日期相关
    'sign_date'      => $sign_date,
    'sign_year'      => $sign_year,
    'sign_month'     => $sign_month,
    'sign_day'       => $sign_day,
];

// 合同内容渲染函数
function render_contract_template($tpl, $vars, $seal_img = '', $signature_img = '') {
    if ($seal_img && strpos($tpl, '{seal}') !== false) {
        $tpl = str_replace('{seal}', '<img src="' . $seal_img . '" style="height:60px;">', $tpl);
    }
    if (strpos($tpl, '{signature}') !== false) {
        if ($signature_img) {
            $tpl = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:60px;">', $tpl);
        } else {
            $tpl = str_replace('{signature}', 
                '<button id="showSignPad" class="btn btn-outline-primary btn-sm">甲方在线签字</button><div id="signPadArea" style="display:none;margin-top:10px;"></div>', 
                $tpl
            );
        }
    }
    foreach ($vars as $k => $v) $tpl = str_replace('{'.$k.'}', htmlspecialchars($v), $tpl);
    return $tpl;
}

// ========== 关键：未签署时动态渲染，已签署时优先用快照 ==========
if (empty($agreement['sign_image'])) {
    // 未签署，允许签字
    $content = render_contract_template($agreement['template_content'], $vars, $seal_img, '');
} else {
    // 已签署，优先用快照（含签字图片）
    if (!empty($agreement['content_snapshot'])) {
        $content = $agreement['content_snapshot'];
    } else {
        $content = render_contract_template($agreement['template_content'], $vars, $seal_img, $signature_img);
    }
}

// 所有签署功能/下载链接等都用uuid
$sign_url = "/ht_agreement_sign.php?uuid=" . urlencode($agreement['uuid']);

// 读取pdf_map.json，获取pdf下载链接
$pdf_url = '';
$mapfile = __DIR__ . '/uploads/pdf_map.json';
if (file_exists($mapfile)) {
    $map = json_decode(file_get_contents($mapfile), true);
    if (isset($map[$agreement['uuid']])) {
        $pdf_url = $map[$agreement['uuid']];
        if ($pdf_url[0] !== '/' && strpos($pdf_url, 'uploads') === 0) {
            $pdf_url = '/' . $pdf_url;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同详情</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    #signature-pad { border:1px solid #aaa; border-radius:8px; background:#fff; }
    </style>
</head>
<body>
<?php include('navbar.php');?>
<div class="container mt-4" style="max-width:800px;">
    <h4>合同详情</h4>
    <div class="bg-white p-4 rounded shadow-sm mb-4" style="white-space:pre-line;" id="contractContent">
        <?= $content ?>
    </div>
    <div class="mb-3">
        <a class="btn btn-success" href="<?= $sign_url ?>" target="_blank">在线签署</a>
        <button class="btn btn-info" onclick="copySignLink('<?= $agreement['uuid']?>')">复制签署链接</button>
        <?php if ($pdf_url): ?>
            <a class="btn btn-primary" href="<?= $pdf_url ?>" target="_blank">下载PDF</a>
        <?php endif; ?>
        <a class="btn btn-danger" href="ht_agreement_delete.php?uuid=<?= urlencode($agreement['uuid'])?>" onclick="return confirm('确定要删除该合同吗？此操作不可恢复！');">删除合同</a>
        <a class="btn btn-secondary" href="ht_agreements.php">返回</a>
    </div>
</div>

<script src="/bootstrap/signature_pad.umd.min.js"></script>
<script>
function copySignLink(uuid) {
    var origin = window.location.origin || (window.location.protocol + "//" + window.location.host);
    var link = origin + '/ht_agreement_sign.php?uuid=' + uuid;
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

document.addEventListener('DOMContentLoaded', function() {
    let showBtn = document.getElementById('showSignPad');
    if (showBtn) {
        showBtn.onclick = function() {
            const area = document.getElementById('signPadArea');
            area.innerHTML = `
                <canvas id="signature-pad" width="350" height="100"></canvas>
                <div class="mt-2">
                  <button id="clear-sign" class="btn btn-warning btn-sm">清除</button>
                  <button id="save-sign" class="btn btn-success btn-sm">保存签名</button>
                </div>
            `;
            area.style.display = '';
            let canvas = document.getElementById('signature-pad');
            let pad = new SignaturePad(canvas);

            document.getElementById('clear-sign').onclick = function() { pad.clear(); }
            document.getElementById('save-sign').onclick = function() {
                if (pad.isEmpty()) { alert('请先签名'); return; }
                let data = pad.toDataURL('image/png');
                fetch('ht_agreement_sign_save.php?uuid=<?= urlencode($agreement['uuid'])?>',{
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({ signature: data })
                }).then(r=>r.json()).then(res=>{
                    if(res.ok){
                        location.reload();
                    }else{
                        alert(res.msg||'保存失败');
                    }
                });
            }
        }
    }
});
</script>
</body>
</html>