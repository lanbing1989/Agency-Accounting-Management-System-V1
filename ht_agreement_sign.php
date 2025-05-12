<?php
require 'db.php';
$id = intval($_GET['id'] ?? 0);
$agreement = $db->query("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.seal_id
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.id=$id
")->fetchArray(SQLITE3_ASSOC);

if (!$agreement) die('合同不存在');

// 服务期
$period = null;
if ($agreement['service_period_id']) {
    $period = $db->query("SELECT * FROM service_periods WHERE id={$agreement['service_period_id']}")->fetchArray(SQLITE3_ASSOC);
}
// 分段
$segment = null;
if ($agreement['service_segment_id']) {
    $segment = $db->query("SELECT * FROM service_segments WHERE id={$agreement['service_segment_id']}")->fetchArray(SQLITE3_ASSOC);
}

// 获取盖章图片
$seal_img = '';
if ($agreement['seal_id']) {
    $seal = $db->query("SELECT image_path FROM seal_templates WHERE id={$agreement['seal_id']}")->fetchArray(SQLITE3_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 签名图片
$signature_img = $agreement['sign_image'] ?? '';

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
    // 价格优先用分段，否则用服务期
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    // 签署日期相关变量
    'sign_date'      => $sign_date,
    'sign_year'      => $sign_year,
    'sign_month'     => $sign_month,
    'sign_day'       => $sign_day,
];

function render_contract_template($tpl, $vars, $seal_img = '', $signature_img = '') {
    if ($seal_img && strpos($tpl, '{seal}') !== false) {
        $tpl = str_replace('{seal}', '<img src="' . $seal_img . '" style="height:60px;">', $tpl);
    }
    if ($signature_img && strpos($tpl, '{signature}') !== false) {
        $tpl = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:60px;">', $tpl);
    } else if (strpos($tpl, '{signature}') !== false) {
        $tpl = str_replace('{signature}', '<button id="showSignPad" class="btn btn-outline-primary btn-sm">甲方在线签字</button><div id="signPadArea" style="display:none;margin-top:10px;"></div>', $tpl);
    }
    foreach ($vars as $k => $v) $tpl = str_replace('{'.$k.'}', htmlspecialchars($v), $tpl);
    return $tpl;
}
$content = render_contract_template($agreement['template_content'], $vars, $seal_img, $signature_img);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同在线签署</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
    #signature-pad { border:1px solid #aaa; border-radius:8px; background:#fff; }
    </style>
</head>
<body>
<div class="container mt-4" style="max-width:800px;">
    <h4>合同在线签署</h4>
    <div class="bg-white p-4 rounded shadow-sm mb-4" style="white-space:pre-line;" id="contractContent">
        <?= $content ?>
    </div>
    <?php if ($signature_img): ?>
        <div class="alert alert-success">已签署完成，合同内容只读。</div>
        <a class="btn btn-info" href="ht_agreement_pdf.php?id=<?= $id ?>" target="_blank">下载PDF</a>
    <?php endif;?>
    <a class="btn btn-secondary" href="javascript:history.back();">返回</a>
</div>

<?php if (!$signature_img): ?>
<!-- 仅未签署时加载签名板JS -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let showBtn = document.getElementById('showSignPad');
    if (showBtn) {
        showBtn.onclick = function() {
            const area = document.getElementById('signPadArea');
            area.innerHTML = `
                <canvas id="signature-pad" width="350" height="100"></canvas>
                <div class="mt-2">
                  <button id="clear-sign" class="btn btn-warning btn-sm">清除</button>
                  <button id="save-sign" class="btn btn-success btn-sm">确认签署</button>
                </div>
            `;
            area.style.display = '';
            let canvas = document.getElementById('signature-pad');
            let pad = new SignaturePad(canvas);

            document.getElementById('clear-sign').onclick = function() { pad.clear(); }
            document.getElementById('save-sign').onclick = function() {
                if (pad.isEmpty()) { alert('请先签名'); return; }
                let data = pad.toDataURL('image/png');
                // AJAX保存
                fetch('ht_agreement_sign_save.php?id=<?= $agreement['id']?>',{
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
<?php endif;?>
</body>
</html>